<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="todo-api-base" content="">
  <title>Admin Panel | Team Hifsa</title>
  <link rel="icon" href="data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQEAAAAMAtDSzAAAAAEElEQVR42mNkIAAYGBAAAQAA/wEAgP8AAAAASUVORK5CYII=">
  <link rel="stylesheet" href="style.css?v=20260317-45">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <span id="admin-email" class="session-email"></span>
      </div>
    </header>

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
            <tbody id="recent-users-table">
              <!-- Dynamic content -->
            </tbody>
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
          <div style="padding: 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; gap: 15px; flex-wrap: wrap;">
            <h3 class="section-title">All System Tasks</h3>
            <div style="display: flex; gap: 10px; flex: 1; justify-content: flex-end; min-width: 300px;">
              <input type="text" id="admin-task-search" placeholder="Search tasks..." style="padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; width: 200px; flex: 1;">
              <select id="admin-task-filter" style="padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px;">
                <option value="all">All Tasks</option>
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
          <!-- General Settings -->
          <div class="admin-settings-card">
            <h3 class="section-title">General Settings</h3>
            <div class="auth-field">
              <label class="auth-label">App Name</label>
              <input type="text" name="APP_NAME" placeholder="Team Hifsa">
            </div>
            <div class="auth-field">
              <label class="auth-label">App Description</label>
              <input type="text" name="APP_DESCRIPTION" placeholder="Task management for learners">
            </div>
            <div class="auth-field">
              <label class="auth-label">Footer Text</label>
              <input type="text" name="FOOTER_TEXT" placeholder="© 2026 Team Hifsa">
            </div>
            <div class="auth-field">
              <label class="auth-label">Logo</label>
              <div style="display: flex; gap: 15px; align-items: center; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div id="logo-preview-container" style="width: 48px; height: 48px; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                  <i class="fas fa-image" style="color: #94a3b8;"></i>
                </div>
                <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                  <input type="file" name="LOGO_FILE" accept="image/*">
                  <input type="text" name="LOGO_URL" placeholder="Logo URL (fallback)" style="font-size: 0.8rem; padding: 6px 10px;">
                </div>
              </div>
            </div>
            <div class="auth-field">
              <label class="auth-label">Favicon</label>
              <div style="display: flex; gap: 15px; align-items: center; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div id="favicon-preview-container" style="width: 48px; height: 48px; border-radius: 6px; background: #fff; border: 1px solid #cbd5e1; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                  <i class="fas fa-icons" style="color: #94a3b8;"></i>
                </div>
                <div style="flex: 1; display: flex; flex-direction: column; gap: 8px;">
                  <input type="file" name="FAVICON_FILE" accept="image/*">
                  <input type="text" name="FAVICON_URL" placeholder="Favicon URL (fallback)" style="font-size: 0.8rem; padding: 6px 10px;">
                </div>
              </div>
            </div>
          </div>

          <!-- SMTP Settings -->
          <div class="admin-settings-card">
            <h3 class="section-title">SMTP Settings</h3>
            <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 16px;">Configure SMTP to send verification emails to new users.</p>
            <div class="auth-field">
              <label class="auth-label">SMTP Host</label>
              <input type="text" name="SMTP_HOST" placeholder="smtp.gmail.com">
            </div>
            <div class="auth-field">
              <label class="auth-label">SMTP Port</label>
              <input type="text" name="SMTP_PORT" placeholder="587">
            </div>
            <div class="auth-field">
              <label class="auth-label">SMTP User</label>
              <input type="text" name="SMTP_USER" placeholder="your-email@gmail.com">
            </div>
            <div class="auth-field">
              <label class="auth-label">SMTP Pass</label>
              <input type="password" name="SMTP_PASS" placeholder="Your app password">
            </div>
            <div class="auth-field">
              <label class="auth-label">From Email</label>
              <input type="email" name="SMTP_FROM_EMAIL" placeholder="noreply@teamhifsa.com">
            </div>
            <div class="auth-field">
              <label class="auth-label">From Name</label>
              <input type="text" name="SMTP_FROM_NAME" placeholder="Team Hifsa Admin">
            </div>
            <div class="auth-field">
              <label class="custom-checkbox-container">
                <input type="checkbox" name="SMTP_ENABLED" value="1">
                <div class="custom-checkbox-mark">
                  <i class="fas fa-check"></i>
                </div>
                <span class="custom-checkbox-label">Enable Email Verification</span>
              </label>
            </div>
          </div>

          <!-- OAuth Settings -->
          <div class="admin-settings-card">
            <h3 class="section-title">Google OAuth Settings</h3>
            <div style="background: #f1f5f9; padding: 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem; color: #475569; border: 1px solid #e2e8f0;">
              <p><strong>Instructions:</strong></p>
              <ol style="margin-left: 18px; margin-top: 8px; display: flex; flex-direction: column; gap: 8px;">
                <li>Go to <a href="https://console.cloud.google.com/" target="_blank" style="color: #2563eb; text-decoration: underline;">Google Cloud Console</a></li>
                <li>Create a project and go to <strong>APIs & Services > Credentials</strong></li>
                <li>Create an <strong>OAuth 2.0 Client ID</strong> (Web application)</li>
                <li>Add this URL to <strong>Authorized JavaScript origins</strong>:
                  <div class="url-input-container">
                    <code id="origin-url">...</code>
                    <button type="button" class="copy-icon-btn" onclick="copyToClipboard('origin-url')" title="Copy"><i class="fas fa-copy"></i></button>
                  </div>
                </li>
                <li>Add this URL to <strong>Authorized redirect URIs</strong>:
                  <div class="url-input-container">
                    <code id="callback-url">...</code>
                    <button type="button" class="copy-icon-btn" onclick="copyToClipboard('callback-url')" title="Copy"><i class="fas fa-copy"></i></button>
                  </div>
                </li>
              </ol>
            </div>
            
            <div class="auth-field">
              <label class="custom-checkbox-container">
                <input type="checkbox" name="GOOGLE_LOGIN_ENABLED" value="1">
                <div class="custom-checkbox-mark">
                  <i class="fas fa-check"></i>
                </div>
                <span class="custom-checkbox-label">Enable Google Login</span>
              </label>
            </div>

            <div class="auth-field">
              <label class="auth-label">Google Client ID</label>
              <input type="text" name="GOOGLE_CLIENT_ID" placeholder="your-client-id.apps.googleusercontent.com">
            </div>
            <div class="auth-field">
              <label class="auth-label">Google Client Secret</label>
              <input type="password" name="GOOGLE_CLIENT_SECRET" placeholder="your-client-secret">
            </div>
          </div>

          <!-- Action Section -->
          <div class="admin-settings-card admin-settings-full">
            <button type="submit" class="playlist-btn" style="width: 100%;">Save All Settings</button>
          </div>
        </div>
      </form>
    </section>
  </main>

  <div id="custom-modal" class="modal-overlay">
    <div class="modal-content">
      <h3 id="modal-title" class="modal-title">Confirm Action</h3>
      <p id="modal-message" class="modal-message">Are you sure you want to proceed?</p>
      <input type="text" id="modal-input" class="modal-input app-hidden">
      <div class="modal-actions">
        <button id="modal-cancel" class="modal-btn modal-btn-cancel">Cancel</button>
        <button id="modal-confirm" class="modal-btn modal-btn-primary">Confirm</button>
      </div>
    </div>
  </div>

  <script src="script.js?v=20260317-45"></script>
</body>
</html>
