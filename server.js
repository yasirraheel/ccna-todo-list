require('dotenv').config();

const express = require('express');
const cors = require('cors');
const fs = require('fs/promises');
const path = require('path');
const { Innertube } = require('youtubei.js');

const app = express();
const port = process.env.PORT || 3000;
const dbPath = path.join(__dirname, 'db.json');
const DEFAULT_IMPORT_LIMIT = 300;
const MAX_IMPORT_LIMIT = 500;
const PUBLIC_API_BASE_URL = String(process.env.PUBLIC_API_BASE_URL || '').trim().replace(/\/+$/, '');

app.use(express.json());
app.use(cors());
app.use(express.static(__dirname));

app.get('/api/config', (_req, res) => {
  return res.json({
    apiBaseUrl: PUBLIC_API_BASE_URL || '',
    port
  });
});

async function readDb() {
  const file = await fs.readFile(dbPath, 'utf8');
  return JSON.parse(file);
}

async function writeDb(data) {
  await fs.writeFile(dbPath, JSON.stringify(data, null, 2), 'utf8');
}

let youtubeClientPromise;

async function getYoutubeClient() {
  if (!youtubeClientPromise) {
    youtubeClientPromise = Innertube.create();
  }
  return youtubeClientPromise;
}

function parsePlaylistId(value) {
  if (!value || typeof value !== 'string') return '';
  const trimmed = value.trim();
  if (/^[\w-]+$/.test(trimmed)) return trimmed;

  try {
    const url = new URL(trimmed);
    const listParam = url.searchParams.get('list');
    if (listParam) return listParam;
  } catch (_error) {
    return '';
  }

  return '';
}

function normalizeText(value) {
  return String(value || '').trim().toLowerCase();
}

function resolveImportLimit(value) {
  const parsed = Number(value);
  if (!Number.isFinite(parsed)) return DEFAULT_IMPORT_LIMIT;
  return Math.max(1, Math.min(Math.floor(parsed), MAX_IMPORT_LIMIT));
}

function getTitleText(item) {
  if (!item) return '';
  if (typeof item.title === 'string') return item.title.trim();
  if (item.title?.text) return String(item.title.text).trim();
  const runs = item.title?.runs || [];
  if (Array.isArray(runs) && runs.length > 0) {
    return runs.map(run => run.text || '').join('').trim();
  }
  return '';
}

