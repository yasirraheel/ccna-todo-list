document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('todo-form');
  const taskInput = document.getElementById('task-input');
  const taskDate = document.getElementById('task-date');
  const taskPriority = document.getElementById('task-priority');
  const taskCategory = document.getElementById('task-category');
  const taskList = document.getElementById('task-list');
  const taskCount = document.getElementById('task-count');
  const filterBtns = document.querySelectorAll('.filter-btn');
  const dateDisplay = document.getElementById('current-date');
  const playlistUrlInput = document.getElementById('playlist-url');
  const playlistPriorityInput = document.getElementById('playlist-priority');
  const playlistTypeInput = document.getElementById('playlist-type');
  const playlistDateInput = document.getElementById('playlist-date');
  const playlistNameInput = document.getElementById('playlist-name');
  const importPlaylistBtn = document.getElementById('import-playlist-btn');
  const playlistStatus = document.getElementById('playlist-status');
  const playlistFilterSelect = document.getElementById('playlist-filter');
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
  const isLoginPage = window.location.pathname.toLowerCase().endsWith('/login.html') || document.body.dataset.page === 'login';
  const scrollTopBtn = document.getElementById('scroll-top-btn');

  const browserHost = window.location.hostname || 'localhost';
  const isLocalNetworkHost =
    browserHost === 'localhost' ||
    browserHost === '127.0.0.1' ||
    browserHost.startsWith('192.168.') ||
    browserHost.startsWith('10.') ||
    browserHost.startsWith('172.');
  let API_BASE = '/api/tasks';
  let IMPORT_API = '/api/import/youtube-playlist';
  let BULK_DELETE_API = '/api/tasks/bulk-delete';
  let AUTH_LOGIN_API = '/api/auth/login';
  let AUTH_REGISTER_API = '/api/auth/register';
  let AUTH_ME_API = '/api/auth/me';
  let PLAYLISTS_API = '/api/playlists';
  const IMPORT_LIMIT = 300;
  const AUTH_TOKEN_KEY = 'todo_auth_token';
  let tasks = [];
  let currentFilter = 'all';
  const selectedTaskIds = new Set();
  let authToken = localStorage.getItem(AUTH_TOKEN_KEY) || '';
  let currentPlaylistFilter = 'all';

  const options = { weekday: 'long', month: 'short', day: 'numeric' };
  if (dateDisplay) {
    dateDisplay.textContent = new Date().toLocaleDateString('en-US', options);
  }

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
      await loadTasks();
      renderTasks();
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
    IMPORT_API = `${apiRoot}/import/youtube-playlist`;
    BULK_DELETE_API = `${API_BASE}/bulk-delete`;
    AUTH_LOGIN_API = `${apiRoot}/auth/login`;
    AUTH_REGISTER_API = `${apiRoot}/auth/register`;
    AUTH_ME_API = `${apiRoot}/auth/me`;
    PLAYLISTS_API = `${apiRoot}/playlists`;
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

  function redirectToLogin() {
    if (!isLoginPage) {
      window.location.href = 'login.html';
    }
  }

  function redirectToApp() {
    if (isLoginPage) {
      window.location.href = 'index.html';
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
    if (authStatus) authStatus.textContent = '';
  }

  async function apiFetch(url, options = {}) {
    const headers = { ...(options.headers || {}) };
    if (authToken) {
      headers.Authorization = `Bearer ${authToken}`;
    }
    return fetch(url, { ...options, headers });
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
      return;
    }
    setAuthToken(data.token || '');
    showAppPanel(data.user || {});
    redirectToApp();
    if (isLoginPage) return;
    await loadTasks();
    renderTasks();
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
      return;
    }
    setAuthToken(data.token || '');
    showAppPanel(data.user || {});
    redirectToApp();
    if (isLoginPage) return;
    await loadTasks();
    renderTasks();
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
  }

  async function init() {
    await resolveApiBase();
    const authed = await authenticateWithStoredToken();
    if (!authed) return;
    if (isLoginPage) return;
    await loadPlaylists();
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
      const response = await apiFetch(`${API_BASE}${q}`);
      if (!response.ok) return;
      tasks = await response.json();
    } catch (_error) {
      tasks = [];
    }
  }

  async function loadPlaylists() {
    if (!playlistFilterSelect) return;
    try {
      const response = await apiFetch(PLAYLISTS_API);
      if (!response.ok) return;
      const items = await response.json();
      const prev = currentPlaylistFilter;
      playlistFilterSelect.innerHTML = '';
      const optAll = document.createElement('option');
      optAll.value = 'all';
      optAll.textContent = 'All Playlists';
      playlistFilterSelect.appendChild(optAll);
      (items || []).forEach(it => {
        const name = String(it?.name || '');
        const label = name || 'Unassigned';
        const option = document.createElement('option');
        option.value = name;
        option.textContent = `${label} (${it?.count ?? 0})`;
        playlistFilterSelect.appendChild(option);
      });
      playlistFilterSelect.value = prev || 'all';
      currentPlaylistFilter = playlistFilterSelect.value || 'all';
    } catch (_e) {
      // ignore
    }
  }

  async function addTask() {
    const text = taskInput.value.trim();
    if (!text) return;

    const newTask = {
      text,
      date: taskDate.value,
      priority: taskPriority.value,
      category: taskCategory.value,
      playlistName: currentPlaylistFilter && currentPlaylistFilter !== 'all' ? currentPlaylistFilter : '',
      completed: false
    };

    try {
      const response = await apiFetch(API_BASE, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(newTask)
      });
      if (!response.ok) return;

      const createdTask = await response.json();
      tasks.unshift(createdTask);
      renderTasks();
    } catch (_error) {
      return;
    }

    taskInput.value = '';
    taskDate.value = '';
    taskPriority.value = 'medium';
    taskCategory.value = 'personal';
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
      if (!response.ok) return;

      const updatedTask = await response.json();
      tasks = tasks.map(item => item.id === id ? updatedTask : item);
      if (currentFilter === 'all' && taskElement) {
        taskElement.classList.toggle('completed', updatedTask.completed);
        if (checkboxElement) {
          checkboxElement.checked = updatedTask.completed;
        }
        const pendingCount = tasks.filter(item => !item.completed).length;
        taskCount.textContent = pendingCount;
        updateBulkActionState(getFilteredTasks());
      } else {
        renderTasks();
      }
    } catch (_error) {
      return;
    }
  }

  function deleteTask(id, element) {
    element.classList.add('removing');

    setTimeout(async () => {
      try {
        const response = await apiFetch(`${API_BASE}/${id}`, { method: 'DELETE' });
        if (!response.ok && response.status !== 204) return;
        tasks = tasks.filter(task => task.id !== id);
        selectedTaskIds.delete(id);
        renderTasks();
      } catch (_error) {
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
      playlistStatus.textContent = 'Select priority and type before import';
      return;
    }

    importPlaylistBtn.disabled = true;
    playlistStatus.textContent = 'Importing playlist...';

    try {
      const response = await apiFetch(IMPORT_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          url: playlistUrl,
          priority: playlistPriority,
          category: playlistType,
          date: playlistDate,
          playlistName: playlistNameInput ? playlistNameInput.value.trim() : '',
          maxVideos: IMPORT_LIMIT
        })
      });

      const data = await response.json();
      if (!response.ok) {
        playlistStatus.textContent = data.message || 'Import failed';
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
        await loadTasks();
      }
      renderTasks();
      if (data.partial) {
        playlistStatus.textContent = `Imported ${data.importedCount} videos (${data.source || 'fallback'}). ${data.message || ''}`.trim();
      } else {
        playlistStatus.textContent = `Imported ${data.importedCount} videos as tasks (${data.source || 'primary'}, limit ${data.requestedLimit || IMPORT_LIMIT})`;
      }
      playlistUrlInput.value = '';
      playlistPriorityInput.value = '';
      playlistTypeInput.value = '';
      playlistDateInput.value = '';
      if (playlistNameInput) playlistNameInput.value = '';
    } catch (_error) {
      playlistStatus.textContent = `Could not connect to import service (${API_BASE})`;
    } finally {
      importPlaylistBtn.disabled = false;
    }
  }

  async function deleteSelectedTasks() {
    const ids = Array.from(selectedTaskIds);
    if (ids.length === 0) return;

    deleteSelectedBtn.disabled = true;
    try {
      const response = await apiFetch(BULK_DELETE_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ids })
      });
      if (!response.ok) return;

      const idsSet = new Set(ids);
      tasks = tasks.filter(task => !idsSet.has(task.id));
      ids.forEach(id => selectedTaskIds.delete(id));
      renderTasks();
    } finally {
      deleteSelectedBtn.disabled = false;
    }
  }

  async function deleteAllTasks() {
    if (tasks.length === 0) return;

    deleteAllBtn.disabled = true;
    try {
      const response = await apiFetch(API_BASE, { method: 'DELETE' });
      if (!response.ok) return;
      tasks = [];
      selectedTaskIds.clear();
      renderTasks();
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

  function renderTasks() {
    if (!taskList || !taskCount) return;
    taskList.innerHTML = '';
    
    const filteredTasks = getFilteredTasks();

    const pendingCount = tasks.filter(t => !t.completed).length;
    taskCount.textContent = pendingCount;
    updateBulkActionState(filteredTasks);

    filteredTasks.forEach(task => {
      const li = document.createElement('li');
      li.className = `task-item priority-${task.priority} ${task.completed ? 'completed' : ''} ${selectedTaskIds.has(task.id) ? 'selected-for-delete' : ''}`;
      
      const formattedDate = task.date ? new Date(task.date).toLocaleDateString('en-US', {month: 'short', day: 'numeric'}) : 'No date';
      const watchUrl = getSafeWatchUrl(task.videoUrl);
      const watchButton = watchUrl ? `<a class="watch-btn" href="${watchUrl}" target="_blank" rel="noopener noreferrer"><i class="fab fa-youtube"></i> Watch</a>` : '';
      const playlistLabel = task.playlistName ? `<span class="playlist-pill"><i class="fas fa-list"></i> ${task.playlistName}</span>` : '';

      li.innerHTML = `
        <input type="checkbox" class="custom-checkbox" ${task.completed ? 'checked' : ''}>
        <div class="task-details">
          <div class="task-content">${task.text}</div>
          <div class="task-meta">
            ${task.date ? `<span><i class="far fa-calendar"></i> ${formattedDate}</span>` : ''}
            <span><i class="fas fa-tag"></i> ${task.category}</span>
            ${playlistLabel}
          </div>
        </div>
        <div class="task-actions">
          ${watchButton}
          <button class="delete-btn"><i class="fas fa-trash"></i></button>
        </div>
      `;

      const checkbox = li.querySelector('.custom-checkbox');
      checkbox.addEventListener('change', () => {
        toggleTask(task.id, li, checkbox).catch(() => {});
      });

      const deleteBtn = li.querySelector('.delete-btn');
      deleteBtn.addEventListener('click', () => deleteTask(task.id, li));

      taskList.appendChild(li);
    });
  }
});
