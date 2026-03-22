<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="todo-api-base" content="">
  <title>Admin Panel | <?php echo $appName; ?></title>
  <link rel="icon" href="data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQEAAAAMAtDSzAAAAAEElEQVR42mNkIAAYGBAAAQAA/wEAgP8AAAAASUVORK5CYII=">
  <link rel="stylesheet" href="style.css?v=<?php echo $assetVersion; ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <style>
    :root {
      --primary-orange: #ee8331;
      --bg-dark: #000000;
      --card-bg: rgba(255, 255, 255, 0.05);
      --text-main: #ffffff;
      --text-muted: rgba(255, 255, 255, 0.6);
      --sidebar-bg: rgba(255, 255, 255, 0.03);
      --border-color: rgba(255, 255, 255, 0.1);
    }

    body.admin-layout {
      background-color: var(--bg-dark);
      color: var(--text-main);
      font-family: 'Azonix', sans-serif !important;
      display: flex;
      min-height: 100vh;
      margin: 0;
      overflow-x: hidden;
    }

    /* Override for standard text elements to stay readable */
    body.admin-layout p, 
    body.admin-layout span, 
    body.admin-layout div:not(.page-title):not(.stat-value), 
    body.admin-layout td, 
    body.admin-layout th,
    body.admin-layout input,
    body.admin-layout select,
    body.admin-layout textarea {
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif !important;
    }

    .admin-sidebar {
      background: var(--sidebar-bg);
      border-right: 1px solid var(--border-color);
      width: 260px;
      display: flex;
      flex-direction: column;
      position: fixed;
      height: 100vh;
      z-index: 100;
      transition: transform 0.3s ease;
    }

    .admin-sidebar-header {
      padding: 24px;
      border-bottom: 1px solid var(--border-color);
    }

    .admin-sidebar-logo {
      color: var(--primary-orange);
      font-weight: 800;
      font-size: 1.2rem;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
      font-family: 'Azonix', sans-serif !important;
    }

    .admin-nav {
      padding: 20px 0;
      flex: 1;
    }

    .admin-nav-item {
      color: var(--text-muted);
      padding: 15px 24px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: all 0.2s;
      border-left: 3px solid transparent;
      font-weight: 500;
    }

    .admin-nav-item:hover, .admin-nav-item.active {
      background: rgba(238, 131, 49, 0.1);
      color: var(--primary-orange);
      border-left-color: var(--primary-orange);
    }

    .admin-sidebar-footer {
      padding: 20px 0;
      border-top: 1px solid var(--border-color);
    }

    .admin-main {
      flex: 1;
      margin-left: 260px;
      min-width: 0;
      display: flex;
      flex-direction: column;
      transition: margin-left 0.3s ease;
    }

    .admin-header {
      background: var(--bg-dark);
      border-bottom: 1px solid var(--border-color);
      padding: 20px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 90;
    }

    .mobile-menu-btn {
      display: none;
      background: none;
      border: none;
      color: var(--text-main);
      font-size: 1.5rem;
      cursor: pointer;
    }

    .page-title {
      color: var(--text-main);
      margin: 0;
      font-size: 1.5rem;
    }

    .admin-section {
      padding: 30px;
      display: none;
    }

    .admin-section.active {
      display: block;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 24px;
      margin-bottom: 30px;
    }

    .stat-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 24px;
      text-align: center;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
      border-color: rgba(255, 255, 255, 0.2);
    }

    .stat-label {
      color: var(--text-muted);
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 8px;
    }

    .stat-value {
      color: #fff;
      font-size: 2.5rem;
      font-weight: 700;
      line-height: 1;
    }

    .admin-table-container {
      overflow-x: auto;
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      margin-bottom: 30px;
    }

    .admin-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
    }

    .admin-table th, .admin-table td {
      padding: 15px 20px;
      white-space: nowrap;
    }

    .admin-table th {
      background: rgba(0, 0, 0, 0.3);
      color: var(--text-muted);
      font-weight: 600;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 1px;
      border-bottom: 1px solid var(--border-color);
    }

    .admin-table td {
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      color: var(--text-main);
    }

    .admin-table tr:last-child td {
      border-bottom: none;
    }

    .admin-table tr:hover td {
      background: rgba(255, 255, 255, 0.02);
    }

    .admin-settings-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 30px;
      max-width: 800px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      color: var(--text-muted);
    }

    input[type="text"], input[type="email"], input[type="password"], select, textarea {
      width: 100%;
      background: rgba(255, 255, 255, 0.05) !important;
      border: 1px solid var(--border-color) !important;
      color: white !important;
      padding: 10px 15px !important;
      border-radius: 8px !important;
      transition: all 0.2s;
    }

    input:focus, select:focus, textarea:focus {
      border-color: var(--primary-orange) !important;
      box-shadow: 0 0 0 3px rgba(238, 131, 49, 0.15) !important;
      outline: none;
    }

    .url-input-container {
      display: flex;
      align-items: center;
      background: rgba(0, 0, 0, 0.3);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      overflow: hidden;
    }

    .url-input-container code {
      background: rgba(255, 255, 255, 0.05);
      color: var(--text-muted);
      padding: 10px 15px;
      border-right: 1px solid var(--border-color);
    }

    .url-input-container input {
      border: none !important;
      background: transparent !important;
      border-radius: 0 !important;
    }

    .admin-badge {
      background: rgba(255, 255, 255, 0.1);
      color: var(--text-main);
      border: 1px solid var(--border-color);
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
    }
    
    .admin-badge.admin {
      background: rgba(238, 131, 49, 0.1);
      color: var(--primary-orange);
      border-color: rgba(238, 131, 49, 0.3);
    }

    .action-btn {
      background: rgba(255, 255, 255, 0.1);
      border: none;
      color: #fff;
      padding: 6px 12px;
      border-radius: 6px;
      cursor: pointer;
      transition: background 0.2s;
      font-size: 0.85rem;
    }

    .action-btn:hover {
      background: rgba(255, 255, 255, 0.2);
    }

    .action-btn.danger {
      color: #ef4444;
    }

    .action-btn.danger:hover {
      background: rgba(239, 68, 68, 0.2);
    }

    .btn-primary {
      background: var(--primary-orange);
      color: #fff;
      border: none;
      padding: 10px 24px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }

    .btn-primary:hover {
      background: #d9772c;
    }

    @media (max-width: 992px) {
      .admin-sidebar {
        transform: translateX(-100%);
      }
      .admin-sidebar.open {
        transform: translateX(0);
      }
      .admin-main {
        margin-left: 0;
      }
      .mobile-menu-btn {
        display: block;
      }
    }

    /* Page Loader Fix for Admin */
    .page-loader {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: var(--bg-dark);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      transition: opacity 0.3s ease;
    }
    .page-loader.app-hidden {
      display: none !important;
    }
    .loader-spinner {
      width: 50px;
      height: 50px;
      border: 4px solid rgba(238, 131, 49, 0.2);
      border-top-color: var(--primary-orange);
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-bottom: 20px;
    }
    .loader-text {
      color: var(--text-muted);
      font-size: 1.1rem;
      font-family: 'Azonix', sans-serif !important;
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body class="admin-layout">
  <div id="page-loader" class="page-loader">
    <div class="loader-spinner"></div>
    <div class="loader-text">Loading admin panel...</div>
  </div>
  <div id="flash-stack" class="flash-stack"></div>

  <aside class="admin-sidebar" id="admin-sidebar">
    <div class="admin-sidebar-header">
      <a href="/" class="admin-sidebar-logo">
        <i class="fas fa-shield-halved"></i>
        <span>Admin Panel</span>
      </a>
    </div>
    <nav class="admin-nav">
      <div class="admin-nav-item active" data-section="dashboard">
        <i class="fas fa-chart-line"></i>
        <span>Dashboard</span>
      </div>
      <div class="admin-nav-item" data-section="users">
        <i class="fas fa-users"></i>
        <span>Manage Users</span>
      </div>
      <div class="admin-nav-item" data-section="tasks">
        <i class="fas fa-tasks"></i>
        <span>Manage Tasks</span>
      </div>
      <div class="admin-nav-item" data-section="settings">
        <i class="fas fa-cog"></i>
        <span>Site Settings</span>
      </div>
    </nav>
    <div class="admin-sidebar-footer">
      <div class="admin-nav-item" id="admin-logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
      </div>
    </div>
  </aside>

  <main class="admin-main">
    <header class="admin-header">
      <div class="d-flex align-items-center gap-3">
        <button class="mobile-menu-btn" id="mobile-menu-btn">
          <i class="fas fa-bars"></i>
        </button>
        <h1 id="section-title" class="page-title">Dashboard</h1>
      </div>
      <div class="admin-user-info">
        <span id="admin-email" class="session-email text-muted"></span>
      </div>
    </header>

    <!-- Sections remain same as original but styled via CSS above -->
    <!-- Dashboard Section -->
    <section id="section-dashboard" class="admin-section active">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label"><i class="fas fa-users"></i> Total Users</div>
          <div id="admin-stat-users" class="stat-value">0</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><i class="fas fa-tasks"></i> Total Tasks</div>
          <div id="admin-stat-tasks" class="stat-value">0</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><i class="fas fa-globe"></i> Public Tasks</div>
          <div id="admin-stat-public" class="stat-value">0</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><i class="fas fa-sticky-note"></i> Total Notes</div>
          <div id="admin-stat-notes" class="stat-value">0</div>
        </div>
      </div>

      <h3 class="section-title" style="margin-top: 32px;">Recent Users</h3>
      <div class="admin-table-container">
        <div class="admin-table-card">
          <table class="admin-table">
            <thead>
              <tr>
                <th>User</th>
                <th>Email & Status</th>
                <th>Role</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="recent-users-table"></tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- Users Section -->
    <div id="section-users" class="admin-section">
      <div class="admin-table-container">
        <div class="admin-table-card">
          <table class="admin-table">
            <thead>
              <tr>
                <th>User</th>
                <th>Email & Status</th>
                <th>Role</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="all-users-table"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Tasks Section -->
    <div id="section-tasks" class="admin-section">
      <div class="admin-table-container">
        <div class="admin-table-card">
          <div style="padding: 20px; border-bottom: 1px solid #333; display: flex; justify-content: space-between; align-items: center; gap: 15px; flex-wrap: wrap;">
            <h3 class="section-title">All System Tasks</h3>
            <div style="display: flex; gap: 10px; flex: 1; justify-content: flex-end; min-width: 300px; flex-wrap: wrap;">
              <input type="text" id="admin-task-search" placeholder="Search tasks...">
              <input type="text" id="admin-task-user-search" placeholder="User Email...">
              <select id="admin-task-filter">
                <option value="all">All Visibility</option>
                <option value="public">Public Only</option>
                <option value="private">Private Only</option>
                <option value="playlist">Playlist Only</option>
              </select>
              <button onclick="window.adminAddNewTask()" class="bulk-btn success">
                <i class="fas fa-plus"></i> Add Task
              </button>
            </div>
          </div>
          <table class="admin-table">
            <thead>
              <tr>
                <th>Task Content</th>
                <th>Owner</th>
                <th>Playlist & Visibility</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="all-tasks-table"></tbody>
          </table>
          <div id="admin-tasks-pagination" style="padding: 20px; display: flex; justify-content: center; gap: 10px;"></div>
        </div>
      </div>
    </div>

    <!-- Settings Section -->
    <section id="section-settings" class="admin-section">
      <form id="admin-settings-form" class="auth-form">
        <div class="admin-settings-grid">
          <div class="admin-settings-card">
            <h3 class="section-title">General Settings</h3>
            <div class="auth-field">
              <label class="auth-label">App Name</label>
              <input type="text" name="APP_NAME" placeholder="Team Hifsa">
            </div>
            <div class="auth-field">
              <label class="auth-label">App Description</label>
              <input type="text" name="APP_DESCRIPTION">
            </div>
            <div class="auth-field">
              <label class="auth-label">Footer Text</label>
              <input type="text" name="FOOTER_TEXT">
            </div>
          </div>

          <div class="admin-settings-card">
            <h3 class="section-title">SMTP Settings</h3>
            <div class="auth-field"><label class="auth-label">Host</label><input type="text" name="SMTP_HOST"></div>
            <div class="auth-field"><label class="auth-label">Port</label><input type="text" name="SMTP_PORT"></div>
            <div class="auth-field"><label class="auth-label">User</label><input type="text" name="SMTP_USER"></div>
            <div class="auth-field"><label class="auth-label">Pass</label><input type="password" name="SMTP_PASS"></div>
          </div>

          <div class="admin-settings-card admin-settings-full">
            <button type="submit" class="playlist-btn">Save All Settings</button>
          </div>
        </div>
      </form>
    </section>
  </main>

  <div id="custom-modal" class="modal-overlay">
    <div class="modal-content">
      <h3 id="modal-title" class="modal-title">Confirm Action</h3>
      <div id="modal-message" class="modal-message">Are you sure you want to proceed?</div>
      <input type="text" id="modal-input" class="modal-input app-hidden">
      <div class="modal-actions">
        <button id="modal-cancel" class="modal-btn modal-btn-cancel">Cancel</button>
        <button id="modal-confirm" class="modal-btn modal-btn-primary">Confirm</button>
      </div>
    </div>
  </div>

  <div id="sub-modal" class="modal-overlay sub-modal">
    <div class="modal-content">
      <h3 id="sub-modal-title" class="modal-title">Confirm Action</h3>
      <div id="sub-modal-message" class="modal-message">Are you sure you want to proceed?</div>
      <div class="modal-actions">
        <button id="sub-modal-cancel" class="modal-btn modal-btn-cancel">Cancel</button>
        <button id="sub-modal-confirm" class="modal-btn modal-btn-primary">Confirm</button>
      </div>
    </div>
  </div>

  <script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js'></script>
  <script>
    const assetVersion = '<?php echo $assetVersion; ?>';
    const isAdminPage = true;
    
    // Mobile Sidebar Toggle
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const adminSidebar = document.getElementById('admin-sidebar');
    
    if (mobileMenuBtn && adminSidebar) {
      mobileMenuBtn.addEventListener('click', () => {
        adminSidebar.classList.toggle('open');
      });
      
      // Close sidebar when clicking outside on mobile
      document.addEventListener('click', (e) => {
        if (window.innerWidth <= 992) {
          if (!adminSidebar.contains(e.target) && !mobileMenuBtn.contains(e.target) && adminSidebar.classList.contains('open')) {
            adminSidebar.classList.remove('open');
          }
        }
      });
    }
  </script>
  <script src="script.js?v=<?php echo $assetVersion; ?>"></script>
</body>
</html>
