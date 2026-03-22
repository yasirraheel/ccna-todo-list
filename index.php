<?php
$appName = "Team Hifsa";
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$assetVersion = "20260317-65";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="todo-api-base" content="">
  <title><?php echo $appName; ?> - Dashboard</title>
  <meta name="description" content="Manage tasks, playlists, and progress in one place.">
  <meta name="robots" content="index,follow">
  <meta name="theme-color" content="#0f172a">
  <link rel="canonical" href="<?php echo $baseUrl; ?>">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="<?php echo $appName; ?>">
  <meta property="og:title" content="<?php echo $appName; ?> - Dashboard">
  <meta property="og:description" content="Manage tasks, playlists, and progress in one place.">
  <meta property="og:url" content="<?php echo $baseUrl; ?>">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?php echo $appName; ?> - Dashboard">
  
  <link rel="icon" href="data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQEAAAAMAtDSzAAAAAEElEQVR42mNkIAAYGBAAAQAA/wEAgP8AAAAASUVORK5CYII=">
  
  <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' media='all' />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://ccnax.com/wp-content/themes/ccnax/assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="https://ccnax.com/wp-content/themes/ccnax/assets/css/main.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css?v=<?php echo $assetVersion; ?>">

  <style>
    :root {
      --primary-blue: #3b82f6;
      --bg-dark: #0f172a;
      --card-bg: #1e293b;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --border-color: #334155;
    }

    body {
      background-color: var(--bg-dark);
      color: var(--text-main);
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif !important;
      margin: 0;
    }

    #header {
      background: rgba(15, 23, 42, 0.8);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border-color);
      height: 70px;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
    }

    .header .logo {
      font-size: 24px;
      font-weight: 800;
      color: var(--primary-blue);
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .navbar ul li a {
      color: var(--text-muted);
      font-weight: 600;
      text-decoration: none;
      font-size: 0.95rem;
      transition: all 0.2s;
    }

    .navbar ul li a:hover, .navbar ul li a.active {
      color: var(--primary-blue);
    }

    .app-container {
      margin-top: 100px;
      padding: 24px;
      max-width: 1400px;
      margin-left: auto;
      margin-right: auto;
    }

    .stat-card {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      padding: 20px;
      border-radius: 16px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s;
    }

    .stat-card:hover {
      transform: translateY(-4px);
    }

    .stat-label {
      font-size: 0.85rem;
      font-weight: 600;
      color: var(--text-muted);
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .stat-label i {
      color: var(--primary-blue);
    }

    .stat-value {
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--text-main);
    }

    #todo-form, .view-controls {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 20px;
      padding: 24px;
      margin-bottom: 24px;
    }

    .section-title {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--text-main);
      margin-bottom: 20px;
    }

    input, select, textarea {
      background: #0f172a !important;
      border: 1px solid var(--border-color) !important;
      color: #ffffff !important;
      border-radius: 10px !important;
      padding: 12px 16px !important;
    }

    input:focus, select:focus {
      border-color: var(--primary-blue) !important;
      box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15) !important;
      outline: none !important;
    }

    .task-item {
      background: var(--card-bg);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 16px;
      transition: all 0.2s;
    }

    .task-item:hover {
      border-color: var(--primary-blue);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .footer {
      background: #0a0a0a;
      padding: 60px 0 30px;
      border-top: 1px solid var(--border-color);
      margin-top: 60px;
    }

    .saas-search-wrap {
      background: #0f172a;
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 10px 16px;
      display: flex;
      align-items: center;
      gap: 12px;
      width: 100%;
      max-width: 500px;
    }

    #saas-search-input {
      background: transparent !important;
      border: none !important;
      padding: 0 !important;
      width: 100%;
      color: white !important;
    }

    #saas-search-input:focus {
      box-shadow: none !important;
    }

    * {
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif !important;
    }
  </style>
