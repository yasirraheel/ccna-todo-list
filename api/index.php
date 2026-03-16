<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function loadEnvFile(string $filePath): void {
    if (!is_file($filePath)) {
        return;
    }

    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $key = trim($key);
        $value = trim($value);
        if ($value !== '' && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }

        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function envValue(string $key, string $default = ''): string {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return (string) $value;
}

function jsonResponse(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function getRawBody(): string {
    $body = file_get_contents('php://input');
    return $body === false ? '' : $body;
}

function getJsonBody(): array {
    $rawBody = getRawBody();
    if ($rawBody === '') {
        return [];
    }
    $decoded = json_decode($rawBody, true);
    return is_array($decoded) ? $decoded : [];
}

function normalizeText(string $value): string {
    $trimmed = trim($value);
    return mb_strtolower($trimmed, 'UTF-8');
}

function parsePlaylistId(string $value): string {
    $trimmed = trim($value);
    if ($trimmed === '') {
        return '';
    }

    if (preg_match('/^[\w-]+$/', $trimmed)) {
        return $trimmed;
    }

    $parts = parse_url($trimmed);
    if (!is_array($parts) || !isset($parts['query'])) {
        return '';
    }

    parse_str($parts['query'], $query);
    $list = isset($query['list']) ? (string) $query['list'] : '';
    return preg_match('/^[\w-]+$/', $list) ? $list : '';
}

function extractPlaylistTitlesFromFeed(string $playlistId): array {
    $feedUrl = 'https://www.youtube.com/feeds/videos.xml?playlist_id=' . urlencode($playlistId);
    $context = stream_context_create([
        'http' => [
            'timeout' => 20,
            'ignore_errors' => true
        ]
    ]);
    $xml = @file_get_contents($feedUrl, false, $context);
    if (!is_string($xml) || $xml === '') {
        return [];
    }

    preg_match_all('/<entry>[\s\S]*?<\/entry>/', $xml, $entries);
    $titles = [];
    foreach ($entries[0] ?? [] as $entry) {
        if (preg_match('/<title>([\s\S]*?)<\/title>/', $entry, $match)) {
            $title = trim(html_entity_decode($match[1], ENT_QUOTES | ENT_XML1, 'UTF-8'));
            if ($title !== '') {
                $titles[] = $title;
            }
        }
    }

    return array_values(array_unique($titles));
}

function extractPlaylistTitlesFromYouTubeApi(string $playlistId, int $limit, string $apiKey): array {
    if ($apiKey === '') {
        return [];
    }

    $titles = [];
    $pageToken = '';

    while (count($titles) < $limit) {
        $query = http_build_query([
            'part' => 'snippet',
            'playlistId' => $playlistId,
            'maxResults' => 50,
            'pageToken' => $pageToken,
            'key' => $apiKey
        ]);
        $url = 'https://www.googleapis.com/youtube/v3/playlistItems?' . $query;
        $context = stream_context_create([
            'http' => [
                'timeout' => 20,
                'ignore_errors' => true
            ]
        ]);
        $raw = @file_get_contents($url, false, $context);
        if (!is_string($raw) || $raw === '') {
            break;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['items']) || !is_array($decoded['items'])) {
            break;
        }

        foreach ($decoded['items'] as $item) {
            $title = (string) (($item['snippet']['title'] ?? ''));
            $title = trim($title);
            if ($title !== '' && $title !== 'Private video' && $title !== 'Deleted video') {
                $titles[] = $title;
            }
            if (count($titles) >= $limit) {
                break;
            }
        }

        $nextPageToken = (string) ($decoded['nextPageToken'] ?? '');
        if ($nextPageToken === '') {
            break;
        }
        $pageToken = $nextPageToken;
    }

    return array_values(array_unique($titles));
}

function getApiPathSegments(): array {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = is_string($uri) ? trim($uri, '/') : '';
    if ($path === '') {
        return [];
    }

    $parts = array_values(array_filter(explode('/', $path), fn($part) => $part !== ''));
    $apiIndex = array_search('api', $parts, true);
    if ($apiIndex === false) {
        return [];
    }

    return array_slice($parts, $apiIndex + 1);
}

function readDb(string $dbPath): array {
    if (!is_file($dbPath)) {
        return ['tasks' => []];
    }

    $raw = file_get_contents($dbPath);
    if (!is_string($raw) || trim($raw) === '') {
        return ['tasks' => []];
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return ['tasks' => []];
    }

    if (!isset($decoded['tasks']) || !is_array($decoded['tasks'])) {
        $decoded['tasks'] = [];
    }

    return $decoded;
}

function writeDb(string $dbPath, array $db): void {
    $encoded = json_encode($db, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents($dbPath, $encoded . PHP_EOL, LOCK_EX);
}

function resolveImportLimit($value): int {
    $default = 300;
    $max = 500;
    if ($value === null || $value === '') {
        return $default;
    }
    $parsed = (int) $value;
    if ($parsed < 1) {
        return $default;
    }
    return min($parsed, $max);
}

$projectRoot = dirname(__DIR__);
loadEnvFile($projectRoot . DIRECTORY_SEPARATOR . '.env');
$dbPath = $projectRoot . DIRECTORY_SEPARATOR . 'db.json';
$publicApiBase = rtrim(envValue('PUBLIC_API_BASE_URL', ''), '/');
$youtubeApiKey = trim(envValue('YOUTUBE_API_KEY', ''));

$segments = getApiPathSegments();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$body = getJsonBody();

if (count($segments) === 1 && $segments[0] === 'config' && $method === 'GET') {
    jsonResponse(200, [
        'apiBaseUrl' => $publicApiBase,
        'runtime' => 'php'
    ]);
}

if (count($segments) === 1 && $segments[0] === 'tasks' && $method === 'GET') {
    $db = readDb($dbPath);
    jsonResponse(200, $db['tasks']);
}

if (count($segments) === 1 && $segments[0] === 'tasks' && $method === 'POST') {
    $text = trim((string) ($body['text'] ?? ''));
    if ($text === '') {
        jsonResponse(400, ['message' => 'Task text is required']);
    }

    $db = readDb($dbPath);
    $task = [
        'id' => (string) round(microtime(true) * 1000),
        'text' => $text,
        'date' => (string) ($body['date'] ?? ''),
        'priority' => (string) ($body['priority'] ?? 'medium'),
        'category' => (string) ($body['category'] ?? 'personal'),
        'completed' => (bool) ($body['completed'] ?? false)
    ];
    array_unshift($db['tasks'], $task);
    writeDb($dbPath, $db);
    jsonResponse(201, $task);
}

if (count($segments) === 1 && $segments[0] === 'tasks' && $method === 'DELETE') {
    $db = readDb($dbPath);
    $deletedCount = count($db['tasks']);
    $db['tasks'] = [];
    writeDb($dbPath, $db);
    jsonResponse(200, ['deletedCount' => $deletedCount]);
}

if (count($segments) === 2 && $segments[0] === 'tasks' && $segments[1] === 'bulk-delete' && $method === 'POST') {
    $ids = $body['ids'] ?? null;
    if (!is_array($ids) || count($ids) === 0) {
        jsonResponse(400, ['message' => 'Task ids are required']);
    }

    $idSet = [];
    foreach ($ids as $id) {
        $idSet[(string) $id] = true;
    }

    $db = readDb($dbPath);
    $before = count($db['tasks']);
    $db['tasks'] = array_values(array_filter($db['tasks'], function ($task) use ($idSet) {
        $taskId = (string) ($task['id'] ?? '');
        return !isset($idSet[$taskId]);
    }));
    writeDb($dbPath, $db);
    jsonResponse(200, ['deletedCount' => $before - count($db['tasks'])]);
}

if (count($segments) === 2 && $segments[0] === 'tasks') {
    $taskId = (string) $segments[1];
    $db = readDb($dbPath);
    $index = -1;
    foreach ($db['tasks'] as $i => $task) {
        if ((string) ($task['id'] ?? '') === $taskId) {
            $index = $i;
            break;
        }
    }

    if ($index === -1) {
        jsonResponse(404, ['message' => 'Task not found']);
    }

    if ($method === 'PATCH') {
        $current = $db['tasks'][$index];
        $next = array_merge($current, $body);
        $next['id'] = $current['id'];
        $db['tasks'][$index] = $next;
        writeDb($dbPath, $db);
        jsonResponse(200, $next);
    }

    if ($method === 'DELETE') {
        array_splice($db['tasks'], $index, 1);
        writeDb($dbPath, $db);
        http_response_code(204);
        exit;
    }
}

if (count($segments) === 2 && $segments[0] === 'import' && $segments[1] === 'youtube-playlist' && $method === 'POST') {
    $playlistUrl = (string) ($body['url'] ?? '');
    $priority = trim((string) ($body['priority'] ?? ''));
    $category = trim((string) ($body['category'] ?? ''));
    $date = (string) ($body['date'] ?? '');
    $importLimit = resolveImportLimit($body['maxVideos'] ?? null);

    $playlistId = parsePlaylistId($playlistUrl);
    if ($playlistId === '') {
        jsonResponse(400, ['message' => 'Valid YouTube playlist URL is required']);
    }
    if ($priority === '') {
        jsonResponse(400, ['message' => 'Priority is required for playlist import']);
    }
    if ($category === '') {
        jsonResponse(400, ['message' => 'Type is required for playlist import']);
    }

    $titles = extractPlaylistTitlesFromYouTubeApi($playlistId, $importLimit, $youtubeApiKey);
    $source = 'youtube-data-api';
    $partial = false;

    if (count($titles) === 0) {
        $titles = extractPlaylistTitlesFromFeed($playlistId);
        $source = 'feed';
        $partial = true;
    }

    if (count($titles) === 0) {
        jsonResponse(404, ['message' => 'No videos found in playlist']);
    }

    $db = readDb($dbPath);
    $existing = [];
    foreach ($db['tasks'] as $task) {
        $existing[normalizeText((string) ($task['text'] ?? ''))] = true;
    }

    $now = (string) round(microtime(true) * 1000);
    $imported = [];
    $index = 0;
    foreach ($titles as $title) {
        $normalized = normalizeText($title);
        if ($normalized === '' || isset($existing[$normalized])) {
            continue;
        }
        $existing[$normalized] = true;
        $imported[] = [
            'id' => $now . '-' . $index,
            'text' => trim($title),
            'date' => $date,
            'priority' => $priority,
            'category' => $category,
            'completed' => false
        ];
        $index++;
    }

    $db['tasks'] = array_values(array_merge($imported, $db['tasks']));
    writeDb($dbPath, $db);
    jsonResponse(201, [
        'importedCount' => count($imported),
        'tasks' => $imported,
        'requestedLimit' => $importLimit,
        'partial' => $partial,
        'source' => $source,
        'message' => $partial ? 'Imported available videos. Add YOUTUBE_API_KEY in .env for full playlist pagination.' : 'Playlist imported successfully'
    ]);
}

jsonResponse(404, ['message' => 'Not found']);
