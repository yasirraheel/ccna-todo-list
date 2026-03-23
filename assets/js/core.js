var browserHost = window.location.hostname || 'localhost';
var isLocalNetworkHost =
  browserHost === 'localhost' ||
  browserHost === '127.0.0.1' ||
  browserHost.startsWith('192.168.') ||
  browserHost.startsWith('10.') ||
  browserHost.startsWith('172.');

var API_BASE = '/api/tasks';
var PUBLIC_TASKS_API = '/api/tasks/public';
var IMPORT_API = '/api/import/youtube-playlist';
var BULK_DELETE_API = '/api/tasks/bulk-delete';
var AUTH_LOGIN_API = '/api/auth/login';
var AUTH_REGISTER_API = '/api/auth/register';
var AUTH_VERIFY_OTP_API = '/api/auth/verify-otp';
var AUTH_ME_API = '/api/auth/me';
var PLAYLISTS_API = '/api/playlists';
var PREFERENCES_API = '/api/preferences';
var GOOGLE_LOGIN_API = '/api/auth/google';
var PLAYLIST_VISIBILITY_API = '/api/playlists/visibility';
var PLAYLIST_RENAME_API = '/api/playlists/rename';
var PLAYLIST_DELETE_API = '/api/playlists/delete';
var ADMIN_API = '/api/admin';
var ADMIN_USERS_API = '/api/admin/users';
var ADMIN_SETTINGS_API = '/api/admin/settings';

var IMPORT_LIMIT = 300;
var AUTH_TOKEN_KEY = 'todo_auth_token';
var SELECTED_PLAYLIST_KEY = 'todo_selected_playlist';
var SELECTED_PUBLIC_PLAYLIST_KEY = 'todo_selected_public_playlist';
var TASK_SCOPE_KEY = 'todo_task_scope';
var TASK_STATUS_FILTER_KEY = 'todo_task_status_filter';
var SCROLL_POSITION_KEY = 'todo_scroll_position';
var VISIBLE_TASK_COUNT_KEY = 'todo_visible_task_count';
var TASKS_ROWS_PER_PAGE = 6;

var authToken = localStorage.getItem(AUTH_TOKEN_KEY) || '';
var appName = 'My Tasks';
var tasks = [];
var visibleTaskCount = 18;
var isLoadingMore = false;

// DOM Elements
var form, taskInput, taskDate, taskPriority, taskCategory, taskVisibility, taskList, filterBtns, dateDisplay;
var playlistUrlInput, playlistPriorityInput, playlistTypeInput, playlistVisibilityInput, playlistDateInput, playlistNameInput;
var importPlaylistBtn, playlistStatus, importBtnIdleLabel, playlistFilterSelect, scopeFilterSelect;
var playlistVisibilityBtn, playlistRenameBtn, playlistDeleteBtn, selectAllTasksInput, deleteSelectedBtn, deleteAllBtn, loadMoreBtn;
var authPanel, appContainer, authStatus, authForm, otpForm, otpEmailDisplay, otpInput, otpSubmitBtn;
var authNameInput, authNameField, authEmailInput, authPasswordInput, authTitleEl, authSubtitleEl;
var authModeLoginBtn, authModeRegisterBtn, authSubmitBtn, authSwitchLabel, authSwitchBtn, logoutBtn, sessionEmail;
let appTitle, footerTitle, footerDescription, footerYear, scrollTopBtn, pageLoader;

const isLoginPage = window.location.pathname.toLowerCase().endsWith('/login') || document.body.dataset.page === 'login';
const isAdminPage = window.location.pathname.toLowerCase().endsWith('/admin');

let authMode = 'login';
let currentFilter = localStorage.getItem(TASK_STATUS_FILTER_KEY) || 'all';
if (!['all', 'active', 'completed', 'has-notes'].includes(currentFilter)) currentFilter = 'all';
const selectedTaskIds = new Set();
let currentPlaylistFilter = 'all';
let currentScope = localStorage.getItem(TASK_SCOPE_KEY) || 'my';
let currentUserId = null;
const taskNotesCache = new Map();
const taskNotesLoading = new Set();

// Admin Elements
let adminNavItems, adminSections, adminLogoutBtn, adminSettingsForm, adminStatUsers, adminStatTasks, adminStatPublic, adminStatNotes;
let recentUsersTable, allUsersTable, adminEmailEl;
let allTasksTable, adminTaskSearch, adminTaskUserSearch, adminTaskFilter, adminTasksPagination;

// Captcha
let captchaQuestion, captchaInput, currentCaptchaAnswer = null;

// Modals
let customModal, modalTitle, modalMessage, modalInput, modalCancel, modalConfirm, flashStack;
let subModal, subModalTitle, subModalMessage, subModalCancel, subModalConfirm;

// Search
let saasSearchInput, currentSearchQuery = '';

// Stats
let statTotalEl, statCompletedEl, statPendingEl, statPlaylistEl, statProgressFill, statProgressLabel;