</head>
<body>
  <div id="page-loader" class="page-loader">
    <div class="loader-spinner"></div>
    <div class="loader-text">Loading workspace...</div>
  </div>
  <div id="flash-stack" class="flash-stack"></div>

  <header id="header" class="header d-flex align-items-center">
    <div class="container-fluid container-xl d-flex align-items-center justify-content-between">
      <a href="<?php echo $baseUrl; ?>" class="logo d-flex align-items-center">
        <i class="fas fa-check-double"></i> <span><?php echo $appName; ?></span>
      </a>
      <nav id="navbar" class="navbar">
        <ul class="d-flex list-unstyled m-0 p-0 gap-4">
          <li><a href="<?php echo $baseUrl; ?>" class="active">Dashboard</a></li>
          <li><a href="<?php echo $baseUrl; ?>/quiz.php">Subnet Quiz</a></li>
        </ul>
      </nav>
      <div class="d-flex align-items-center gap-3">
        <span id="session-email" class="session-email d-none d-md-inline" style="color: #64748b; font-size: 0.9rem;"></span>
        <button type="button" id="logout-btn" class="bulk-btn">Logout</button>
      </div>
    </div>
  </header>

  <main id="app-container" class="app-container">
    <div class="saas-header-center mb-4 d-flex justify-content-center">
      <div class="saas-search-wrap w-100" style="max-width: 600px;">
        <i class="fas fa-search"></i>
        <input id="saas-search-input" type="search" placeholder="Search tasks or playlists" aria-label="Search">
      </div>
    </div>

    <section class="page-title-row mb-4">
      <h1 class="page-title">Dashboard</h1>
      <p id="current-date" class="date-display text-muted"></p>
    </section>

    <section class="dashboard mb-5">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-label"><i class="fas fa-layer-group"></i> Total</div>
          <div id="stat-total" class="stat-value">0</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><i class="fas fa-circle-check"></i> Completed</div>
          <div id="stat-completed" class="stat-value">0</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><i class="fas fa-hourglass-half"></i> Pending</div>
          <div id="stat-pending" class="stat-value">0</div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><i class="fas fa-chart-simple"></i> Progress</div>
          <div class="progress-wrap">
            <div class="progress-bar"><div id="stat-progress-fill" class="progress-fill" style="width:0%"></div></div>
            <div id="stat-progress-label" class="progress-label">0%</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-label"><i class="fas fa-list"></i> Active Playlist</div>
          <div id="stat-playlist" class="stat-value small">All</div>
        </div>
      </div>
    </section>

    <section class="entry-grid">
      <form id="todo-form">
        <h3 class="section-title">Add New Task</h3>
        <div class="playlist-input-wrapper">
          <div class="playlist-url-row">
            <input type="text" id="task-input" placeholder="What needs to be done?" required autocomplete="off">
          </div>
          <div class="playlist-attributes-row">
            <input type="date" id="task-date">
            <select id="task-priority">
              <option value="low">Low Priority</option>
              <option value="medium" selected>Medium Priority</option>
              <option value="high">High Priority</option>
            </select>
            <select id="task-category">
              <option value="General" selected>General</option>
              <option value="Work">Work</option>
              <option value="Personal">Personal</option>
              <option value="Learning">Learning</option>
            </select>
          </div>
          <div id="playlist-url-row" class="playlist-url-row">
            <input type="url" id="playlist-url" placeholder="Paste YouTube Playlist URL (Optional)" autocomplete="off">
            <button type="button" id="import-playlist-btn" class="bulk-btn success">
              <i class="fas fa-file-import"></i> Import
            </button>
          </div>
          <div class="form-actions">
            <button type="submit" id="add-task-btn" class="bulk-btn primary">
              <i class="fas fa-plus"></i> Add Task
            </button>
          </div>
        </div>
      </form>

      <div class="view-controls">
        <h3 class="section-title">My Tasks</h3>
        <div class="filter-row">
          <div class="filter-group">
            <button class="filter-btn active" data-filter="all">All</button>
            <button class="filter-btn" data-filter="active">Active</button>
            <button class="filter-btn" data-filter="completed">Completed</button>
            <button class="filter-btn" data-filter="has-notes">Notes</button>
          </div>
          <div class="playlist-filter-wrap">
            <select id="playlist-filter" class="playlist-select">
              <option value="all">All Playlists</option>
            </select>
          </div>
        </div>
        <div id="bulk-actions" class="bulk-actions-row">
          <label class="select-all-wrap">
            <input type="checkbox" id="select-all-tasks" class="custom-checkbox">
            <span>Select All</span>
          </label>
          <div class="bulk-buttons">
            <button type="button" id="delete-selected-btn" class="bulk-btn danger" disabled>
              <i class="fas fa-trash-can"></i> Delete Selected
            </button>
            <button type="button" id="delete-all-btn" class="bulk-btn danger">
              <i class="fas fa-dumpster"></i> Delete All
            </button>
          </div>
        </div>
      </div>
    </section>

    <ul id="task-list" class="task-list mt-4"></ul>
    <div class="load-more-wrap mt-4 text-center">
      <button id="load-more-btn" class="bulk-btn">Load More Tasks</button>
    </div>
  </main>

  <footer id="footer" class="footer mt-5">
    <div class="container">
      <div class="text-center mb-4">
        <h4 class="text-white">Give us a follow</h4>
        <div class="footersocial d-flex justify-content-center mt-3">
          <a href="https://www.youtube.com/davidbombal" target="_blank" class="social-icon fab fa-youtube"></a>
          <a href="https://x.com/davidbombal" target="_blank" class="social-icon fab fa-twitter"></a>
          <a href="https://www.linkedin.com/in/davidbombal" target="_blank" class="social-icon fab fa-linkedin-in"></a>
          <a href="https://www.facebook.com/davidbombal.co" target="_blank" class="social-icon fab fa-facebook-f"></a>
          <a href="https://www.instagram.com/davidbombal/" target="_blank" class="social-icon fab fa-instagram"></a>
          <a href="https://www.tiktok.com/@davidbombal" target="_blank" class="social-icon fas fa-music"></a>
        </div>
      </div>
      <div class="text-center border-top border-secondary pt-4">
        <p class="mb-2"><a href="#" class="text-muted text-decoration-none">Terms & Conditions</a> | <a href="#" class="text-muted text-decoration-none">Privacy Policy</a></p>
        <div class="copyright text-muted">
          &copy; <?php echo date('Y'); ?> <?php echo $appName; ?>. All Rights Reserved.
        </div>
      </div>
    </div>
  </footer>

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
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="script.js?v=<?php echo $assetVersion; ?>"></script>
</body>
</html>