function decodeXmlEntities(value) {
  return String(value || '')
    .replace(/&amp;/g, '&')
    .replace(/&lt;/g, '<')
    .replace(/&gt;/g, '>')
    .replace(/&quot;/g, '"')
    .replace(/&#39;/g, "'");
}

function extractTitlesFromPlaylistFeed(xml) {
  const entries = xml.match(/<entry>[\s\S]*?<\/entry>/g) || [];
  return entries
    .map(entry => {
      const titleMatch = entry.match(/<title>([\s\S]*?)<\/title>/);
      if (!titleMatch) return '';
      return decodeXmlEntities(titleMatch[1]).trim();
    })
    .filter(Boolean);
}

function collectTitlesAndContinuations(node, titles, continuations) {
  if (!node) return;

  if (Array.isArray(node)) {
    node.forEach(item => collectTitlesAndContinuations(item, titles, continuations));
    return;
  }

  if (typeof node !== 'object') return;

  const videoRenderer = node.playlistVideoRenderer || node.videoRenderer || node.gridVideoRenderer;
  if (videoRenderer) {
    const title = getTitleText(videoRenderer);
    if (title) titles.push(title);
  }

  const continuationTokenCandidates = [
    node?.continuationEndpoint?.continuationCommand?.token,
    node?.continuationCommand?.token,
    node?.nextContinuationData?.continuation,
    node?.reloadContinuationData?.continuation,
    node?.commandExecutorCommand?.commands?.find(command => command?.continuationCommand?.token)?.continuationCommand?.token,
    typeof node?.continuation === 'string' ? node.continuation : '',
    typeof node?.token === 'string' ? node.token : ''
  ];
  continuationTokenCandidates
    .filter(token => typeof token === 'string' && token.length > 20)
    .forEach(token => continuations.push(token));

  Object.values(node).forEach(value => collectTitlesAndContinuations(value, titles, continuations));
}

function extractJsonBlob(text, marker) {
  const markerIndex = text.indexOf(marker);
  if (markerIndex === -1) return null;
  const startIndex = text.indexOf('{', markerIndex);
  if (startIndex === -1) return null;

  let depth = 0;
  let inString = false;
  let escaped = false;

  for (let i = startIndex; i < text.length; i++) {
    const char = text[i];
    if (inString) {
      if (escaped) {
        escaped = false;
      } else if (char === '\\') {
        escaped = true;
      } else if (char === '"') {
        inString = false;
      }
      continue;
    }

    if (char === '"') {
      inString = true;
      continue;
    }

    if (char === '{') depth++;
    if (char === '}') depth--;

    if (depth === 0) {
      return text.slice(startIndex, i + 1);
    }
  }

  return null;
}

async function getPlaylistTitlesFromWebBrowse(playlistId, limit) {
  const pageUrl = `https://www.youtube.com/playlist?list=${encodeURIComponent(playlistId)}`;
  const pageResponse = await fetch(pageUrl, { headers: { 'accept-language': 'en-US,en;q=0.9' } });
  if (!pageResponse.ok) return [];

  const html = await pageResponse.text();
  const initialDataText = extractJsonBlob(html, 'var ytInitialData = ');
  const ytcfgText = extractJsonBlob(html, 'ytcfg.set(');
  if (!initialDataText || !ytcfgText) return [];

  const initialData = JSON.parse(initialDataText);
  const ytcfg = JSON.parse(ytcfgText);
  const apiKey = ytcfg?.INNERTUBE_API_KEY;
  const context = ytcfg?.INNERTUBE_CONTEXT || {
    client: {
      clientName: 'WEB',
      clientVersion: ytcfg?.INNERTUBE_CONTEXT_CLIENT_VERSION || '2.20240101.00.00'
    }
  };

  if (!apiKey) return [];

  const titles = [];
  const continuations = [];
  const seenTokens = new Set();
  collectTitlesAndContinuations(initialData, titles, continuations);

  while (continuations.length > 0 && titles.length < limit) {
    const token = continuations.shift();
    if (!token || seenTokens.has(token)) continue;
    seenTokens.add(token);

    const response = await fetch(`https://www.youtube.com/youtubei/v1/browse?key=${apiKey}`, {
      method: 'POST',
      headers: { 'content-type': 'application/json' },
      body: JSON.stringify({ context, continuation: token })
    });
    if (!response.ok) continue;

    const data = await response.json();
    collectTitlesAndContinuations(data, titles, continuations);
    if (seenTokens.size > 250) break;
  }

  return Array.from(new Set(titles.map(title => title.trim()).filter(Boolean))).slice(0, limit);
}

async function getPlaylistTitles(playlistId, limit) {
  const youtube = await getYoutubeClient();
  let playlist = await youtube.getPlaylist(playlistId);
  const titles = [];

  while (playlist && titles.length < limit) {
    (playlist.items || []).forEach(item => {
      const title = getTitleText(item);
      if (title) titles.push(title);
    });
    if (!playlist.has_continuation) break;
    playlist = await playlist.getContinuation();
  }

  return titles.slice(0, limit);
}

async function getPlaylistTitlesFromInnertubeRaw(playlistId, limit) {
  const youtube = await getYoutubeClient();
  const titles = [];
  const continuations = [];
  const seenTokens = new Set();

  const initialData = await youtube.actions.execute('/browse', {
    browseId: `VL${playlistId}`,
    parse: false
  });
  collectTitlesAndContinuations(initialData, titles, continuations);

  while (continuations.length > 0 && titles.length < limit) {
    const token = continuations.shift();
    if (!token || seenTokens.has(token)) continue;
    seenTokens.add(token);

    const continuationData = await youtube.actions.execute('/browse', {
      continuation: token,
      parse: false
    });
    collectTitlesAndContinuations(continuationData, titles, continuations);
    if (seenTokens.size > 250) break;
  }

  return Array.from(new Set(titles.map(title => title.trim()).filter(Boolean))).slice(0, limit);
}

async function getPlaylistTitlesFromFeed(playlistId) {
  const feedUrl = `https://www.youtube.com/feeds/videos.xml?playlist_id=${encodeURIComponent(playlistId)}`;
  const response = await fetch(feedUrl);
  if (!response.ok) return [];
  const xml = await response.text();
  return extractTitlesFromPlaylistFeed(xml);
}

async function getPlaylistTitlesWithFallback(playlistId, limit) {
  try {
    const rawTitles = await getPlaylistTitlesFromInnertubeRaw(playlistId, limit);
    if (rawTitles.length > 0) {
      return { titles: rawTitles, source: 'innertube-raw', partial: false };
    }
  } catch (_error) {
  }

  try {
    const titles = await getPlaylistTitles(playlistId, limit);
    if (titles.length > 0) {
      return { titles, source: 'innertube', partial: false };
    }
  } catch (_error) {
  }

  try {
    const webTitles = await getPlaylistTitlesFromWebBrowse(playlistId, limit);
    if (webTitles.length > 0) {
      return { titles: webTitles, source: 'web-browse', partial: false };
    }
  } catch (_error) {
  }

  const feedTitles = await getPlaylistTitlesFromFeed(playlistId);
  if (feedTitles.length > 0) {
    return { titles: feedTitles, source: 'feed', partial: true };
  }

  throw new Error('Could not access playlist videos');
}

app.get('/api/tasks', async (_req, res) => {
  try {
    const db = await readDb();
    res.json(db.tasks || []);
  } catch (_error) {
    res.status(500).json({ message: 'Failed to load tasks' });
  }
});

app.post('/api/tasks', async (req, res) => {
  try {
    const { text, date = '', priority = 'medium', category = 'personal', completed = false } = req.body;
    if (!text || typeof text !== 'string') {
      return res.status(400).json({ message: 'Task text is required' });
    }

    const db = await readDb();
    const task = {
      id: Date.now().toString(),
      text: text.trim(),
      date,
      priority,
      category,
      completed: Boolean(completed)
    };

    db.tasks = [task, ...(db.tasks || [])];
    await writeDb(db);
    return res.status(201).json(task);
  } catch (_error) {
    return res.status(500).json({ message: 'Failed to create task' });
  }
});

app.patch('/api/tasks/:id', async (req, res) => {
  try {
    const db = await readDb();
    const index = (db.tasks || []).findIndex(task => task.id === req.params.id);
    if (index === -1) {
      return res.status(404).json({ message: 'Task not found' });
    }

    db.tasks[index] = { ...db.tasks[index], ...req.body, id: db.tasks[index].id };
    await writeDb(db);
    return res.json(db.tasks[index]);
  } catch (_error) {
    return res.status(500).json({ message: 'Failed to update task' });
  }
});

app.delete('/api/tasks/:id', async (req, res) => {
  try {
    const db = await readDb();
    const nextTasks = (db.tasks || []).filter(task => task.id !== req.params.id);
    db.tasks = nextTasks;
    await writeDb(db);
    return res.status(204).end();
  } catch (_error) {
    return res.status(500).json({ message: 'Failed to delete task' });
  }
});

app.delete('/api/tasks', async (_req, res) => {
  try {
    const db = await readDb();
    const deletedCount = (db.tasks || []).length;
    db.tasks = [];
    await writeDb(db);
    return res.json({ deletedCount });
  } catch (_error) {
    return res.status(500).json({ message: 'Failed to delete all tasks' });
  }
});

app.post('/api/tasks/bulk-delete', async (req, res) => {
  try {
    const { ids } = req.body;
    if (!Array.isArray(ids) || ids.length === 0) {
      return res.status(400).json({ message: 'Task ids are required' });
    }

    const idsSet = new Set(ids.map(id => String(id)));
    const db = await readDb();
    const currentTasks = db.tasks || [];
    const nextTasks = currentTasks.filter(task => !idsSet.has(task.id));
    const deletedCount = currentTasks.length - nextTasks.length;
    db.tasks = nextTasks;
    await writeDb(db);
    return res.json({ deletedCount });
  } catch (_error) {
    return res.status(500).json({ message: 'Failed to delete selected tasks' });
  }
});

app.post('/api/import/youtube-playlist', async (req, res) => {
  try {
    const { url, priority, category, date = '', maxVideos } = req.body;
    const importLimit = resolveImportLimit(maxVideos);
    const playlistId = parsePlaylistId(url);
    if (!playlistId) {
      return res.status(400).json({ message: 'Valid YouTube playlist URL is required' });
    }
    if (!priority || !String(priority).trim()) {
      return res.status(400).json({ message: 'Priority is required for playlist import' });
    }
    if (!category || !String(category).trim()) {
      return res.status(400).json({ message: 'Type is required for playlist import' });
    }

    const { titles, partial, source } = await getPlaylistTitlesWithFallback(playlistId, importLimit);
    if (titles.length === 0) {
      return res.status(404).json({ message: 'No videos found in playlist' });
    }

    const db = await readDb();
    const existingTexts = new Set((db.tasks || []).map(task => normalizeText(task.text)));
    const now = Date.now();
    const importedTasks = [];

    titles.forEach((title, index) => {
      const normalized = normalizeText(title);
      if (!normalized || existingTexts.has(normalized)) return;
      existingTexts.add(normalized);
      importedTasks.push({
        id: `${now}-${index}`,
        text: title.trim(),
        date: String(date || ''),
        priority,
        category,
        completed: false
      });
    });

    db.tasks = [...importedTasks, ...(db.tasks || [])];
    await writeDb(db);
    return res.status(201).json({
      importedCount: importedTasks.length,
      tasks: importedTasks,
      requestedLimit: importLimit,
      partial,
      source,
      message: partial ? 'Imported available videos. Some playlists may provide only recent items.' : 'Playlist imported successfully'
    });
  } catch (error) {
    return res.status(500).json({ message: error?.message || 'Failed to import playlist' });
  }
});

app.listen(port, () => {
  console.log(`Todo app running on http://localhost:${port}`);
});
