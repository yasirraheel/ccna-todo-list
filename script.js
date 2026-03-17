document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('todo-form');
  const taskInput = document.getElementById('task-input');
  const taskDate = document.getElementById('task-date');
  const taskPriority = document.getElementById('task-priority');
  const taskCategory = document.getElementById('task-category');
  const taskVisibility = document.getElementById('task-visibility');
  const taskList = document.getElementById('task-list');
  const filterBtns = document.querySelectorAll('.filter-btn');
  const dateDisplay = document.getElementById('current-date');
  const playlistUrlInput = document.getElementById('playlist-url');
  const playlistPriorityInput = document.getElementById('playlist-priority');
  const playlistTypeInput = document.getElementById('playlist-type');
  const playlistVisibilityInput = document.getElementById('playlist-visibility');
  const playlistDateInput = document.getElementById('playlist-date');
  const playlistNameInput = document.getElementById('playlist-name');
  const importPlaylistBtn = document.getElementById('import-playlist-btn');
  const playlistStatus = document.getElementById('playlist-status');
  const importBtnIdleLabel = importPlaylistBtn ? importPlaylistBtn.textContent.trim() : 'Import Playlist';
  const playlistFilterSelect = document.getElementById('playlist-filter');
  const scopeFilterSelect = document.getElementById('scope-filter');
  const playlistVisibilityBtn = document.getElementById('playlist-visibility-btn');
  const playlistRenameBtn = document.getElementById('playlist-rename-btn');
  const playlistDeleteBtn = document.getElementById('playlist-delete-btn');
  const selectAllTasksInput = document.getElementById('select-all-tasks');
  const deleteSelectedBtn = document.getElementById('delete-selected-btn');
  const deleteAllBtn = document.getElementById('delete-all-btn');
  const authPanel = document.getElementById('auth-panel');
  const appContainer = document.getElementById('app-container');
  const authStatus = document.getElementById('auth-status');
  const authNameInput = document.getElementById('auth-name');
  const authEmailInput = document.getElementById('auth-email');
  const authPasswordInput = document.getElementById('auth-password');
  const authLoginBtn = document.getElementById('auth-login-btn');
  const authRegisterBtn = document.getElementById('auth-register-btn');
  const logoutBtn = document.getElementById('logout-btn');
  const sessionEmail = document.getElementById('session-email');
  const appTitle = document.getElementById('app-home-link');
  const footerTitle = document.getElementById('footer-title');
  const footerDescription = document.getElementById('footer-description');
  const footerYear = document.getElementById('footer-year');
  const isLoginPage = window.location.pathname.toLowerCase().endsWith('/login.html') || document.body.dataset.page === 'login';
  const scrollTopBtn = document.getElementById('scroll-top-btn');

  // Modal elements
  const customModal = document.getElementById('custom-modal');
  const modalTitle = document.getElementById('modal-title');
  const modalMessage = document.getElementById('modal-message');
  const modalInput = document.getElementById('modal-input');
  const modalCancel = document.getElementById('modal-cancel');
  const modalConfirm = document.getElementById('modal-confirm');
  const flashStack = document.getElementById('flash-stack');

  const statTotalEl = document.getElementById('stat-total');
  const statCompletedEl = document.getElementById('stat-completed');
  const statPendingEl = document.getElementById('stat-pending');
  const statPlaylistEl = document.getElementById('stat-playlist');
  const statProgressFill = document.getElementById('stat-progress-fill');
  const statProgressLabel = document.getElementById('stat-progress-label');

  const browserHost = window.location.hostname || 'localhost';
  const isLocalNetworkHost =
    browserHost === 'localhost' ||
    browserHost === '127.0.0.1' ||
    browserHost.startsWith('192.168.') ||
    browserHost.startsWith('10.') ||
    browserHost.startsWith('172.');
  let API_BASE = '/api/tasks';
  let PUBLIC_TASKS_API = '/api/tasks/public';
  let IMPORT_API = '/api/import/youtube-playlist';
  let BULK_DELETE_API = '/api/tasks/bulk-delete';
  let AUTH_LOGIN_API = '/api/auth/login';
  let AUTH_REGISTER_API = '/api/auth/register';
  let AUTH_ME_API = '/api/auth/me';
  let PLAYLISTS_API = '/api/playlists';
  let PREFERENCES_API = '/api/preferences';
  let PLAYLIST_VISIBILITY_API = '/api/playlists/visibility';
  let PLAYLIST_RENAME_API = '/api/playlists/rename';
  let PLAYLIST_DELETE_API = '/api/playlists/delete';
  const IMPORT_LIMIT = 300;
  const AUTH_TOKEN_KEY = 'todo_auth_token';
  const SELECTED_PLAYLIST_KEY = 'todo_selected_playlist';
  const SELECTED_PUBLIC_PLAYLIST_KEY = 'todo_selected_public_playlist';
  const TASK_SCOPE_KEY = 'todo_task_scope';
  let tasks = [];
  let currentFilter = 'all';
  const selectedTaskIds = new Set();
  let authToken = localStorage.getItem(AUTH_TOKEN_KEY) || '';
  let currentPlaylistFilter = 'all';
  let currentScope = localStorage.getItem(TASK_SCOPE_KEY) || 'my';
  let currentUserId = null;
  let appName = 'My Tasks';

  const options = { weekday: 'long', month: 'short', day: 'numeric' };
  if (dateDisplay) {
    dateDisplay.textContent = new Date().toLocaleDateString('en-US', options);
  }
  if (footerYear) footerYear.textContent = String(new Date().getFullYear());
  applySeoConfig({ appName });

  init();

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      addTask().catch(() => {});
    });
  }

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentFilter = btn.dataset.filter;
      renderTasks();
      showFlash(`Showing ${currentFilter} tasks`, 'info');
    });
  });

  if (importPlaylistBtn) {
    importPlaylistBtn.addEventListener('click', () => {
      importPlaylist().catch(() => {});
    });
  }

  if (selectAllTasksInput) {
    selectAllTasksInput.addEventListener('change', () => {
      const filteredTasks = getFilteredTasks();
      if (selectAllTasksInput.checked) {
        filteredTasks.forEach(task => selectedTaskIds.add(task.id));
      } else {
        filteredTasks.forEach(task => selectedTaskIds.delete(task.id));
      }
      renderTasks();
      showFlash(selectAllTasksInput.checked ? 'Selected visible tasks' : 'Selection cleared', 'info');
    });
  }

  if (deleteSelectedBtn) {
    deleteSelectedBtn.addEventListener('click', () => {
      deleteSelectedTasks().catch(() => {});
    });
  }

  if (deleteAllBtn) {
    deleteAllBtn.addEventListener('click', () => {
      deleteAllTasks().catch(() => {});
    });
  }

  if (playlistFilterSelect) {
    playlistFilterSelect.addEventListener('change', async () => {
      currentPlaylistFilter = playlistFilterSelect.value || 'all';
      await saveSelectedPlaylistPreference(currentPlaylistFilter);
      await loadTasks();
      renderTasks();
      updatePlaylistActionState();
      const label = currentPlaylistFilter === 'all' ? 'All Playlists' : currentPlaylistFilter;
      showFlash(`Playlist selected: ${label}`, 'info');
    });
  }

  if (scopeFilterSelect) {
    scopeFilterSelect.value = currentScope === 'public' ? 'public' : 'my';
    scopeFilterSelect.addEventListener('change', async () => {
      currentScope = scopeFilterSelect.value === 'public' ? 'public' : 'my';
      localStorage.setItem(TASK_SCOPE_KEY, currentScope);
      selectedTaskIds.clear();
      currentPlaylistFilter = 'all';
      if (playlistFilterSelect) playlistFilterSelect.value = 'all';
      await loadPlaylists();
      await loadSelectedPlaylistPreference();
      await loadTasks();
      renderTasks();
      updatePlaylistActionState();
      showFlash(currentScope === 'public' ? 'Showing public tasks' : 'Showing your tasks', 'info');
    });
  }

  if (playlistRenameBtn) {
    playlistRenameBtn.addEventListener('click', async () => {
      if (!currentPlaylistFilter || currentPlaylistFilter === 'all') return;
      const next = await showCustomPrompt(
        'Rename Playlist',
        'Enter a new playlist name',
        currentPlaylistFilter,
        { confirmText: 'Save' }
      );
      const toName = String(next || '').trim();
      if (!toName) return;
      const body = { fromName: currentPlaylistFilter, toName };
      const r = await apiFetch(PLAYLIST_RENAME_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(body)
      });
      if (!r.ok) {
        showFlash(await readResponseMessage(r, 'Playlist rename failed'), 'error');
        return;
      }
      await loadPlaylists();
      currentPlaylistFilter = toName;
      if (playlistFilterSelect) playlistFilterSelect.value = currentPlaylistFilter;
      await saveSelectedPlaylistPreference(currentPlaylistFilter);
      await loadTasks();
      renderTasks();
      updatePlaylistActionState();
      showFlash(`Playlist renamed to ${toName}`, 'success');
    });
  }

  if (playlistVisibilityBtn) {
    playlistVisibilityBtn.addEventListener('click', async () => {
      if (!currentPlaylistFilter || currentPlaylistFilter === 'all' || currentScope === 'public') return;
      const playlistTasks = tasks.filter(t => String(t.playlistName || '') === currentPlaylistFilter);
      const currentVisibility = playlistTasks.some(t => String(t.visibility || '') === 'public') ? 'public' : 'private';
      const nextVisibility = currentVisibility === 'public' ? 'private' : 'public';
      const confirmed = await showCustomConfirm(
        'Change Playlist Visibility',
        `Set "${currentPlaylistFilter}" to ${nextVisibility}?`,
        { confirmText: `Set ${nextVisibility}`, confirmVariant: 'primary' }
      );
      if (!confirmed) return;
      const r = await apiFetch(PLAYLIST_VISIBILITY_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: currentPlaylistFilter, visibility: nextVisibility })
      });
      if (!r.ok) {
        showFlash(await readResponseMessage(r, 'Visibility update failed'), 'error');
        return;
      }
      await loadTasks();
      renderTasks();
      updatePlaylistActionState();
      showFlash(`Playlist visibility set to ${nextVisibility}`, 'success');
    });
  }

  if (playlistDeleteBtn) {
    playlistDeleteBtn.addEventListener('click', async () => {
      if (!currentPlaylistFilter || currentPlaylistFilter === 'all') return;
      
      const confirmed = await showCustomConfirm(
        'Delete Playlist',
        `Are you sure you want to delete "${currentPlaylistFilter}"? All tasks in this playlist will be moved to "Unassigned".`,
        { confirmText: 'Delete', confirmVariant: 'danger' }
      );
      
      if (!confirmed) return;
      
      const r = await apiFetch(PLAYLIST_DELETE_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name: currentPlaylistFilter })
      });
      if (!r.ok) {
        showFlash(await readResponseMessage(r, 'Playlist delete failed'), 'error');
        return;
      }
      await loadPlaylists();
      currentPlaylistFilter = 'all';
      if (playlistFilterSelect) playlistFilterSelect.value = 'all';
      await saveSelectedPlaylistPreference(currentPlaylistFilter);
      await loadTasks();
      renderTasks();
      updatePlaylistActionState();
      showFlash('Playlist cleared to Unassigned', 'success');
    });
  }
  if (authLoginBtn) {
    authLoginBtn.addEventListener('click', () => {
      loginUser().catch(() => {});
    });
  }

  if (authRegisterBtn) {
    authRegisterBtn.addEventListener('click', () => {
      registerUser().catch(() => {});
    });
  }

  if (logoutBtn) {
    logoutBtn.addEventListener('click', () => {
      logoutUser();
    });
  }

  function buildApiBase(originOrPath) {
    const value = String(originOrPath || '').trim().replace(/\/+$/, '');
    if (!value) return '/api/tasks';
    if (value.endsWith('/api/tasks')) return value;
    if (value.endsWith('/api')) return `${value}/tasks`;
    return `${value}/api/tasks`;
  }

  function updateApiEndpoints(base) {
    API_BASE = buildApiBase(base);
    const apiRoot = API_BASE.replace(/\/tasks$/, '');
    PUBLIC_TASKS_API = `${API_BASE}/public`;
    IMPORT_API = `${apiRoot}/import/youtube-playlist`;
    BULK_DELETE_API = `${API_BASE}/bulk-delete`;
    AUTH_LOGIN_API = `${apiRoot}/auth/login`;
    AUTH_REGISTER_API = `${apiRoot}/auth/register`;
    AUTH_ME_API = `${apiRoot}/auth/me`;
    PLAYLISTS_API = `${apiRoot}/playlists`;
    PREFERENCES_API = `${apiRoot}/preferences`;
    PLAYLIST_VISIBILITY_API = `${apiRoot}/playlists/visibility`;
    PLAYLIST_RENAME_API = `${apiRoot}/playlists/rename`;
    PLAYLIST_DELETE_API = `${apiRoot}/playlists/delete`;
  }

  async function fetchWithTimeout(url, timeoutMs = 2500) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeoutMs);
    try {
      return await fetch(url, { signal: controller.signal });
    } finally {
      clearTimeout(timer);
    }
  }

  function getApiBaseFromMeta() {
    const metaTag = document.querySelector('meta[name="todo-api-base"]');
    if (!metaTag) return '';
    return String(metaTag.getAttribute('content') || '').trim();
  }

  async function resolveApiBase() {
    if (window.location.port === '3000') {
      updateApiEndpoints(window.location.origin);
      return;
    }

    if (window.__TODO_API_BASE) {
      updateApiEndpoints(window.__TODO_API_BASE);
      return;
    }

    const metaApiBase = getApiBaseFromMeta();
    if (metaApiBase) {
      updateApiEndpoints(metaApiBase);
      return;
    }

    const candidates = [window.location.origin];
    if (isLocalNetworkHost) {
      candidates.push(`http://${browserHost}:3000`, 'http://localhost:3000', 'http://127.0.0.1:3000');
    }

    const uniqueCandidates = Array.from(new Set(candidates));

    for (const origin of uniqueCandidates) {
      try {
        const configResponse = await fetchWithTimeout(`${origin}/api/config`);
        if (configResponse.ok) {
          const config = await configResponse.json();
          applySeoConfig(config || {});
          if (config?.apiBaseUrl) {
            updateApiEndpoints(config.apiBaseUrl);
            return;
          }
          updateApiEndpoints(origin);
          return;
        }
      } catch (_error) {
      }
    }

    if (isLocalNetworkHost) {
      updateApiEndpoints(`http://${browserHost}:3000`);
      return;
    }

    updateApiEndpoints(window.location.origin);
  }

  function setAuthToken(token) {
    authToken = String(token || '').trim();
    if (authToken) {
      localStorage.setItem(AUTH_TOKEN_KEY, authToken);
    } else {
      localStorage.removeItem(AUTH_TOKEN_KEY);
    }
  }

  function setDefaultPublicScope() {
    currentScope = 'public';
    localStorage.setItem(TASK_SCOPE_KEY, 'public');
    currentPlaylistFilter = 'all';
    selectedTaskIds.clear();
    if (scopeFilterSelect) scopeFilterSelect.value = 'public';
    if (playlistFilterSelect) playlistFilterSelect.value = 'all';
  }

  function redirectToLogin() {
    if (!isLoginPage) {
      window.location.href = 'login.html';
    }
  }

  function redirectToApp() {
    if (isLoginPage) {
      window.location.href = '/';
    }
  }

  function showAuthPanel(message = '') {
    if (authPanel) authPanel.classList.remove('app-hidden');
    if (appContainer) appContainer.classList.add('app-hidden');
    if (authStatus) authStatus.textContent = message;
  }

  function showAppPanel(user) {
    if (authPanel) authPanel.classList.add('app-hidden');
    if (appContainer) appContainer.classList.remove('app-hidden');
    if (sessionEmail) sessionEmail.textContent = user?.email || '';
    currentUserId = Number(user?.id || 0) || null;
    if (authStatus) authStatus.textContent = '';
  }

  async function apiFetch(url, options = {}) {
    const headers = { ...(options.headers || {}) };
    if (authToken) {
      headers.Authorization = `Bearer ${authToken}`;
    }
    return fetch(url, { ...options, headers });
  }

  function applyAppName(value) {
    const next = String(value || '').trim();
    if (!next) return;
    appName = next;
    if (appTitle) appTitle.textContent = next;
    if (footerTitle) footerTitle.textContent = `© ${new Date().getFullYear()} ${next}`;
    document.title = next;
  }

  function setMetaContent(selector, value) {
    const text = String(value || '').trim();
    const el = document.querySelector(selector);
    if (!el || !text) return;
    el.setAttribute('content', text);
  }

  function setCanonicalUrl(value) {
    const url = String(value || '').trim() || window.location.href;
    const canonical = document.querySelector('link[rel="canonical"]');
    if (!canonical) return;
    canonical.setAttribute('href', url);
  }

  function applySeoConfig(config = {}) {
    const title = String(config?.appName || appName || '').trim();
    if (title) {
      applyAppName(title);
      setMetaContent('meta[property="og:site_name"]', title);
      setMetaContent('meta[property="og:title"]', title);
      setMetaContent('meta[name="twitter:title"]', title);
    }
    const description = String(config?.appDescription || '').trim();
    if (description) {
      setMetaContent('meta[name="description"]', description);
      setMetaContent('meta[property="og:description"]', description);
      setMetaContent('meta[name="twitter:description"]', description);
      if (footerDescription) footerDescription.textContent = description;
    }
    const ogImage = String(config?.appOgImageUrl || '').trim();
    if (ogImage) {
      setMetaContent('meta[property="og:image"]', ogImage);
      setMetaContent('meta[name="twitter:image"]', ogImage);
    }
    const canonicalUrl = String(config?.appCanonicalUrl || '').trim();
    setCanonicalUrl(canonicalUrl);
    setMetaContent('meta[property="og:url"]', canonicalUrl || window.location.href);
  }

  async function readResponseMessage(response, fallback) {
    try {
      const data = await response.clone().json();
      return String(data?.message || fallback || 'Request failed');
    } catch (_error) {
      return String(fallback || 'Request failed');
    }
  }

  function showFlash(message, type = 'info') {
    const text = String(message || '').trim();
    if (!flashStack || !text) return;
    const tone = type === 'error' ? 'error' : (type === 'success' ? 'success' : 'info');
    const icon = tone === 'error' ? 'fa-circle-exclamation' : (tone === 'success' ? 'fa-circle-check' : 'fa-circle-info');
    const item = document.createElement('div');
    item.className = `flash-item ${tone}`;
    item.innerHTML = `<i class="fas ${icon}"></i><span>${text}</span>`;
    flashStack.appendChild(item);
    setTimeout(() => {
      item.remove();
    }, 2800);
  }

  function setPlaylistStatus(message, type = 'info') {
    if (!playlistStatus) return;
    const text = String(message || '').trim();
    playlistStatus.classList.remove('show', 'success', 'error');
    playlistStatus.textContent = '';
    if (!text) return;
    playlistStatus.textContent = text;
    playlistStatus.classList.add('show');
    if (type === 'success') playlistStatus.classList.add('success');
    if (type === 'error') playlistStatus.classList.add('error');
  }

  async function authenticateWithStoredToken() {
    if (!authToken) {
      if (isLoginPage) {
        showAuthPanel();
      } else {
        redirectToLogin();
      }
      return false;
    }
    try {
      const response = await apiFetch(AUTH_ME_API);
      if (!response.ok) {
        setAuthToken('');
        if (isLoginPage) {
          showAuthPanel('Please login to continue');
        } else {
          redirectToLogin();
        }
        return false;
      }
      const data = await response.json();
      showAppPanel(data.user || {});
      if (isLoginPage) {
        redirectToApp();
      }
      return true;
    } catch (_error) {
      if (isLoginPage) {
        showAuthPanel('Could not connect to auth service');
      }
      return false;
    }
  }

  async function loginUser() {
    if (!authEmailInput || !authPasswordInput) return;
    const email = authEmailInput.value.trim();
    const password = authPasswordInput.value;
    if (!email || !password) {
      if (authStatus) authStatus.textContent = 'Email and password are required';
      return;
    }
    if (authStatus) authStatus.textContent = 'Signing in...';
    const response = await fetch(AUTH_LOGIN_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });
    const data = await response.json();
    if (!response.ok) {
      if (authStatus) authStatus.textContent = data.message || 'Login failed';
      showFlash(data.message || 'Login failed', 'error');
      return;
    }
    setAuthToken(data.token || '');
    setDefaultPublicScope();
    showAppPanel(data.user || {});
    redirectToApp();
    if (isLoginPage) return;
    await loadPlaylists();
    await loadTasks();
    renderTasks();
    showFlash('Logged in successfully', 'success');
  }

  async function registerUser() {
    if (!authNameInput || !authEmailInput || !authPasswordInput) return;
    const name = authNameInput.value.trim();
    const email = authEmailInput.value.trim();
    const password = authPasswordInput.value;
    if (!name || !email || !password) {
      if (authStatus) authStatus.textContent = 'Name, email and password are required';
      return;
    }
    if (authStatus) authStatus.textContent = 'Creating account...';
    const response = await fetch(AUTH_REGISTER_API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, email, password })
    });
    const data = await response.json();
    if (!response.ok) {
      if (authStatus) authStatus.textContent = data.message || 'Register failed';
      showFlash(data.message || 'Register failed', 'error');
      return;
    }
    setAuthToken(data.token || '');
    setDefaultPublicScope();
    showAppPanel(data.user || {});
    redirectToApp();
    if (isLoginPage) return;
    await loadPlaylists();
    await loadTasks();
    renderTasks();
    showFlash('Account created successfully', 'success');
  }

  function logoutUser() {
    setAuthToken('');
    tasks = [];
    selectedTaskIds.clear();
    renderTasks();
    if (isLoginPage) {
      showAuthPanel('Logged out');
    } else {
      redirectToLogin();
    }
    showFlash('Logged out', 'info');
  }

  async function init() {
    await resolveApiBase();
    const authed = await authenticateWithStoredToken();
    if (!authed) return;
    if (isLoginPage) return;
    await loadPlaylists();
    await loadSelectedPlaylistPreference();
    await loadTasks();
    renderTasks();
    if (scrollTopBtn) {
      const onScroll = () => {
        if (window.scrollY > 300) {
          scrollTopBtn.classList.add('visible');
        } else {
          scrollTopBtn.classList.remove('visible');
        }
      };
      window.addEventListener('scroll', onScroll, { passive: true });
      scrollTopBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });
      onScroll();
    }
  }

  async function loadTasks() {
    try {
      const q = currentPlaylistFilter && currentPlaylistFilter !== 'all' ? `?playlist=${encodeURIComponent(currentPlaylistFilter)}` : '';
      const base = currentScope === 'public' ? PUBLIC_TASKS_API : API_BASE;
      const response = await apiFetch(`${base}${q}`);
      if (!response.ok) {
        showFlash(await readResponseMessage(response, 'Could not load tasks'), 'error');
        return;
      }
      tasks = await response.json();
      if (currentScope === 'public') {
        selectedTaskIds.clear();
      }
      updateDashboard();
    } catch (_error) {
      tasks = [];
      showFlash('Could not connect to task service', 'error');
    }
  }

  async function loadPlaylists() {
    if (!playlistFilterSelect) return;
    try {
      const response = await apiFetch(currentScope === 'public' ? PUBLIC_TASKS_API : PLAYLISTS_API);
      if (!response.ok) return;
      const items = await response.json();
      const prev = currentPlaylistFilter;
      playlistFilterSelect.innerHTML = '';
      const optAll = document.createElement('option');
      optAll.value = 'all';
      optAll.textContent = 'All Playlists';
      playlistFilterSelect.appendChild(optAll);
      if (currentScope === 'public') {
        const map = new Map();
        (items || []).forEach(task => {
          const name = String(task?.playlistName || '');
          map.set(name, (map.get(name) || 0) + 1);
        });
        Array.from(map.entries()).forEach(([name, count]) => {
          const label = name || 'Unassigned';
          const option = document.createElement('option');
          option.value = name;
          option.textContent = `${label} (${count})`;
          playlistFilterSelect.appendChild(option);
        });
      } else {
        (items || []).forEach(it => {
          const name = String(it?.name || '');
          const label = name || 'Unassigned';
          const option = document.createElement('option');
          option.value = name;
          option.textContent = `${label} (${it?.count ?? 0})`;
          playlistFilterSelect.appendChild(option);
        });
      }
      playlistFilterSelect.value = prev || 'all';
      currentPlaylistFilter = playlistFilterSelect.value || 'all';
    } catch (_e) {
      // ignore
    }
  }

  async function loadSelectedPlaylistPreference() {
    if (currentScope === 'public') {
      const selectedPublic = localStorage.getItem(SELECTED_PUBLIC_PLAYLIST_KEY) || 'all';
      if (playlistFilterSelect) {
        const exists = Array.from(playlistFilterSelect.options).some(o => o.value === selectedPublic) || selectedPublic === 'all';
        playlistFilterSelect.value = exists ? selectedPublic : 'all';
      }
      currentPlaylistFilter = (playlistFilterSelect && playlistFilterSelect.value) || selectedPublic || 'all';
      return;
    }
    let fromServer = '';
    try {
      const r = await apiFetch(PREFERENCES_API);
      if (r.ok) {
        const d = await r.json();
        fromServer = String(d?.selectedPlaylist || '');
      }
    } catch (_e) {}
    const fromLocal = localStorage.getItem(SELECTED_PLAYLIST_KEY) || '';
    const chosen = fromServer || fromLocal || 'all';
    if (playlistFilterSelect) {
      const exists = Array.from(playlistFilterSelect.options).some(o => o.value === chosen) || chosen === 'all';
      playlistFilterSelect.value = exists ? chosen : 'all';
    }
    currentPlaylistFilter = (playlistFilterSelect && playlistFilterSelect.value) || chosen || 'all';
  }

  async function saveSelectedPlaylistPreference(value) {
    const v = String(value || 'all');
    if (currentScope === 'public') {
      localStorage.setItem(SELECTED_PUBLIC_PLAYLIST_KEY, v);
      return;
    }
    localStorage.setItem(SELECTED_PLAYLIST_KEY, v);
    try {
      await apiFetch(PREFERENCES_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ selectedPlaylist: v })
      });
    } catch (_e) {}
  }

  async function addTask() {
    const text = taskInput.value.trim();
    if (!text) return;

    const newTask = {
      text,
      date: taskDate.value,
      priority: taskPriority.value,
      category: taskCategory.value,
      visibility: taskVisibility ? taskVisibility.value : 'private',
      playlistName: currentPlaylistFilter && currentPlaylistFilter !== 'all' ? currentPlaylistFilter : '',
      completed: false
    };

    try {
      const response = await apiFetch(API_BASE, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newTask)
      });
      if (!response.ok) {
        showFlash(await readResponseMessage(response, 'Could not add task'), 'error');
        return;
      }

      const createdTask = await response.json();
      tasks.unshift(createdTask);
      renderTasks();
      showFlash('Task added', 'success');
    } catch (_error) {
      showFlash('Could not connect to add task', 'error');
      return;
    }

    taskInput.value = '';
    taskDate.value = '';
    taskPriority.value = 'medium';
    taskCategory.value = 'personal';
    if (taskVisibility) taskVisibility.value = 'private';
    taskInput.focus();
  }

  async function toggleTask(id, taskElement, checkboxElement) {
    const task = tasks.find(item => item.id === id);
    if (!task) return;

    try {
      const response = await apiFetch(`${API_BASE}/${id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ completed: !task.completed })
      });
      if (!response.ok) {
        showFlash(await readResponseMessage(response, 'Could not update task'), 'error');
        return;
      }

      const updatedTask = await response.json();
      tasks = tasks.map(item => item.id === id ? updatedTask : item);
      if (currentFilter === 'all' && taskElement) {
        taskElement.classList.toggle('completed', updatedTask.completed);
        if (checkboxElement) {
          checkboxElement.checked = updatedTask.completed;
        }
        const pendingCount = tasks.filter(item => !item.completed).length;
        updateBulkActionState(getFilteredTasks());
        updateDashboard();
      } else {
        renderTasks();
      }
      showFlash(updatedTask.completed ? 'Task marked done' : 'Task marked active', 'success');
    } catch (_error) {
      showFlash('Could not update task', 'error');
      return;
    }
  }

  function deleteTask(id, element) {
    element.classList.add('removing');

    setTimeout(async () => {
      try {
        const response = await apiFetch(`${API_BASE}/${id}`, { method: 'DELETE' });
        if (!response.ok && response.status !== 204) {
          showFlash(await readResponseMessage(response, 'Could not delete task'), 'error');
          return;
        }
        tasks = tasks.filter(task => task.id !== id);
        selectedTaskIds.delete(id);
        renderTasks();
        showFlash('Task deleted', 'success');
      } catch (_error) {
        showFlash('Could not delete task', 'error');
        return;
      }
    }, 300);
  }

  async function importPlaylist() {
    const playlistUrl = playlistUrlInput.value.trim();
    const playlistPriority = playlistPriorityInput.value.trim();
    const playlistType = playlistTypeInput.value.trim();
    const playlistDate = playlistDateInput.value;
    if (!playlistUrl) return;
    if (!playlistPriority || !playlistType) {
      setPlaylistStatus('Select priority and type before import', 'error');
      showFlash('Select priority and type before import', 'error');
      return;
    }

    importPlaylistBtn.disabled = true;
    importPlaylistBtn.classList.add('is-loading');
    importPlaylistBtn.textContent = 'Importing...';
    setPlaylistStatus('Importing playlist...', 'info');

    try {
      const response = await apiFetch(IMPORT_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          url: playlistUrl,
          priority: playlistPriority,
          category: playlistType,
          visibility: playlistVisibilityInput ? playlistVisibilityInput.value : 'private',
          date: playlistDate,
          playlistName: playlistNameInput ? playlistNameInput.value.trim() : '',
          maxVideos: IMPORT_LIMIT
        })
      });

      const data = await response.json();
      if (!response.ok) {
        setPlaylistStatus(data.message || 'Import failed', 'error');
        showFlash(data.message || 'Import failed', 'error');
        return;
      }

      const importedTasks = data.tasks || [];
      tasks = [...importedTasks, ...tasks];
      const importedPlaylistName = playlistNameInput ? playlistNameInput.value.trim() : '';
      await loadPlaylists();
      if (importedPlaylistName) {
        currentPlaylistFilter = importedPlaylistName;
        if (playlistFilterSelect) {
          playlistFilterSelect.value = importedPlaylistName;
        }
        await saveSelectedPlaylistPreference(currentPlaylistFilter);
        await loadTasks();
      }
      renderTasks();
      if (data.partial) {
        setPlaylistStatus(`Imported ${data.importedCount} videos as tasks. Some videos could not be imported.`, 'success');
      } else {
        setPlaylistStatus(`Imported ${data.importedCount} videos as tasks.`, 'success');
      }
      showFlash(`Imported ${data.importedCount} tasks`, 'success');
      playlistUrlInput.value = '';
      playlistPriorityInput.value = '';
      playlistTypeInput.value = '';
      if (playlistVisibilityInput) playlistVisibilityInput.value = 'private';
      playlistDateInput.value = '';
      if (playlistNameInput) playlistNameInput.value = '';
    } catch (_error) {
      setPlaylistStatus('Could not connect to import service', 'error');
      showFlash('Could not connect to import service', 'error');
    } finally {
      importPlaylistBtn.disabled = false;
      importPlaylistBtn.classList.remove('is-loading');
      importPlaylistBtn.textContent = importBtnIdleLabel;
    }
  }

  async function deleteSelectedTasks() {
    if (currentScope === 'public') return;
    const ids = Array.from(selectedTaskIds);
    if (ids.length === 0) return;

    const confirmed = await showCustomConfirm(
      'Delete Selected Tasks',
      `Are you sure you want to delete ${ids.length} selected tasks? This action cannot be undone.`,
      { confirmText: 'Delete', confirmVariant: 'danger' }
    );
    
    if (!confirmed) return;

    deleteSelectedBtn.disabled = true;
    try {
      const response = await apiFetch(BULK_DELETE_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids })
      });
      if (!response.ok) {
        showFlash(await readResponseMessage(response, 'Could not delete selected tasks'), 'error');
        return;
      }

      const idsSet = new Set(ids);
      tasks = tasks.filter(task => !idsSet.has(task.id));
      ids.forEach(id => selectedTaskIds.delete(id));
      renderTasks();
      showFlash(`Deleted ${ids.length} selected tasks`, 'success');
    } finally {
      deleteSelectedBtn.disabled = false;
    }
  }

  async function deleteAllTasks() {
    if (currentScope === 'public') return;
    if (tasks.length === 0) return;

    const confirmed = await showCustomConfirm(
      'Delete All Tasks',
      'Are you sure you want to delete ALL tasks? This action is permanent.',
      { confirmText: 'Delete All', confirmVariant: 'danger' }
    );
    
    if (!confirmed) return;

    deleteAllBtn.disabled = true;
    try {
      const response = await apiFetch(API_BASE, { method: 'DELETE' });
      if (!response.ok) {
        showFlash(await readResponseMessage(response, 'Could not delete all tasks'), 'error');
        return;
      }
      tasks = [];
      selectedTaskIds.clear();
      renderTasks();
      showFlash('All tasks deleted', 'success');
    } finally {
      deleteAllBtn.disabled = false;
    }
  }

  function getFilteredTasks() {
    let filteredTasks = tasks;
    if (currentFilter === 'active') filteredTasks = tasks.filter(t => !t.completed);
    if (currentFilter === 'completed') filteredTasks = tasks.filter(t => t.completed);
    return filteredTasks;
  }

  function updateBulkActionState(filteredTasks) {
    if (!selectAllTasksInput || !deleteSelectedBtn || !deleteAllBtn) return;
    if (currentScope === 'public') {
      selectAllTasksInput.checked = false;
      selectAllTasksInput.indeterminate = false;
      selectAllTasksInput.disabled = true;
      deleteSelectedBtn.disabled = true;
      deleteAllBtn.disabled = true;
      return;
    }
    selectAllTasksInput.disabled = false;
    const filteredIds = filteredTasks.map(task => task.id);
    const selectedVisibleCount = filteredIds.filter(id => selectedTaskIds.has(id)).length;
    selectAllTasksInput.checked = filteredIds.length > 0 && selectedVisibleCount === filteredIds.length;
    selectAllTasksInput.indeterminate = selectedVisibleCount > 0 && selectedVisibleCount < filteredIds.length;
    deleteSelectedBtn.disabled = selectedTaskIds.size === 0;
    deleteAllBtn.disabled = tasks.length === 0;
  }

  function getSafeWatchUrl(value) {
    const raw = String(value || '').trim();
    if (!raw) return '';
    try {
      const parsed = new URL(raw, window.location.origin);
      if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') return '';
      return parsed.toString();
    } catch (_error) {
      return '';
    }
  }

  function formatOwnerLabel(email) {
    const raw = String(email || '').trim();
    if (!raw) return '';
    const base = raw.split('@')[0] || '';
    if (!base) return '';
    return base
      .split(/[._\-\s]+/)
      .filter(Boolean)
      .map(part => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase())
      .join(' ');
  }

  async function trackTaskView(taskId) {
    const id = String(taskId || '').trim();
    if (!id) return;
    try {
      const response = await apiFetch(`${API_BASE}/${id}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ incrementViews: true })
      });
      if (!response.ok) return;
      const updated = await response.json();
      const idx = tasks.findIndex(task => task.id === id);
      if (idx !== -1 && updated && typeof updated.views === 'number') {
        tasks[idx] = { ...tasks[idx], views: updated.views };
      }
    } catch (_error) {
    }
  }

  function renderTasks() {
    if (!taskList) return;
    taskList.innerHTML = '';
    
    const filteredTasks = getFilteredTasks();

    updateBulkActionState(filteredTasks);
    updatePlaylistActionState();
    updateDashboard();

    if (filteredTasks.length === 0) {
      const li = document.createElement('li');
      li.className = 'task-item empty-state';
      const label = currentPlaylistFilter && currentPlaylistFilter !== 'all' ? currentPlaylistFilter : 'All';
      const heading = currentScope === 'public' ? 'No public tasks found' : `No tasks in ${label}`;
      const description = currentScope === 'public'
        ? 'Try changing playlist filter or switch to My Tasks.'
        : 'Add a task or import a playlist to get started.';
      li.innerHTML = `
        <div class="empty-state-card">
          <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
          <div class="empty-state-title">${heading}</div>
          <div class="empty-state-text">${description}</div>
        </div>
      `;
      taskList.appendChild(li);
      return;
    }
    filteredTasks.forEach(task => {
      const li = document.createElement('li');
      li.className = `task-item priority-${task.priority} ${task.completed ? 'completed' : ''} ${selectedTaskIds.has(task.id) ? 'selected-for-delete' : ''}`;
      
      const formattedDate = task.date ? new Date(task.date).toLocaleDateString('en-US', {month: 'short', day: 'numeric'}) : '';
      const watchUrl = getSafeWatchUrl(task.videoUrl);
      const ownerLabel = formatOwnerLabel(task.ownerEmail);
      const canDelete = currentScope !== 'public' || Number(task.ownerId || 0) === Number(currentUserId || 0);
      const canToggle = currentScope !== 'public' || task.visibility === 'public' || Number(task.ownerId || 0) === Number(currentUserId || 0);
      const downloadSubUrl = task.captionPath ? `/api/captions/${task.captionPath}` : '';
      
      // YT layout components
      const thumbnailHtml = task.thumbnailUrl 
        ? `<img src="${task.thumbnailUrl}" class="task-thumbnail" alt="${task.text}" loading="lazy">`
        : `<div class="task-thumbnail-placeholder"><i class="fab fa-youtube"></i></div>`;

      const descriptionHtml = task.description 
        ? `<div class="task-description">${task.description}</div>`
        : '';

      li.innerHTML = `
        <div class="task-thumbnail-wrapper">
          ${thumbnailHtml}
          <div class="task-priority-badge">${task.priority}</div>
        </div>
        <div class="task-body">
          <div class="task-content" title="${task.text}">${task.text}</div>
          ${descriptionHtml}
          <div class="task-footer">
            <div class="task-meta">
              ${formattedDate ? `<span><i class="far fa-calendar"></i> ${formattedDate}</span>` : ''}
              <span><i class="fas fa-tag"></i> ${task.category}</span>
              ${task.playlistName ? `<span><i class="fas fa-list"></i> ${task.playlistName}</span>` : ''}
              <span><i class="fas fa-chart-line"></i> ${Number(task.views || 0)} views</span>
              ${task.visibility ? `<span><i class="fas fa-eye"></i> ${task.visibility}</span>` : ''}
              ${ownerLabel ? `<span><i class="fas fa-user"></i> ${ownerLabel}</span>` : ''}
            </div>
            <div class="task-actions">
              <label class="task-check-wrap" title="${task.completed ? 'Completed' : 'Mark as done'}">
                <input type="checkbox" class="custom-checkbox" ${task.completed ? 'checked' : ''} ${canToggle ? '' : 'disabled'}>
                <span>${task.completed ? 'Done' : 'Mark done'}</span>
              </label>
              ${watchUrl ? `<a class="watch-btn" href="${watchUrl}" target="_blank" rel="noopener noreferrer"><i class="fab fa-youtube"></i> Watch</a>` : ''}
              ${downloadSubUrl ? `<a class="sub-btn" href="${downloadSubUrl}" download title="Download Subtitles"><i class="fas fa-closed-captioning"></i> Sub</a>` : ''}
              ${canDelete ? '<button class="delete-btn" title="Delete task"><i class="fas fa-trash"></i></button>' : ''}
            </div>
          </div>
        </div>
      `;

      li.addEventListener('click', (e) => {
        if (
          e.target.closest('a') ||
          e.target.closest('button') ||
          e.target.closest('input') ||
          e.target.closest('.task-check-wrap')
        ) {
          return;
        }

        if (task.completed) {
          return;
        }

        if (watchUrl) {
          trackTaskView(task.id).catch(() => {});
          window.open(watchUrl, '_blank');
        }
      });

      const checkbox = li.querySelector('.custom-checkbox');
      checkbox.addEventListener('change', (e) => {
        e.stopPropagation(); // Prevent card click
        if (!canToggle) {
          checkbox.checked = !!task.completed;
          return;
        }
        toggleTask(task.id, li, checkbox).catch(() => {});
      });

      const deleteBtn = li.querySelector('.delete-btn');
      if (deleteBtn) {
        deleteBtn.addEventListener('click', async (e) => {
          e.stopPropagation(); // Prevent card click
          const confirmed = await showCustomConfirm(
            'Delete Task',
            `Are you sure you want to delete "${task.text}"?`,
            { confirmText: 'Delete', confirmVariant: 'danger' }
          );
          if (confirmed) {
            deleteTask(task.id, li);
          }
        });
      }

      const watchBtn = li.querySelector('.watch-btn');
      if (watchBtn) {
        watchBtn.addEventListener('click', () => {
          trackTaskView(task.id).catch(() => {});
        });
      }

      taskList.appendChild(li);
    });
  }

  function updatePlaylistActionState() {
    const disable = currentScope === 'public' || !currentPlaylistFilter || currentPlaylistFilter === 'all';
    if (playlistVisibilityBtn) playlistVisibilityBtn.disabled = disable;
    if (playlistRenameBtn) playlistRenameBtn.disabled = disable;
    if (playlistDeleteBtn) playlistDeleteBtn.disabled = disable;
  }

  function updateDashboard() {
    if (!statTotalEl || !statCompletedEl || !statPendingEl || !statPlaylistEl || !statProgressFill || !statProgressLabel) return;
    const total = tasks.length;
    const done = tasks.filter(t => t.completed).length;
    const pending = total - done;
    const pct = total ? Math.round((done / total) * 100) : 0;
    statTotalEl.textContent = String(total);
    statCompletedEl.textContent = String(done);
    statPendingEl.textContent = String(pending);
    statPlaylistEl.textContent = currentPlaylistFilter && currentPlaylistFilter !== 'all' ? currentPlaylistFilter : 'All';
    statProgressFill.style.width = `${pct}%`;
    statProgressLabel.textContent = `${pct}%`;
  }

  function showCustomConfirm(title, message, options = {}) {
    if (!customModal || !modalTitle || !modalMessage || !modalCancel || !modalConfirm) {
      return Promise.resolve(false);
    }
    const confirmText = String(options.confirmText || 'Confirm');
    const confirmVariant = options.confirmVariant === 'primary' ? 'primary' : 'danger';
    return new Promise((resolve) => {
      modalTitle.textContent = title;
      modalMessage.textContent = message;
      modalConfirm.textContent = confirmText;
      modalConfirm.classList.remove('modal-btn-primary', 'modal-btn-danger');
      modalConfirm.classList.add(confirmVariant === 'primary' ? 'modal-btn-primary' : 'modal-btn-danger');
      if (modalInput) {
        modalInput.classList.add('app-hidden');
        modalInput.value = '';
      }
      customModal.classList.add('active');
      const onConfirm = () => {
        cleanup();
        resolve(true);
      };
      const onCancel = () => {
        cleanup();
        resolve(false);
      };
      const onEscape = (event) => {
        if (event.key !== 'Escape') return;
        cleanup();
        resolve(false);
      };
      const cleanup = () => {
        customModal.classList.remove('active');
        modalConfirm.removeEventListener('click', onConfirm);
        modalCancel.removeEventListener('click', onCancel);
        document.removeEventListener('keydown', onEscape);
      };
      modalConfirm.addEventListener('click', onConfirm);
      modalCancel.addEventListener('click', onCancel);
      document.addEventListener('keydown', onEscape);
    });
  }

  function showCustomPrompt(title, message, defaultValue = '', options = {}) {
    if (!customModal || !modalTitle || !modalMessage || !modalCancel || !modalConfirm || !modalInput) {
      return Promise.resolve(null);
    }
    const confirmText = String(options.confirmText || 'Save');
    const placeholder = String(options.placeholder || 'Enter value');
    return new Promise((resolve) => {
      modalTitle.textContent = title;
      modalMessage.textContent = message;
      modalInput.classList.remove('app-hidden');
      modalInput.placeholder = placeholder;
      modalInput.value = String(defaultValue || '');
      modalConfirm.textContent = confirmText;
      modalConfirm.classList.remove('modal-btn-danger', 'modal-btn-primary');
      modalConfirm.classList.add('modal-btn-primary');
      customModal.classList.add('active');
      const onConfirm = () => {
        const value = modalInput.value;
        cleanup();
        resolve(value);
      };
      const onCancel = () => {
        cleanup();
        resolve(null);
      };
      const onKeyDown = (event) => {
        if (event.key === 'Enter') {
          event.preventDefault();
          onConfirm();
          return;
        }
        if (event.key === 'Escape') {
          event.preventDefault();
          onCancel();
        }
      };
      const cleanup = () => {
        customModal.classList.remove('active');
        modalInput.classList.add('app-hidden');
        modalInput.value = '';
        modalConfirm.removeEventListener('click', onConfirm);
        modalCancel.removeEventListener('click', onCancel);
        modalInput.removeEventListener('keydown', onKeyDown);
      };
      modalConfirm.addEventListener('click', onConfirm);
      modalCancel.addEventListener('click', onCancel);
      modalInput.addEventListener('keydown', onKeyDown);
      setTimeout(() => modalInput.focus(), 0);
    });
  }
});