if (isAdminPage) {
    adminInit().catch(() => {});
  } else {
    init().catch(() => {});
  }

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      addTask().catch(() => {});
    });
  }

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      currentFilter = btn.dataset.filter;
      localStorage.setItem(TASK_STATUS_FILTER_KEY, currentFilter);
      localStorage.removeItem(VISIBLE_TASK_COUNT_KEY);
      localStorage.removeItem(SCROLL_POSITION_KEY);
      syncFilterButtons();
      syncInitialPageSize();
      showPageLoader('Updating view...');
      renderTasks();
      hidePageLoader();
      const filterLabel = currentFilter === 'has-notes' ? 'tasks with notes' : `${currentFilter} tasks`;
      showFlash(`Showing ${filterLabel}`, 'info');
    });
  });

  if (importPlaylistBtn) {
    importPlaylistBtn.addEventListener('click', () => {
      importPlaylist().catch(() => {});
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

  if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', async () => {
      if (isLoadingMore) return;
      isLoadingMore = true;
      loadMoreBtn.classList.add('is-loading');
      loadMoreBtn.disabled = true;
      await new Promise(resolve => setTimeout(resolve, 220));
      
      const columns = getGridColumnCount();
      const rowsToAdd = 4; // Add 4 more full rows
      visibleTaskCount += (columns * rowsToAdd);
      saveVisibleTaskCount();
      
      isLoadingMore = false;
      loadMoreBtn.classList.remove('is-loading');
      loadMoreBtn.disabled = false;
      renderTasks();
    });
  }

  function syncFilterButtons() {
  filterBtns.forEach(btn => {
    btn.classList.toggle('active', btn.dataset.filter === currentFilter);
  });
  
  const bottomFilterBtns = document.querySelectorAll('.bottom-filter-btn');
  bottomFilterBtns.forEach(btn => {
    btn.classList.toggle('active', btn.dataset.filter === currentFilter);
  });
}

  if (playlistFilterSelect) {
    playlistFilterSelect.addEventListener('change', async () => {
      currentPlaylistFilter = playlistFilterSelect.value || 'all';
      await saveSelectedPlaylistPreference(currentPlaylistFilter);
      
      updateMobileBottomPlaylistName(Array.from(playlistFilterSelect.options).map(o => ({id: o.value, name: o.text})));
      
      localStorage.removeItem(VISIBLE_TASK_COUNT_KEY);
      localStorage.removeItem(SCROLL_POSITION_KEY);
      syncInitialPageSize();
      showPageLoader('Switching playlist...');
      await loadTasks();
      renderTasks();
      updatePlaylistActionState();
      hidePageLoader();
      const label = currentPlaylistFilter === 'all' ? 'All Playlists' : currentPlaylistFilter;
      showFlash(`Playlist selected: ${label}`, 'info');
    });
  }

  if (scopeFilterSelect) {
    scopeFilterSelect.value = currentScope === 'public' ? 'public' : 'my';
    scopeFilterSelect.addEventListener('change', async () => {
      currentScope = scopeFilterSelect.value === 'public' ? 'public' : 'my';
      localStorage.setItem(TASK_SCOPE_KEY, currentScope);
      localStorage.removeItem(VISIBLE_TASK_COUNT_KEY);
      localStorage.removeItem(SCROLL_POSITION_KEY);
      selectedTaskIds.clear();
      currentPlaylistFilter = 'all';
      syncInitialPageSize();
      if (playlistFilterSelect) playlistFilterSelect.value = 'all';
      showPageLoader('Switching scope...');
      await loadPlaylists();
      await loadSelectedPlaylistPreference();
      await loadTasks();
      renderTasks();
      updatePlaylistActionState();
      hidePageLoader();
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
      const data = await r.json();
      await loadPlaylists();
      currentPlaylistFilter = toName;
      if (playlistFilterSelect) playlistFilterSelect.value = currentPlaylistFilter;
      await saveSelectedPlaylistPreference(currentPlaylistFilter);
      await loadTasks();
      renderTasks();
      updatePlaylistActionState();
      showFlash(data.message || `Playlist renamed to ${toName}`, 'success');
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
      const data = await r.json();
      await loadTasks();
      renderTasks();
      updatePlaylistActionState();
      showFlash(data.message || `Playlist visibility set to ${nextVisibility}`, 'success');
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
      const data = await r.json();
      await loadPlaylists();
      currentPlaylistFilter = 'all';
      if (playlistFilterSelect) playlistFilterSelect.value = 'all';
      await saveSelectedPlaylistPreference(currentPlaylistFilter);
      await loadTasks();
      renderTasks();
      updatePlaylistActionState();
      showFlash(data.message || 'Playlist cleared to Unassigned', 'success');
    });
  }
  if (authModeLoginBtn) {
    authModeLoginBtn.addEventListener('click', () => setAuthMode('login'));
  }

  if (authModeRegisterBtn) {
    authModeRegisterBtn.addEventListener('click', () => setAuthMode('register'));
  }

  if (authSwitchBtn) {
    authSwitchBtn.addEventListener('click', () => {
      setAuthMode(authMode === 'login' ? 'register' : 'login');
    });
  }

  if (authForm) {
    authForm.addEventListener('submit', (e) => {
      e.preventDefault();
      if (authMode === 'register') {
        registerUser().catch(() => {});
      } else {
        loginUser().catch(() => {});
      }
    });
  }

  if (otpForm) {
    otpForm.addEventListener('submit', (e) => {
      e.preventDefault();
      verifyOtp().catch(() => {});
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
    AUTH_VERIFY_OTP_API = `${apiRoot}/auth/verify-otp`;
    AUTH_ME_API = `${apiRoot}/auth/me`;
    PLAYLISTS_API = `${apiRoot}/playlists`;
    PREFERENCES_API = `${apiRoot}/preferences`;
    GOOGLE_LOGIN_API = `${apiRoot}/auth/google`;
    PLAYLIST_VISIBILITY_API = `${apiRoot}/playlists/visibility`;
    PLAYLIST_RENAME_API = `${apiRoot}/playlists/rename`;
    PLAYLIST_DELETE_API = `${apiRoot}/playlists/delete`;
    ADMIN_API = `${apiRoot}/admin`;
    ADMIN_USERS_API = `${apiRoot}/admin/users`;
    ADMIN_SETTINGS_API = `${apiRoot}/admin/settings`;
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
      window.location.href = '/login';
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
    syncAuthModeUi();
  }

  function setAuthMode(mode) {
    authMode = mode === 'register' ? 'register' : 'login';
    syncAuthModeUi();
  }

  function syncAuthModeUi() {
    if (authNameField) authNameField.classList.toggle('app-hidden', authMode !== 'register');
    if (authModeLoginBtn) authModeLoginBtn.classList.toggle('active', authMode === 'login');
    if (authModeRegisterBtn) authModeRegisterBtn.classList.toggle('active', authMode === 'register');
    if (authTitleEl) authTitleEl.textContent = authMode === 'register' ? 'Create Your Account' : 'Welcome Back';
    if (authSubtitleEl) authSubtitleEl.textContent = authMode === 'register'
      ? 'Register once and start managing tasks with playlists and notes.'
      : 'Sign in to continue managing your tasks.';
    if (authSubmitBtn) authSubmitBtn.textContent = authMode === 'register' ? 'Register' : 'Login';
    if (authSwitchLabel) authSwitchLabel.textContent = authMode === 'register' ? 'Already have an account?' : 'Don’t have an account?';
    if (authSwitchBtn) authSwitchBtn.textContent = authMode === 'register' ? 'Login here' : 'Create account';
    if (authStatus) {
      authStatus.textContent = '';
      authStatus.classList.remove('show', 'success', 'error');
    }
    generateCaptcha();
  }

  function generateCaptcha() {
    if (!captchaQuestion) return;
    const a = Math.floor(Math.random() * 10) + 1;
    const b = Math.floor(Math.random() * 10) + 1;
    currentCaptchaAnswer = a + b;
    captchaQuestion.textContent = `${a} + ${b} = ?`;
    if (captchaInput) captchaInput.value = '';
  }

  function getGridColumnCount() {
    if (!taskList) return 1;
    const computedStyle = window.getComputedStyle(taskList);
    const gridTemplateColumns = computedStyle.getPropertyValue('grid-template-columns');
    const cols = gridTemplateColumns.split(' ').length;
    return cols > 0 ? cols : 1;
  }

  function syncInitialPageSize() {
    const columns = getGridColumnCount();
    const initial = columns * TASKS_ROWS_PER_PAGE;
    const saved = localStorage.getItem(VISIBLE_TASK_COUNT_KEY);
    if (saved) {
      const savedCount = parseInt(saved, 10);
      visibleTaskCount = Math.max(initial, savedCount || initial);
    } else {
      visibleTaskCount = initial;
    }
  }

  function saveVisibleTaskCount() {
    localStorage.setItem(VISIBLE_TASK_COUNT_KEY, visibleTaskCount);
  }

  function showAppPanel(user) {
    if (authPanel) authPanel.classList.add('app-hidden');
    if (appContainer) appContainer.classList.remove('app-hidden');
    if (sessionEmail) sessionEmail.textContent = user?.email || '';
    currentUserId = Number(user?.id || 0) || null;
    if (authStatus) {
      authStatus.textContent = '';
      authStatus.classList.remove('show');
    }

    // Add Admin link if admin
    if (user?.role === 'admin') {
      const navUl = document.querySelector('#navbar ul');
      if (navUl && !navUl.querySelector('a[href="/admin.php"]')) {
        const adminLi = document.createElement('li');
        adminLi.innerHTML = `<a href="/admin.php" class="nav-admin-link"><i class="fas fa-shield-halved" style="margin-right: 6px;"></i>Admin</a>`;
        // Insert before the session-info-wrap or at the end
        const sessionWrap = navUl.querySelector('.session-info-wrap');
        if (sessionWrap) {
          navUl.insertBefore(adminLi, sessionWrap);
        } else {
          navUl.appendChild(adminLi);
        }
      }
    }
  }

  async function apiFetch(url, options = {}) {
    const headers = { ...(options.headers || {}) };
    if (authToken) {
      headers.Authorization = `Bearer ${authToken}`;
    }
    
    // Don't set Content-Type if it's FormData, let browser handle it
    if (options.body instanceof FormData) {
      if (headers['Content-Type']) delete headers['Content-Type'];
    } else if (options.body && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }

    return fetch(url, { ...options, headers });
  }

  async function logActivity(activity = 'Page Visit', pageUrl = window.location.href) {
    try {
      await apiFetch(`${API_BASE.replace('/tasks', '')}/logs`, {
        method: 'POST',
        body: JSON.stringify({ activity, page_url: pageUrl })
      });
    } catch (_e) {
      // Fail silently for logs
    }
  }

  function updateFavicon(url) {
    if (!url) return;
    // Standard favicons
    const rels = ['icon', 'shortcut icon', 'apple-touch-icon'];
    rels.forEach(rel => {
      let link = document.querySelector(`link[rel="${rel}"]`);
      if (!link) {
        link = document.createElement('link');
        link.rel = rel;
        document.head.appendChild(link);
      }
      link.href = url;
    });
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

    if (config?.footerText && footerTitle) {
      footerTitle.textContent = config.footerText;
    }

    if (config?.logoUrl) {
      const logoEls = document.querySelectorAll('.saas-logo, .admin-sidebar-logo span');
      logoEls.forEach(el => {
        // If it's the admin sidebar logo text, we might want to keep it or add an image
        if (el.classList.contains('saas-logo')) {
          el.innerHTML = `<img src="${config.logoUrl}" alt="${title}" style="max-height: 40px;">`;
        }
      });
    }

    if (config?.faviconUrl) {
      updateFavicon(config.faviconUrl);
    }

    if (config?.googleClientId && config?.googleLoginEnabled) {
      const googleRow = document.getElementById('google-login-row');
      const googleOnload = document.getElementById('g_id_onload');
      if (googleRow && googleOnload) {
        googleOnload.setAttribute('data-client_id', config.googleClientId);
        googleRow.style.display = 'block';
        // Re-initialize Google button if script already loaded
        if (window.google && window.google.accounts) {
          window.google.accounts.id.initialize({
            client_id: config.googleClientId,
            callback: window.handleGoogleLogin
          });
          window.google.accounts.id.renderButton(
            document.querySelector('.g_id_signin'),
            { theme: 'outline', size: 'large', width: '100%' }
          );
        }
      }
    }
  }

  window.handleGoogleLogin = async (response) => {
    showPageLoader('Signing in with Google...');
    try {
      const r = await fetch(GOOGLE_LOGIN_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ credential: response.credential })
      });
      const data = await r.json();
      if (r.ok) {
        setAuthToken(data.token || '');
        showAppPanel(data.user || {});
        redirectToApp();
      } else {
        showFlash(data.message || 'Google Login failed', 'error');
      }
    } catch (_err) {
      showFlash('Google Login error', 'error');
    } finally {
      hidePageLoader();
    }
  };

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
        if (data.user?.role === 'admin') {
          window.location.href = '/admin';
        } else {
          redirectToApp();
        }
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
    const captchaValue = captchaInput ? parseInt(captchaInput.value, 10) : null;
    
    if (!email || !password) {
      if (authStatus) {
        authStatus.textContent = 'Email and password are required';
        authStatus.classList.add('show', 'error');
      }
      return;
    }

    if (captchaInput && captchaValue !== currentCaptchaAnswer) {
      if (authStatus) {
        authStatus.textContent = 'Incorrect captcha answer. Try again.';
        authStatus.classList.add('show', 'error');
      }
      generateCaptcha();
      return;
    }

    if (authStatus) {
      authStatus.textContent = 'Signing in...';
      authStatus.classList.add('show');
      authStatus.classList.remove('error', 'success');
    }
    if (authSubmitBtn) {
      authSubmitBtn.disabled = true;
      authSubmitBtn.classList.add('is-loading');
    }
    try {
      const response = await fetch(AUTH_LOGIN_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
      });
      const data = await response.json();
      if (!response.ok) {
        if (authStatus) {
          authStatus.textContent = data.message || 'Login failed';
          authStatus.classList.add('show', 'error');
        }
        showFlash(data.message || 'Login failed', 'error');
        
        // If email not verified, show OTP form
        if (response.status === 403 && data.message.toLowerCase().includes('verify')) {
          if (authForm) authForm.style.display = 'none';
          if (otpForm) otpForm.style.display = 'block';
          if (otpEmailDisplay) otpEmailDisplay.textContent = email;
        }
        return;
      }
      setAuthToken(data.token || '');
      setDefaultPublicScope();
      showAppPanel(data.user || {});
      
      if (data.user?.role === 'admin') {
        window.location.href = '/admin';
        return;
      }
      
      redirectToApp();
      if (isLoginPage) return;
      await loadPlaylists();
      syncInitialPageSize();
      await loadTasks();
      renderTasks();
      showFlash(data.message || 'Logged in successfully', 'success');
    } catch (err) {
      if (authStatus) {
        authStatus.textContent = 'Connection error';
        authStatus.classList.add('show', 'error');
      }
    } finally {
      if (authSubmitBtn) {
        authSubmitBtn.disabled = false;
        authSubmitBtn.classList.remove('is-loading');
      }
    }
  }

  async function registerUser() {
    if (!authNameInput || !authEmailInput || !authPasswordInput) return;
    const name = authNameInput.value.trim();
    const email = authEmailInput.value.trim();
    const password = authPasswordInput.value;
    const captchaValue = captchaInput ? parseInt(captchaInput.value, 10) : null;
    
    if (!name || !email || !password) {
      if (authStatus) {
        authStatus.textContent = 'Name, email and password are required';
        authStatus.classList.add('show', 'error');
      }
      return;
    }

    if (captchaInput && captchaValue !== currentCaptchaAnswer) {
      if (authStatus) {
        authStatus.textContent = 'Incorrect captcha answer. Try again.';
        authStatus.classList.add('show', 'error');
      }
      generateCaptcha();
      return;
    }

    if (authStatus) {
      authStatus.textContent = 'Creating account...';
      authStatus.classList.add('show');
      authStatus.classList.remove('error', 'success');
    }
    if (authSubmitBtn) {
      authSubmitBtn.disabled = true;
      authSubmitBtn.classList.add('is-loading');
    }
    try {
      const response = await fetch(AUTH_REGISTER_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ name, email, password })
      });
      const data = await response.json();
      if (!response.ok) {
        if (authStatus) {
          authStatus.textContent = data.message || 'Register failed';
          authStatus.classList.add('show', 'error');
        }
        showFlash(data.message || 'Register failed', 'error');
        return;
      }

      if (data.requireOtp) {
        if (authForm) authForm.style.display = 'none';
        if (otpForm) otpForm.style.display = 'block';
        if (otpEmailDisplay) otpEmailDisplay.textContent = data.email || email;
        if (authStatus) {
          authStatus.textContent = data.message;
          authStatus.classList.add('show', 'success');
        }
        showFlash(data.message, 'success');
        return;
      }

      setAuthToken(data.token || '');
      setDefaultPublicScope();
      showAppPanel(data.user || {});
      
      if (data.user?.role === 'admin') {
        window.location.href = '/admin';
        return;
      }
      
      redirectToApp();
      if (isLoginPage) return;
      await loadPlaylists();
      syncInitialPageSize();
      await loadTasks();
      renderTasks();
      showFlash(data.message || 'Account created successfully', 'success');
    } catch (err) {
      if (authStatus) {
        authStatus.textContent = 'Connection error';
        authStatus.classList.add('show', 'error');
      }
    } finally {
      if (authSubmitBtn) {
        authSubmitBtn.disabled = false;
        authSubmitBtn.classList.remove('is-loading');
      }
    }
  }

  async function verifyOtp() {
    if (!otpInput || !otpEmailDisplay) return;
    const email = otpEmailDisplay.textContent;
    const otp = otpInput.value.trim();
    
    if (!otp || otp.length !== 6) {
      showFlash('Please enter a 6-digit code', 'error');
      return;
    }

    if (otpSubmitBtn) {
      otpSubmitBtn.disabled = true;
      otpSubmitBtn.classList.add('is-loading');
    }

    try {
      const response = await fetch(AUTH_VERIFY_OTP_API, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, otp })
      });
      const data = await response.json();
      if (!response.ok) {
        showFlash(data.message || 'Verification failed', 'error');
        return;
      }
      
      setAuthToken(data.token || '');
      setDefaultPublicScope();
      showAppPanel(data.user || {});
      
      if (data.user?.role === 'admin') {
        window.location.href = '/admin';
        return;
      }
      
      redirectToApp();
      if (isLoginPage) return;
      await loadPlaylists();
      syncInitialPageSize();
      await loadTasks();
      renderTasks();
      showFlash(data.message || 'Email verified successfully', 'success');
    } catch (err) {
      showFlash('Connection error during verification', 'error');
    } finally {
      if (otpSubmitBtn) {
        otpSubmitBtn.disabled = false;
        otpSubmitBtn.classList.remove('is-loading');
      }
    }
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

  async function adminInit() {
    console.log('Initializing admin application...');
    try {
      await resolveApiBase();
      const authed = await authenticateWithStoredToken();
      if (!authed) {
        hidePageLoader();
        return;
      }
      
      // Extra security check for admin page
      const r = await apiFetch(AUTH_ME_API);
      if (r.ok) {
        const d = await r.json();
        if ((d.user?.role || 'user') !== 'admin') {
          showFlash('Admin access denied', 'error');
          window.location.href = '/';
          return;
        }
        if (adminEmailEl) adminEmailEl.textContent = d.user?.email || '';
      }

      showPageLoader('Loading admin data...');
      try {
        await loadAdminDashboard();
      } catch (err) {
        console.error('Failed to load admin dashboard data:', err);
        showFlash('Failed to load admin dashboard', 'error');
      } finally {
        hidePageLoader(); // Always hide loader, even if it fails
      }


      
      // Setup Admin Navigation
      adminNavItems.forEach(item => {
        item.addEventListener('click', () => {
          const section = item.dataset.section;
          window.location.hash = section;
          switchAdminSection(section);
        });
      });

      // Handle initial hash
      const initialSection = window.location.hash.substring(1) || 'dashboard';
      if (['dashboard', 'users', 'tasks', 'settings'].includes(initialSection)) {
        switchAdminSection(initialSection);
      }


      if (adminTaskSearch) {
        let searchTimeout;
        adminTaskSearch.addEventListener('input', () => {
          clearTimeout(searchTimeout);
          searchTimeout = setTimeout(() => loadAllTasks(1), 500);
        });
      }

      if (adminTaskUserSearch) {
        let userSearchTimeout;
        adminTaskUserSearch.addEventListener('input', () => {
          clearTimeout(userSearchTimeout);
          userSearchTimeout = setTimeout(() => loadAllTasks(1), 500);
        });
      }

      if (adminTaskFilter) {
        adminTaskFilter.addEventListener('change', () => loadAllTasks(1));
      }

      if (adminLogoutBtn) {
        adminLogoutBtn.addEventListener('click', logoutUser);
      }

      if (adminSettingsForm) {
        const submitBtn = adminSettingsForm.querySelector('button[type="submit"]');
        adminSettingsForm.addEventListener('submit', async (e) => {
          e.preventDefault();
          const formData = new FormData(adminSettingsForm);
          
          if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add('is-loading');
          }

          try {
            const r = await apiFetch(ADMIN_SETTINGS_API, {
              method: 'POST',
              body: formData
            });
            
            if (r.ok) {
              const data = await r.json();
              showFlash(data.message || 'Settings saved successfully', 'success');
              logActivity('Admin Saved Settings');
              // Reload to apply changes after a short delay
              setTimeout(() => window.location.reload(), 1200);
            } else {
              const errorMsg = await readResponseMessage(r, 'Failed to save settings');
              showFlash(errorMsg, 'error');
              if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('is-loading');
              }
            }
          } catch (_err) {
            showFlash('Connection error while saving settings', 'error');
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.classList.remove('is-loading');
            }
          }
        });
      }

      if (isAdminPage) {
        const originEl = document.getElementById('origin-url');
        const callbackEl = document.getElementById('callback-url');
        if (originEl) originEl.textContent = window.location.origin;
        
        if (callbackEl) {
          // Correctly handle subdirectories for the callback URL
          const path = window.location.pathname.replace('admin.php', '').replace('/admin', '');
          const base = window.location.origin + (path.endsWith('/') ? path : path + '/');
          callbackEl.textContent = `${base}login`;
        }
      }
    } catch (err) {
      console.error('Admin Init Error:', err);
      showFlash('Failed to load admin panel', 'error');
    } finally {
      hidePageLoader();
    }
  }

  window.copyToClipboard = (elementId) => {
    const el = document.getElementById(elementId);
    if (!el) return;
    const text = el.textContent || el.innerText;
    navigator.clipboard.writeText(text).then(() => {
      showFlash('URL copied to clipboard', 'success');
    }).catch(() => {
      showFlash('Failed to copy URL', 'error');
    });
  };

  function switchAdminSection(sectionId) {
    adminNavItems.forEach(i => i.classList.toggle('active', i.dataset.section === sectionId));
    adminSections.forEach(s => s.classList.toggle('active', s.id === `section-${sectionId}`));
    
    const title = sectionId.charAt(0).toUpperCase() + sectionId.slice(1);
    const titleEl = document.getElementById('section-title');
    if (titleEl) titleEl.textContent = title;

    if (sectionId === 'users') loadAllUsers();
    if (sectionId === 'tasks') loadAllTasks(1);
    if (sectionId === 'logs') loadActivityLogs(1);
    if (sectionId === 'dashboard') loadAdminDashboard();
  }

  window.loadActivityLogs = async (page = 1) => {
    try {
      const r = await apiFetch(`${API_BASE.replace('/tasks', '')}/admin/logs?page=${page}`);
      if (!r.ok) return;
      const data = await r.json();
      renderActivityLogsTable(document.getElementById('activity-logs-table'), data.logs || []);
      renderLogsPagination(data.pagination);
    } catch (_e) {}
  };

  function renderActivityLogsTable(container, logs) {
    if (!container) return;
    container.innerHTML = '';
    
    if (logs.length === 0) {
      container.innerHTML = '<tr><td colspan="4" style="text-align:center; padding: 40px; color: rgba(255,255,255,0.5);">No logs found</td></tr>';
      return;
    }

    logs.forEach(log => {
      const tr = document.createElement('tr');
      const timeStr = new Date(log.created_at).toLocaleString();
      
      tr.innerHTML = `
        <td data-label="Time & IP">
          <div style="font-weight: 600; color: #fff;">${timeStr}</div>
          <div style="font-size: 0.75rem; color: rgba(255,255,255,0.5);">${log.ip_address}</div>
        </td>
        <td data-label="User">
          <div style="font-size: 0.85rem; color: rgba(255,255,255,0.8);">${log.user_email || 'Guest'}</div>
        </td>
        <td data-label="Page & Activity">
          <div style="font-weight: 500; color: #ee8331;">${log.activity}</div>
          <div style="font-size: 0.75rem; color: rgba(255,255,255,0.5); word-break: break-all;">${log.page_url}</div>
        </td>
        <td data-label="Device/Browser">
          <div style="font-size: 0.75rem; color: rgba(255,255,255,0.6); max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${log.user_agent || ''}">
            ${log.user_agent || 'Unknown'}
          </div>
        </td>
      `;
      container.appendChild(tr);
    });
  }

  function renderLogsPagination(pagination) {
    const container = document.getElementById('admin-logs-pagination');
    if (!container) return;
    container.innerHTML = '';
    
    if (!pagination || pagination.pages <= 1) return;
    
    for (let i = 1; i <= pagination.pages; i++) {
      if (i > 5 && i < pagination.pages) {
        if (i === 6) {
          const dot = document.createElement('span');
          dot.textContent = '...';
          dot.style.color = 'rgba(255,255,255,0.3)';
          container.appendChild(dot);
        }
        continue;
      }
      
      const btn = document.createElement('button');
      btn.className = `bulk-btn ${i === pagination.page ? 'primary' : ''}`;
      btn.style.padding = '4px 10px';
      btn.textContent = i;
      btn.onclick = () => window.loadActivityLogs(i);
      container.appendChild(btn);
    }
  }

  async function loadAdminDashboard() {
    try {
      const r = await apiFetch(ADMIN_API);
      if (!r.ok) return;
      const data = await r.json();
      
      if (adminStatUsers) adminStatUsers.textContent = data.stats?.totalUsers || 0;
      if (adminStatTasks) adminStatTasks.textContent = data.stats?.totalTasks || 0;
      if (adminStatPublic) adminStatPublic.textContent = data.stats?.totalPublicTasks || 0;
      if (adminStatNotes) adminStatNotes.textContent = data.stats?.totalNotes || 0;
      
      renderUsersTable(recentUsersTable, data.recentUsers || []);
      
      // Populate settings form if it exists
      if (adminSettingsForm && data.settings) {
        Object.entries(data.settings).forEach(([key, value]) => {
          const input = adminSettingsForm.querySelector(`[name="${key}"]`);
          if (input) {
            if (input.type === 'checkbox') {
              input.checked = (value === '1');
            } else if (input.type !== 'file') {
              input.value = value;
            }
          }
          
          // Previews
          if (key === 'LOGO_URL' && value) {
            const preview = document.getElementById('logo-preview-container');
            if (preview) preview.innerHTML = `<img src="${value}" style="max-width: 100%; max-height: 100%; object-fit: contain;">`;
          }
          if (key === 'FAVICON_URL' && value) {
            const preview = document.getElementById('favicon-preview-container');
            if (preview) preview.innerHTML = `<img src="${value}" style="max-width: 100%; max-height: 100%; object-fit: contain;">`;
          }
        });
      }
    } catch (_e) {}
  }

  async function loadAllUsers() {
    try {
      const r = await apiFetch(ADMIN_USERS_API);
      if (!r.ok) return;
      const users = await r.json();
      renderUsersTable(allUsersTable, users);
    } catch (_e) {}
  }

  async function loadAllTasks(page = 1) {
    try {
      const search = adminTaskSearch?.value || '';
      const userSearch = adminTaskUserSearch?.value || '';
      const filter = adminTaskFilter?.value || 'all';
      const r = await apiFetch(`${ADMIN_API}/tasks?page=${page}&search=${encodeURIComponent(search)}&user_search=${encodeURIComponent(userSearch)}&filter=${filter}`);
      if (!r.ok) return;
      const data = await r.json();
      renderAdminTasksTable(allTasksTable, data.tasks || []);
      renderAdminTasksPagination(data.pagination);
    } catch (_e) {}
  }

  function renderAdminTasksTable(container, tasks) {
    if (!container) return;
    container.innerHTML = '';
    
    if (tasks.length === 0) {
      container.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 40px; color: rgba(255,255,255,0.5);">No tasks found</td></tr>';
      return;
    }

    tasks.forEach(task => {
      const tr = document.createElement('tr');
      const isPublic = task.visibility === 'public';
      const isCompleted = task.completed;
      const hasPlaylist = !!task.playlistName;
      
      tr.innerHTML = `
        <td data-label="Task Content">
          <div style="font-weight: 600; color: #fff; margin-bottom: 4px;">${task.text}</div>
          ${task.videoUrl ? `<div style="font-size: 0.75rem; color: #3b82f6; word-break: break-all;"><i class="fab fa-youtube"></i> ${task.videoUrl}</div>` : ''}
        </td>
        <td data-label="Owner">
          <div style="font-size: 0.85rem; color: rgba(255,255,255,0.8);">${task.ownerEmail || 'Unknown'}</div>
        </td>
        <td data-label="Playlist & Visibility">
          ${hasPlaylist ? `<span class="admin-badge" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-color: rgba(59, 130, 246, 0.3);"><i class="fas fa-list"></i> ${task.playlistName}</span>` : ''}
          <span class="admin-badge" style="margin-top: 4px; cursor: pointer; background: ${isPublic ? 'rgba(16, 185, 129, 0.1)' : 'rgba(255, 255, 255, 0.1)'}; color: ${isPublic ? '#10b981' : '#fff'}; border-color: ${isPublic ? 'rgba(16, 185, 129, 0.3)' : 'rgba(255, 255, 255, 0.2)'};" onclick="window.adminToggleTaskVisibility('${task.id}', '${isPublic ? 'private' : 'public'}')">
            <i class="fas ${isPublic ? 'fa-globe' : 'fa-lock'}"></i> ${isPublic ? 'Public' : 'Private'}
          </span>
        </td>
        <td data-label="Status">
          <span class="admin-badge" style="cursor: pointer; background: ${isCompleted ? 'rgba(16, 185, 129, 0.1)' : 'rgba(238, 131, 49, 0.1)'}; color: ${isCompleted ? '#10b981' : '#ee8331'}; border-color: ${isCompleted ? 'rgba(16, 185, 129, 0.3)' : 'rgba(238, 131, 49, 0.3)'};" onclick="window.adminToggleTaskCompletion('${task.id}', ${!isCompleted})">
            <i class="fas ${isCompleted ? 'fa-check-circle' : 'fa-circle'}"></i> ${isCompleted ? 'Completed' : 'Active'}
          </span>
        </td>
        <td data-label="Actions" class="admin-actions">
          <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button class="action-btn" title="Edit Task" onclick="window.adminEditTask('${task.id}', \`${task.text.replace(/`/g, '\\`')}\`)">
              <i class="fas fa-edit"></i>
            </button>
            ${task.videoUrl ? `
            <button class="action-btn" style="color: #10b981;" title="Refresh YouTube Data" onclick="window.adminRefreshYoutubeTask('${task.id}')">
              <i class="fas fa-sync-alt"></i>
            </button>
            <button class="action-btn" style="color: #f59e0b;" title="Video Tools" onclick="window.adminShowVideoTools('${task.id}', '${task.videoUrl}', '${task.captionPath || ''}')">
              <i class="fas fa-video"></i>
            </button>
            ` : ''}
            <button class="action-btn danger" title="Delete Task" onclick="window.adminDeleteTask('${task.id}')">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      `;
      container.appendChild(tr);
    });
  }

  function renderAdminTasksPagination(p) {
    if (!adminTasksPagination || !p) return;
    adminTasksPagination.innerHTML = '';
    
    if (p.pages <= 1) return;
    
    // Add container styling for better visual alignment
    adminTasksPagination.style.display = 'flex';
    adminTasksPagination.style.flexWrap = 'wrap';
    adminTasksPagination.style.gap = '8px';
    adminTasksPagination.style.justifyContent = 'center';
    adminTasksPagination.style.marginTop = '20px';
    
    for (let i = 1; i <= p.pages; i++) {
      const btn = document.createElement('button');
      btn.textContent = i;
      // Using action-btn styling to match the dark theme nicely
      btn.className = `action-btn ${i === p.page ? 'active' : ''}`;
      
      // Override active state specifically for pagination
      if (i === p.page) {
        btn.style.background = '#ee8331';
        btn.style.color = '#fff';
        btn.style.borderColor = '#ee8331';
      }
      
      btn.style.minWidth = '36px';
      btn.style.height = '36px';
      btn.style.display = 'flex';
      btn.style.alignItems = 'center';
      btn.style.justifyContent = 'center';
      btn.style.fontWeight = '600';
      btn.style.borderRadius = '8px';
      
      btn.onclick = () => loadAllTasks(i);
      adminTasksPagination.appendChild(btn);
    }
  }

  window.adminToggleTaskVisibility = async (id, visibility) => {
    const r = await apiFetch(`${ADMIN_API}/tasks/${id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ visibility })
    });
    if (r.ok) loadAllTasks();
  };

  window.adminToggleTaskCompletion = async (id, completed) => {
    const r = await apiFetch(`${ADMIN_API}/tasks/${id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ completed })
    });
    if (r.ok) loadAllTasks();
  };

  window.adminEditTask = async (id, oldText) => {
    const newText = await showCustomPrompt('Edit Task', 'Update task content:', oldText);
    if (newText === null || newText.trim() === '' || newText === oldText) return;
    
    const r = await apiFetch(`${ADMIN_API}/tasks/${id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ text: newText.trim() })
    });
    if (r.ok) {
      showFlash('Task updated successfully', 'success');
      loadAllTasks();
    }
  };

  window.adminDeleteTask = async (id) => {
    const confirmed = await showCustomConfirm('Delete Task', 'Are you sure you want to delete this task?', { confirmVariant: 'danger' });
    if (!confirmed) return;
    
    const r = await apiFetch(`${ADMIN_API}/tasks/${id}`, { method: 'DELETE' });
    if (r.ok) {
      showFlash('Task deleted', 'success');
      loadAllTasks();
    }
  };

  window.adminAddNewTask = async () => {
    const text = await showCustomPrompt('Add New Task', 'Enter task content:');
    if (!text || text.trim() === '') return;
    
    const r = await apiFetch(API_BASE, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ text: text.trim(), visibility: 'public' })
    });
    
    if (r.ok) {
      showFlash('Task added successfully', 'success');
      loadAllTasks();
    } else {
      showFlash('Failed to add task', 'error');
    }
  };

  window.adminRefreshYoutubeTask = async (id) => {
    showPageLoader('Refreshing YouTube data...');
    try {
      const r = await apiFetch(`${ADMIN_API}/tasks/${id}/youtube-refresh`, { method: 'POST' });
      if (r.ok) {
        const data = await r.json();
        let msg = 'YouTube data refreshed';
        if (data.tags && data.tags.length > 0) {
          msg += `\n\nTags found: ${data.tags.join(', ')}`;
        } else {
          msg += '\n\nNo tags found for this video.';
        }
        showFlash(msg, 'success');
        loadAllTasks();
      } else {
        const err = await readResponseMessage(r, 'Failed to refresh YouTube data');
        showFlash(`Error: ${err}`, 'error');
      }
    } catch (e) {
      console.error('Refresh error:', e);
      showFlash(`Connection error: ${e.message || 'Unknown error'}`, 'error');
    } finally {
      hidePageLoader();
    }
  };

  window.adminShowVideoTools = async (id, videoUrl, captionPath) => {
    let videoId = '';
    const matches = {};
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/', videoUrl, matches)) {
        videoId = matches[1];
    }
    
    showPageLoader('Fetching video details...');
    let taskData = null;
    try {
      const r = await apiFetch(`${ADMIN_API}/tasks?search=${encodeURIComponent(id)}`);
      if (r.ok) {
        const result = await r.json();
        taskData = result.tasks.find(t => String(t.id) === String(id));
      } else {
        const err = await readResponseMessage(r, 'Failed to load task details');
        showFlash(`Error: ${err}`, 'error');
      }
    } catch (e) {
      console.error('Video tools fetch error:', e);
      showFlash(`Connection error: ${e.message || 'Unknown error'}`, 'error');
    }
    hidePageLoader();

    if (!taskData) {
      if (!flashStack.hasChildNodes()) {
        showFlash('Task details not found or failed to load.', 'error');
      }
      return;
    }

    const modalHtml = `
      <div class="video-tools-modal">
        <div class="tool-field">
          <label>Video Title</label>
          <div class="field-row">
            <input type="text" readonly value="${(taskData.text || '').replace(/"/g, '&quot;')}" class="pro-input">
            <button class="copy-btn" onclick="copyToClipboard('${(taskData.text || '').replace(/'/g, "\\'")}')" title="Copy Title">
              <i class="fas fa-copy"></i>
            </button>
          </div>
        </div>
        <div class="tool-field">
          <label>Tags</label>
          <div class="field-row">
            <input type="text" readonly value="${(taskData.tags || '').replace(/"/g, '&quot;')}" class="pro-input">
            <button class="copy-btn" onclick="copyToClipboard('${(taskData.tags || '').replace(/'/g, "\\'")}')" title="Copy Tags">
              <i class="fas fa-copy"></i>
            </button>
          </div>
        </div>
        <div class="tool-field">
          <label>Description</label>
          <div class="field-row">
            <textarea readonly class="pro-textarea">${taskData.description || ''}</textarea>
            <button class="copy-btn" onclick="copyToClipboard(\`${(taskData.description || '').replace(/`/g, '\\`').replace(/\$/g, '\\$')}\`)" title="Copy Description">
              <i class="fas fa-copy"></i>
            </button>
          </div>
        </div>
        <div class="modal-footer-tools">
          <button class="bulk-btn success" onclick="window.open('https://i.ytimg.com/vi/${videoId}/maxresdefault.jpg', '_blank')"><i class="fas fa-image"></i> Thumbnail</button>
          <button class="bulk-btn primary" onclick="window.open('/api/captions/${taskData.captionPath}/srt', '_blank')"><i class="fas fa-file-alt"></i> Download SRT</button>
          <button class="bulk-btn primary" onclick="window.open('/api/captions/${taskData.captionPath}/text', '_blank')"><i class="fas fa-file-alt"></i> Download Text</button>
        </div>
      </div>
    `;

    showCustomConfirm('Video Details & Tools', modalHtml, { 
      confirmLabel: 'Close', 
      confirmVariant: 'primary',
      cancelHidden: true,
      modalClass: 'modal-lg'
    });
  };

  window.copyToClipboard = (text) => {
    navigator.clipboard.writeText(text).then(() => {
      showFlash('Copied to clipboard!', 'success');
    }).catch(() => {
      showFlash('Failed to copy.', 'error');
    });
  };

  window.toggleDescription = (id) => {
    const desc = document.getElementById(`desc-${id}`);
    const btn = document.getElementById(`btn-desc-${id}`);
    if (!desc || !btn) return;
    
    const isCollapsed = desc.classList.contains('collapsed');
    if (isCollapsed) {
      desc.classList.remove('collapsed');
      btn.textContent = 'Show Less';
    } else {
      desc.classList.add('collapsed');
      btn.textContent = 'Read More';
    }
  };

  window.startEditNote = (taskId, noteId) => {
    document.getElementById(`note-text-${noteId}`)?.classList.add('app-hidden');
    document.getElementById(`note-edit-${noteId}`)?.classList.remove('app-hidden');

    // Initialize CKEditor 4 for inline editing
    const inputId = `note-input-${noteId}`;
    if (typeof CKEDITOR !== 'undefined' && !CKEDITOR.instances[inputId]) {
      CKEDITOR.replace(inputId, {
        height: 150,
        extraPlugins: 'colorbutton,font,justify',
        toolbar: [
          { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat' ] },
          { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
          { name: 'links', items: [ 'Link', 'Unlink' ] },
          { name: 'insert', items: [ 'Image', 'Table', 'HorizontalRule', 'SpecialChar' ] },
          { name: 'styles', items: [ 'Styles', 'Format', 'Font', 'FontSize' ] },
          { name: 'colors', items: [ 'TextColor', 'BGColor' ] },
          { name: 'tools', items: [ 'Maximize' ] }
        ],
        filebrowserUploadUrl: '/api/notes/upload-image',
        filebrowserUploadMethod: 'form',
        image_previewText: ' ', // Clear dummy text
        removeButtons: ''
      });
    }
  };

  window.cancelEditNote = (noteId) => {
    document.getElementById(`note-text-${noteId}`)?.classList.remove('app-hidden');
    document.getElementById(`note-edit-${noteId}`)?.classList.add('app-hidden');

    // Cleanup CKEditor 4
    const inputId = `note-input-${noteId}`;
    if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[inputId]) {
      CKEDITOR.instances[inputId].destroy();
    }
  };

  window.saveEditNote = async (taskId, noteId) => {
    const inputId = `note-input-${noteId}`;
    const vis = document.getElementById(`note-vis-${noteId}`);
    
    let text = '';
    if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[inputId]) {
      text = CKEDITOR.instances[inputId].getData().trim();
    } else {
      const input = document.getElementById(inputId);
      text = input?.value.trim();
    }

    if (!text) {
      showFlash('Note text cannot be empty', 'error');
      return;
    }
    const visibility = vis?.value || 'private';
    
    try {
      const r = await apiFetch(`${API_BASE}/${encodeURIComponent(taskId)}/notes/${noteId}`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ text, visibility })
      });
      if (!r.ok) {
        showFlash(await readResponseMessage(r, 'Could not update note'), 'error');
        return;
      }

      // Cleanup CKEditor 4
      if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances[inputId]) {
        CKEDITOR.instances[inputId].destroy();
      }

      const updated = await r.json();
      const notes = taskNotesCache.get(taskId) || [];
      const idx = notes.findIndex(n => n.id === noteId);
      if (idx !== -1) {
        notes[idx] = updated;
        taskNotesCache.set(taskId, notes);
      }
      const listEl = document.getElementById('modal-notes-list') || document.getElementById(`notes-list-${taskId}`);
      if (listEl) renderTaskNotesList(taskId, listEl);
      showFlash('Note updated', 'success');
    } catch (_e) {
      showFlash('Failed to update note', 'error');
    }
  };

  window.deleteTaskNote = async (taskId, noteId) => {
    if (!await showSubConfirm('Delete Note', 'Are you sure you want to delete this note? This action cannot be undone.')) {
      return;
    }
    try {
      const r = await apiFetch(`${API_BASE}/${encodeURIComponent(taskId)}/notes/${noteId}`, {
        method: 'DELETE'
      });
      if (!r.ok) {
        showFlash(await readResponseMessage(r, 'Could not delete note'), 'error');
        return;
      }
      const notes = taskNotesCache.get(taskId) || [];
      const nextNotes = notes.filter(n => n.id !== noteId);
      taskNotesCache.set(taskId, nextNotes);
      
      // Update task note count
      const task = tasks.find(t => t.id === taskId);
      if (task) {
        task.noteCount = Math.max(0, (task.noteCount || 0) - 1);
        renderTasks();
      }
      
      const listEl = document.getElementById('modal-notes-list');
      if (listEl) renderTaskNotesList(taskId, listEl);
      showFlash('Note deleted', 'success');
    } catch (_e) {
      showFlash('Failed to delete note', 'error');
    }
  };

  window.openNotesModal = async (taskId) => {
    const task = tasks.find(t => t.id === taskId);
    if (!task) return;

    const isPublicTask = task.visibility === 'public';
    
    const modalHtml = `
      <div class="notes-modal-container">
        <div class="notes-modal-header">
          <h3 class="notes-task-title">${task.text}</h3>
        </div>
        <div class="notes-modal-body">
          <div id="modal-notes-list" class="task-notes-list">
            <div class="loader-spinner" style="margin: 20px auto;"></div>
          </div>
        </div>
        <div class="notes-modal-footer">
          <div class="task-note-form">
            <textarea id="modal-note-input" class="task-note-input" rows="3" placeholder="Add a new note..."></textarea>
            <div class="task-note-row">
              <select id="modal-note-visibility" class="task-note-visibility">
                <option value="private" selected>Private note</option>
                <option value="public" ${isPublicTask ? 'disabled' : ''}>Public note</option>
              </select>
              <button id="modal-note-save-btn" class="task-note-save-btn" type="button">Save Note</button>
            </div>
            ${isPublicTask ? '<div class="task-note-hint">Public tasks only allow private notes.</div>' : ''}
          </div>
        </div>
      </div>
    `;

    // Show the modal
    const modalPromise = showCustomConfirm('Task Notes', modalHtml, { 
      confirmLabel: 'Close', 
      confirmVariant: 'primary',
      cancelHidden: true,
      modalClass: 'modal-lg'
    });

    modalPromise.then(async () => {
      // Cleanup CKEditor 4 after modal is closed
      if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['modal-note-input']) {
        CKEDITOR.instances['modal-note-input'].destroy();
      }
    });

    const listEl = document.getElementById('modal-notes-list');
    const inputEl = document.getElementById('modal-note-input');
    const visEl = document.getElementById('modal-note-visibility');
    const saveBtn = document.getElementById('modal-note-save-btn');

    // Load notes
    await loadTaskNotes(taskId, listEl);

    // Initialize CKEditor 4 for the modal input
    if (typeof CKEDITOR !== 'undefined' && !CKEDITOR.instances['modal-note-input']) {
      CKEDITOR.replace('modal-note-input', {
        height: 150,
        extraPlugins: 'colorbutton,font,justify',
        toolbar: [
          { name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat' ] },
          { name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock' ] },
          { name: 'links', items: [ 'Link', 'Unlink' ] },
          { name: 'insert', items: [ 'Image', 'Table', 'HorizontalRule', 'SpecialChar' ] },
          { name: 'styles', items: [ 'Styles', 'Format', 'Font', 'FontSize' ] },
          { name: 'colors', items: [ 'TextColor', 'BGColor' ] },
          { name: 'tools', items: [ 'Maximize' ] }
        ],
        filebrowserUploadUrl: '/api/notes/upload-image',
        filebrowserUploadMethod: 'form',
        image_previewText: ' ', // Clear dummy text
        removeButtons: ''
      });
    }

    // Handle save
    if (saveBtn && inputEl && visEl) {
      saveBtn.addEventListener('click', async () => {
        let text = '';
        if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['modal-note-input']) {
          text = CKEDITOR.instances['modal-note-input'].getData().trim();
        } else {
          text = inputEl.value.trim();
        }

        if (!text) {
          showFlash('Write a note first', 'error');
          return;
        }
        const requestedVisibility = isPublicTask ? 'private' : (visEl.value || 'private');
        saveBtn.disabled = true;
        saveBtn.classList.add('is-loading');
        try {
          const response = await apiFetch(`${API_BASE}/${encodeURIComponent(taskId)}/notes`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ text, visibility: requestedVisibility })
          });
          if (!response.ok) {
            showFlash(await readResponseMessage(response, 'Could not save note'), 'error');
            return;
          }
          const saved = await response.json();
          const currentNotes = taskNotesCache.get(taskId) || [];
          const nextNotes = [saved, ...currentNotes];
          taskNotesCache.set(taskId, nextNotes);
          
          // Update task note count
          task.noteCount = (task.noteCount || 0) + 1;
          
          renderTaskNotesList(taskId, listEl);
          
          // Clear editor
          if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances['modal-note-input']) {
            CKEDITOR.instances['modal-note-input'].setData('');
          } else {
            inputEl.value = '';
          }

          showFlash('Note saved', 'success');
          
          // Re-render tasks in background to update badge
          renderTasks();
        } catch (_error) {
          showFlash('Could not save note', 'error');
        } finally {
          saveBtn.disabled = false;
          saveBtn.classList.remove('is-loading');
        }
      });
    }
  };

  // Helper for regex matching
  function preg_match(regex, str, matches) {
    const m = str.match(new RegExp(regex.replace(/^\/|\/[gimuy]*$/g, '')));
    if (m) {
      for (let i = 0; i < m.length; i++) matches[i] = m[i];
      return true;
    }
    return false;
  }

  function renderUsersTable(container, users) {
    if (!container) return;
    container.innerHTML = '';
    
    if (users.length === 0) {
      container.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 40px; color: rgba(255,255,255,0.5);">No users found</td></tr>';
      return;
    }

    users.forEach(user => {
      const tr = document.createElement('tr');
      const date = new Date(user.created_at).toLocaleDateString();
      const roleBadgeStyle = user.role === 'admin' ? 'background: rgba(238, 131, 49, 0.1); color: #ee8331; border-color: rgba(238, 131, 49, 0.3);' : 'background: rgba(255, 255, 255, 0.05); color: #fff; border-color: rgba(255, 255, 255, 0.1);';
      const statusBadgeStyle = user.status === 'suspended' ? 'background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.3);' : 'background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: rgba(16, 185, 129, 0.3);';
      const isSuspended = user.status === 'suspended';
      const isVerified = parseInt(user.is_verified || 0) === 1;
      const verifiedBadgeStyle = isVerified ? 'background: rgba(16, 185, 129, 0.1); color: #10b981; border-color: rgba(16, 185, 129, 0.3);' : 'background: rgba(245, 158, 11, 0.1); color: #f59e0b; border-color: rgba(245, 158, 11, 0.3);';
      const isGoogle = (user.auth_provider || 'email') === 'google';
      
      tr.innerHTML = `
        <td data-label="User">
          <div style="font-weight: 600; margin-bottom: 4px;">${user.name}</div>
          ${isGoogle ? `<div><span class="admin-badge" style="background: rgba(255, 255, 255, 0.1); color: #fff; border-color: rgba(255, 255, 255, 0.2);"><i class="fab fa-google" style="color: #ea4335;"></i> Google</span></div>` : ''}
        </td>
        <td data-label="Email & Status">
          <div style="color: rgba(255,255,255,0.8); margin-bottom: 4px;">${user.email}</div>
          <div>
            <span class="admin-badge" style="${verifiedBadgeStyle}">
              <i class="fas ${isVerified ? 'fa-check-circle' : 'fa-exclamation-triangle'}"></i> ${isVerified ? 'Verified' : 'Unverified'}
            </span>
          </div>
        </td>
        <td data-label="Role & Status">
          <span class="admin-badge" style="${roleBadgeStyle}">${user.role}</span>
          <span class="admin-badge" style="${statusBadgeStyle}">${user.status || 'active'}</span>
        </td>
        <td data-label="Joined"><div style="color: rgba(255,255,255,0.6);">${date}</div></td>
        <td data-label="Actions" class="admin-actions">
          <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button class="action-btn" title="Toggle Role (User/Admin)" onclick="window.updateUserRole(${user.id}, '${user.role === 'admin' ? 'user' : 'admin'}')">
              <i class="fas fa-user-shield"></i>
            </button>
            <button class="action-btn" style="color: ${isSuspended ? '#10b981' : '#f59e0b'};" title="${isSuspended ? 'Activate User' : 'Suspend User'}" onclick="window.updateUserStatus(${user.id}, '${isSuspended ? 'active' : 'suspended'}')">
              <i class="fas ${isSuspended ? 'fa-user-check' : 'fa-user-slash'}"></i>
            </button>
            ${!isVerified ? `
            <button class="action-btn" style="color: #10b981;" title="Verify User Manually" onclick="window.verifyUserManually(${user.id})">
              <i class="fas fa-check-double"></i>
            </button>
            ` : ''}
            <button class="action-btn danger" title="Delete User" onclick="window.deleteUser(${user.id})">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </td>
      `;
      container.appendChild(tr);
    });
  }

  window.verifyUserManually = async (id) => {
    const confirmed = await showCustomConfirm('Verify User', 'Manually verify this user email address?');
    if (!confirmed) return;
    
    const r = await apiFetch(`${ADMIN_USERS_API}/${id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ is_verified: 1 })
    });
    if (r.ok) {
      const data = await r.json();
      showFlash(data.message || 'User verified successfully', 'success');
      const activeSection = document.querySelector('.admin-nav-item.active').dataset.section;
      if (activeSection === 'users') loadAllUsers();
      else loadAdminDashboard();
    } else {
      showFlash(await readResponseMessage(r, 'Verification failed'), 'error');
    }
  };

  window.updateUserStatus = async (id, status) => {
    const action = status === 'suspended' ? 'Suspend' : 'Activate';
    const confirmed = await showCustomConfirm(`${action} User`, `Are you sure you want to ${action.toLowerCase()} this user?`);
    if (!confirmed) return;
    
    const r = await apiFetch(`${ADMIN_USERS_API}/${id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status })
    });
    if (r.ok) {
      const data = await r.json();
      showFlash(data.message || `User status changed to ${status}`, 'success');
      logActivity(`Admin Updated User Status: ${id} to ${status}`);
      const activeSection = document.querySelector('.admin-nav-item.active').dataset.section;
      if (activeSection === 'users') loadAllUsers();
      else loadAdminDashboard();
    } else {
      showFlash(await readResponseMessage(r, 'Status update failed'), 'error');
    }
  };

  window.updateUserRole = async (id, role) => {
    const confirmed = await showCustomConfirm('Update Role', `Change user role to ${role}?`);
    if (!confirmed) return;
    
    const r = await apiFetch(`${ADMIN_USERS_API}/${id}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ role })
    });
    if (r.ok) {
      const data = await r.json();
      showFlash(data.message || `User role updated to ${role}`, 'success');
      logActivity(`Admin Updated User Role: ${id} to ${role}`);
      if (isAdminPage) {
        const activeSection = document.querySelector('.admin-nav-item.active').dataset.section;
        if (activeSection === 'users') loadAllUsers();
        else loadAdminDashboard();
      }
    } else {
      showFlash(await readResponseMessage(r, 'Role update failed'), 'error');
    }
  };

  window.deleteUser = async (id) => {
    const confirmed = await showCustomConfirm('Delete User', 'Permanently delete this user and all their data?', { confirmVariant: 'danger' });
    if (!confirmed) return;
    
    const r = await apiFetch(`${ADMIN_USERS_API}/${id}`, { method: 'DELETE' });
    if (r.ok) {
      const data = await r.json();
      showFlash(data.message || 'User deleted', 'success');
      logActivity(`Admin Deleted User: ${id}`);
      if (isAdminPage) {
        const activeSection = document.querySelector('.admin-nav-item.active').dataset.section;
        if (activeSection === 'users') loadAllUsers();
        else loadAdminDashboard();
      }
    } else {
      showFlash(await readResponseMessage(r, 'User delete failed'), 'error');
    }
  };
  