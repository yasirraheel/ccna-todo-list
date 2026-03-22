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
      --primary-blue: #2563eb;
      --bg-dark: #000000;
      --card-bg: #111111;
      --text-main: #ffffff;
      --text-muted: #94a3b8;
      --sidebar-bg: #0a0a0a;
    }

    body.admin-layout {
      background-color: var(--bg-dark);
      color: var(--text-main);
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif !important;
    }

    .admin-sidebar {
      background: var(--sidebar-bg);
      border-right: 1px solid #222;
    }

    .admin-sidebar-logo {
      color: var(--primary-blue);
      font-weight: 800;
    }

    .admin-nav-item {
      color: var(--text-muted);
    }

    .admin-nav-item:hover, .admin-nav-item.active {
      background: #1e293b;
      color: var(--primary-blue);
    }

    .admin-header {
      background: var(--bg-dark);
      border-bottom: 1px solid #222;
    }

    .page-title {
      color: var(--primary-blue);
    }

    .stat-card {
      background: var(--card-bg);
      border: 1px solid #333;
    }

    .admin-table-card {
      background: var(--card-bg);
      border: 1px solid #333;
    }

    .admin-table th {
      background: #1e293b;
      color: var(--text-main);
      border-bottom: 1px solid #333;
    }

    .admin-table td {
      border-bottom: 1px solid #222;
      color: var(--text-main);
    }

    .admin-table tr:hover td {
      background: #1a1a1a;
    }

    .admin-settings-card {
      background: var(--card-bg);
      border: 1px solid #333;
    }

    input[type="text"], input[type="email"], input[type="password"], select, textarea {
      background: #1e293b !important;
      border: 1px solid #334155 !important;
      color: white !important;
    }

    .url-input-container code {
      background: #000;
      color: #10b981;
    }

    .admin-badge {
      background: #1e293b;
      color: var(--text-muted);
      border: 1px solid #333;
    }

    /* Override the Azonix font issue with system font */
    * {
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif !important;
    }
  </style>
</head>
<body class="admin-layout">
  <div id="page-loader" class="page-loader">
    <div class="loader-spinner"></div>
    <div class="loader-text">Loading admin panel...</div>
  </div>
  <div id="flash-stack" class="flash-stack"></div>

  <aside class="admin-sidebar">
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
      <h1 id="section-title" class="page-title">Dashboard</h1>
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
  <script src="script.js?v=<?php echo $assetVersion; ?>"></script>
</body>
</html>
