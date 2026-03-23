document.addEventListener('DOMContentLoaded', () => {
    if (isAdminPage) {
        if (typeof adminInit === 'function') {
            adminInit().catch(() => {
                hidePageLoader();
            });
        }
    }
});
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

  window.adminInit = async function adminInit() {
    console.log('Initializing admin application...');
    try {
      if (typeof resolveApiBase === 'function') await resolveApiBase();
      
      const authed = typeof authenticateWithStoredToken === 'function' 
        ? await authenticateWithStoredToken() 
        : false;
        
      if (!authed) {
        if (typeof hidePageLoader === 'function') hidePageLoader();
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
        if (typeof loadAdminDashboard === 'function') {
            await loadAdminDashboard();
        }
      } catch (err) {
        console.error('Failed to load admin dashboard data:', err);
        showFlash('Failed to load admin dashboard', 'error');
      } finally {
        if (typeof hidePageLoader === 'function') {
            hidePageLoader(); // Always hide loader, even if it fails
        }
      }


      
      // Setup Admin Navigation
      if (adminNavItems) {
        adminNavItems.forEach(item => {
          item.addEventListener('click', () => {
            const section = item.dataset.section;
            window.location.hash = section;
            switchAdminSection(section);
          });
        });
      }

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
      if (typeof hidePageLoader === 'function') {
        hidePageLoader();
      }
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
    if (adminNavItems) {
      adminNavItems.forEach(i => i.classList.toggle('active', i.dataset.section === sectionId));
    }
    if (adminSections) {
      adminSections.forEach(s => s.classList.toggle('active', s.id === `section-${sectionId}`));
    }
    
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
  async function init() {
    console.log('Initializing application...');
    logActivity('Page Initialized');
    try {
      await resolveApiBase();
      console.log('API Base resolved:', API_BASE);
      const authed = await authenticateWithStoredToken();
      console.log('Authentication status:', authed);
      if (!authed) {
        hidePageLoader();
        return;
      }
      if (isLoginPage) {
        hidePageLoader();
        return;
      }
      
      await loadPlaylists();
      await loadSelectedPlaylistPreference();
      syncInitialPageSize();
      showPageLoader('Loading workspace...');
      await loadTasks();
      console.log('Tasks loaded:', tasks.length);
      renderTasks();
      restoreScrollPosition();

      if (otpForm) {
        otpForm.addEventListener('submit', async (e) => {
          e.preventDefault();
          await verifyOtp();
        });
      }

      if (scrollTopBtn) {
        let ticking = false;
        const updateScrollButton = () => {
          if (window.scrollY > 300) {
            scrollTopBtn.classList.add('visible');
          } else {
            scrollTopBtn.classList.remove('visible');
          }
        };

        const saveScrollPosition = () => {
          localStorage.setItem(SCROLL_POSITION_KEY, window.scrollY);
        };

        const onScroll = () => {
          if (ticking) return;
          ticking = true;
          window.requestAnimationFrame(() => {
            updateScrollButton();
            saveScrollPosition();
            ticking = false;
          });
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        scrollTopBtn.addEventListener('click', () => {
          window.scrollTo({ top: 0, behavior: 'smooth' });
        });
        updateScrollButton();
      }
    } catch (err) {
      console.error('Init Error:', err);
      showFlash('Failed to load workspace', 'error');
    } finally {
      hidePageLoader();
    }
  }

  function restoreScrollPosition() {
    const saved = localStorage.getItem(SCROLL_POSITION_KEY);
    if (saved) {
      const pos = parseInt(saved, 10);
      if (!isNaN(pos)) {
        // Use requestAnimationFrame to ensure rendering is complete
        requestAnimationFrame(() => {
          window.scrollTo(0, pos);
        });
      }
    }
  }

  function showPageLoader(text = 'Loading...') {
    if (!pageLoader) return;
    const textEl = pageLoader.querySelector('.loader-text');
    if (textEl) textEl.textContent = text;
    pageLoader.classList.remove('app-hidden');
  }

  function hidePageLoader() {
    if (!pageLoader) return;
    pageLoader.classList.add('app-hidden');
  }

  async function loadTasks() {
    try {
      const q = currentPlaylistFilter && currentPlaylistFilter !== 'all' ? `?playlist=${encodeURIComponent(currentPlaylistFilter)}` : '';
      const base = currentScope === 'public' ? PUBLIC_TASKS_API : API_BASE;
      const response = await apiFetch(`${base}${q}`);
      if (!response.ok) {
        showFlash(await readResponseMessage(response, 'Could not load tasks'), 'error');
        hidePageLoader();
        return;
      }
      tasks = await response.json();
      taskNotesCache.clear();
      syncInitialPageSize();
      if (currentScope === 'public') {
        selectedTaskIds.clear();
      }
      updateDashboard();
    } catch (_error) {
      tasks = [];
      showFlash('Could not connect to task service', 'error');
      hidePageLoader();
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
      logActivity(`Created Task: ${createdTask.text.substring(0, 30)}...`);
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
        logActivity(`Deleted Task: ${id}`);
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
    
    // Apply playlist filter
    if (currentPlaylistFilter && currentPlaylistFilter !== 'all') {
      filteredTasks = filteredTasks.filter(t => (t.playlistName || '').toLowerCase() === currentPlaylistFilter.toLowerCase());
    }

    // Apply status filter
    if (currentFilter === 'active') filteredTasks = filteredTasks.filter(t => !t.completed);
    if (currentFilter === 'completed') filteredTasks = filteredTasks.filter(t => t.completed);
    if (currentFilter === 'has-notes') filteredTasks = filteredTasks.filter(t => Boolean(t?.noteCount > 0));

    // Apply search query
    if (currentSearchQuery) {
      filteredTasks = filteredTasks.filter(t => {
        const text = (t.text || '').toLowerCase();
        const desc = (t.description || '').toLowerCase();
        const playlist = (t.playlistName || '').toLowerCase();
        const tags = (t.tags || '').toLowerCase();
        return text.includes(currentSearchQuery) || 
               desc.includes(currentSearchQuery) || 
               playlist.includes(currentSearchQuery) || 
               tags.includes(currentSearchQuery);
      });
    }
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

  function formatNoteTime(value) {
    const ts = Number(value || 0);
    if (!ts) return '';
    try {
      return new Date(ts).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
    } catch (_error) {
      return '';
    }
  }

  window.toggleNoteExpand = (noteId) => {
    const el = document.getElementById(`note-text-${noteId}`);
    const btn = document.getElementById(`note-expand-btn-${noteId}`);
    if (!el || !btn) return;
    const isCollapsed = el.classList.contains('collapsed');
    if (isCollapsed) {
      el.classList.remove('collapsed');
      btn.innerHTML = '<i class="fas fa-chevron-up"></i> Show Less';
    } else {
      el.classList.add('collapsed');
      btn.innerHTML = '<i class="fas fa-chevron-down"></i> Read More';
    }
  };

  function renderTaskNotesList(taskId, listEl) {
    if (!listEl) return;
    const notes = taskNotesCache.get(taskId) || [];
    if (notes.length === 0) {
      listEl.innerHTML = '<div class="task-note-empty">No notes yet.</div>';
      return;
    }
    listEl.innerHTML = notes.map(note => {
      const when = formatNoteTime(note.updatedAt || note.createdAt);
      const tone = note.visibility === 'public' ? 'public' : 'private';
      const owner = note.isOwn ? 'You' : 'Shared';
      const visibilityLabel = note.visibility === 'public' ? 'Public' : 'Private';
      
      const actionsHtml = note.isOwn ? `
        <div class="task-note-actions">
          <button class="note-action-btn edit" onclick="window.startEditNote('${taskId}', ${note.id})"><i class="fas fa-edit"></i></button>
          <button class="note-action-btn delete" onclick="window.deleteTaskNote('${taskId}', ${note.id})"><i class="fas fa-trash"></i></button>
        </div>
      ` : '';

      const noteText = String(note.text || '');
      // Determine if note is long enough to need a "Read More" button
      // We'll use a rough character count or just always provide it if it's over a certain length
      const isLong = noteText.length > 300 || (noteText.match(/<p>/g) || []).length > 3 || noteText.includes('<ul>') || noteText.includes('<ol>');

      return `
        <div class="task-note-item ${tone}" id="note-${note.id}">
          <div class="task-note-head">
            <div class="task-note-info">
              <span class="task-note-owner">${owner}</span>
              <span class="task-note-visibility">${visibilityLabel}</span>
              ${when ? `<span class="task-note-time">${when}</span>` : ''}
            </div>
            ${actionsHtml}
          </div>
          <div class="task-note-text ck-content ${isLong ? 'collapsed' : ''}" id="note-text-${note.id}">${noteText}</div>
          ${isLong ? `<button class="note-expand-btn" id="note-expand-btn-${note.id}" onclick="window.toggleNoteExpand(${note.id})"><i class="fas fa-chevron-down"></i> Read More</button>` : ''}
          <div class="task-note-edit-box app-hidden" id="note-edit-${note.id}">
            <textarea class="task-note-input edit-input" id="note-input-${note.id}">${noteText}</textarea>
            <div class="task-note-row" style="margin-top: 10px;">
              <select class="task-note-visibility edit-vis" id="note-vis-${note.id}">
                <option value="private" ${note.visibility === 'private' ? 'selected' : ''}>Private</option>
                <option value="public" ${note.visibility === 'public' ? 'selected' : ''}>Public</option>
              </select>
              <div style="display: flex; gap: 5px;">
                <button class="bulk-btn" onclick="window.cancelEditNote(${note.id})" style="padding: 4px 8px; font-size: 0.7rem;">Cancel</button>
                <button class="task-note-save-btn" onclick="window.saveEditNote('${taskId}', ${note.id})" style="min-height: 28px; padding: 0 8px;">Save</button>
              </div>
            </div>
          </div>
        </div>
      `;
    }).join('');
  }

  async function loadTaskNotes(taskId, listEl) {
    if (!taskId || taskNotesLoading.has(taskId)) return;
    taskNotesLoading.add(taskId);
    if (listEl) listEl.innerHTML = '<div class="task-note-empty">Loading notes...</div>';
    try {
      const response = await apiFetch(`${API_BASE}/${encodeURIComponent(taskId)}/notes`);
      if (!response.ok) {
        if (listEl) listEl.innerHTML = '<div class="task-note-empty">Could not load notes.</div>';
        return;
      }
      const notes = await response.json();
      taskNotesCache.set(taskId, Array.isArray(notes) ? notes : []);
      renderTaskNotesList(taskId, listEl);
    } catch (_error) {
      if (listEl) listEl.innerHTML = '<div class="task-note-empty">Could not load notes.</div>';
    } finally {
      taskNotesLoading.delete(taskId);
    }
  }

  function renderTasks() {
    if (!taskList) return;
    taskList.innerHTML = '';
    
    const filteredTasks = getFilteredTasks();

    const visibleTasks = filteredTasks.slice(0, visibleTaskCount);
    updateBulkActionState(visibleTasks);
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
    visibleTasks.forEach(task => {
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

      li.innerHTML = `
        <div class="task-thumbnail-wrapper ${watchUrl ? 'task-open-link' : ''}">
          <div class="task-select-wrap">
            <input type="checkbox" class="task-checkbox" data-id="${task.id}" ${selectedTaskIds.has(task.id) ? 'checked' : ''}>
          </div>
          ${thumbnailHtml}
          <div class="task-priority-badge">${task.priority}</div>
        </div>
        <div class="task-body">
          <div class="task-content ${watchUrl ? 'task-open-link' : ''}" title="${task.text}">${task.text}</div>
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
              <button class="task-note-toggle-btn ${task.noteCount > 0 ? 'has-notes' : ''}" title="Notes" onclick="window.openNotesModal('${task.id}')">
                <i class="far fa-note-sticky"></i> Notes ${task.noteCount > 0 ? `<span class="note-badge">${task.noteCount}</span>` : ''}
              </button>
              ${watchUrl ? `<a href="${watchUrl}" target="_blank" class="watch-btn" title="Watch Video"><i class="fab fa-youtube"></i> Watch</a>` : ''}
              ${downloadSubUrl ? `<a href="${downloadSubUrl}" target="_blank" class="sub-btn" title="Download Subtitles" download><i class="fas fa-closed-captioning"></i> Subs</a>` : ''}
              ${canDelete ? `<button class="delete-btn" title="Delete Task"><i class="fas fa-trash-can"></i></button>` : ''}
            </div>
          </div>
        </div>
      `;

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

      if (watchUrl) {
        const titleEl = li.querySelector('.task-content');
        const thumbEl = li.querySelector('.task-thumbnail-wrapper');
        const openVideo = (e) => {
          e.stopPropagation();
          trackTaskView(task.id).catch(() => {});
          window.open(watchUrl, '_blank');
        };
        if (titleEl) titleEl.addEventListener('click', openVideo);
        if (thumbEl) thumbEl.addEventListener('click', openVideo);
      }

      // Bulk select checkbox logic
      const selectCheckbox = li.querySelector('.task-checkbox');
      if (selectCheckbox) {
        // Stop click from bubbling to prevent card-level redirection
        selectCheckbox.addEventListener('click', (e) => {
          e.stopPropagation();
        });
        
        selectCheckbox.addEventListener('change', (e) => {
          e.stopPropagation();
          const isChecked = e.target.checked;
          
          if (isChecked) {
            selectedTaskIds.add(task.id);
            li.classList.add('selected-for-delete');
          } else {
            selectedTaskIds.delete(task.id);
            li.classList.remove('selected-for-delete');
          }
          const filteredTasks = getFilteredTasks();
          const visibleTasks = filteredTasks.slice(0, visibleTaskCount);
          updateBulkActionState(visibleTasks);
        });
      }

      taskList.appendChild(li);
    });

    if (loadMoreBtn) {
      const hasMore = filteredTasks.length > visibleTasks.length;
      loadMoreBtn.style.display = hasMore ? 'inline-flex' : 'none';
      loadMoreBtn.classList.remove('is-loading');
      loadMoreBtn.disabled = false;
      loadMoreBtn.textContent = hasMore
        ? `Load More (${filteredTasks.length - visibleTasks.length} remaining)`
        : 'Load More';
    }
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
    const confirmText = String(options.confirmText || options.confirmLabel || 'Confirm');
    const confirmVariant = options.confirmVariant === 'primary' ? 'primary' : 'danger';
    const modalClass = options.modalClass || '';
    const cancelHidden = options.cancelHidden || false;

    return new Promise((resolve) => {
      modalTitle.textContent = title;
      if (message.includes('<') && message.includes('>')) {
        modalMessage.innerHTML = message;
      } else {
        modalMessage.textContent = message;
      }
      modalConfirm.textContent = confirmText;
      modalConfirm.classList.remove('modal-btn-primary', 'modal-btn-danger');
      modalConfirm.classList.add(confirmVariant === 'primary' ? 'modal-btn-primary' : 'modal-btn-danger');
      
      const modalContent = customModal.querySelector('.modal-content');
      if (modalContent) {
        modalContent.className = 'modal-content ' + modalClass;
      }

      if (modalInput) {
        modalInput.classList.add('app-hidden');
        modalInput.value = '';
      }

      if (cancelHidden) {
        modalCancel.classList.add('app-hidden');
      } else {
        modalCancel.classList.remove('app-hidden');
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

  function showSubConfirm(title, message, options = {}) {
    if (!subModal || !subModalTitle || !subModalMessage || !subModalCancel || !subModalConfirm) {
      return Promise.resolve(false);
    }
    const confirmText = String(options.confirmText || options.confirmLabel || 'Confirm');
    const confirmVariant = options.confirmVariant === 'primary' ? 'primary' : 'danger';

    return new Promise((resolve) => {
      subModalTitle.textContent = title;
      subModalMessage.textContent = message;
      subModalConfirm.textContent = confirmText;
      subModalConfirm.classList.remove('modal-btn-primary', 'modal-btn-danger');
      subModalConfirm.classList.add(confirmVariant === 'primary' ? 'modal-btn-primary' : 'modal-btn-danger');
      
      subModal.classList.add('active');
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
        subModal.classList.remove('active');
        subModalConfirm.removeEventListener('click', onConfirm);
        subModalCancel.removeEventListener('click', onCancel);
        document.removeEventListener('keydown', onEscape);
      };
      subModalConfirm.addEventListener('click', onConfirm);
      subModalCancel.addEventListener('click', onCancel);
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

