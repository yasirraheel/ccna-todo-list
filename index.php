<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="todo-api-base" content="">
  <title>Team Hifsa</title>
  <meta name="description" content="Manage tasks, playlists, and progress in one place.">
  <meta name="robots" content="index,follow">
  <meta name="theme-color" content="#0f172a">
  <link rel="canonical" href="">
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="My Tasks">
  <meta property="og:title" content="My Tasks">
  <meta property="og:description" content="Manage tasks, playlists, and progress in one place.">
  <meta property="og:url" content="">
  <meta property="og:image" content="">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="My Tasks">
  <meta name="twitter:description" content="Manage tasks, playlists, and progress in one place.">
  <meta name="twitter:image" content="">
  <link rel="icon" href="data:image/x-icon;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQEAAAAMAtDSzAAAAAEElEQVR42mNkIAAYGBAAAQAA/wEAgP8AAAAASUVORK5CYII=">
  <link rel="stylesheet" href="style.css?v=20260317-63">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div id="page-loader" class="page-loader">
    <div class="loader-spinner"></div>
    <div class="loader-text">Loading workspace...</div>
  </div>
  <div id="flash-stack" class="flash-stack"></div>
  <header class="saas-header">
    <div class="saas-header-left">
      <a id="app-home-link" href="/" class="saas-logo">Team Hifsa</a>
      <nav class="saas-nav">
        <a href="/" class="saas-nav-item active">Dashboard</a>
        <a href="/quiz.php" class="saas-nav-item">Subnet Quiz</a>
        <a href="#todo-form" class="saas-nav-item">Tasks</a>
        <a href="#playlist-url" class="saas-nav-item">Playlists</a>
      </nav>
    </div>
    <div class="saas-header-center">
      <div class="saas-search-wrap">
        <i class="fas fa-search"></i>
        <input id="saas-search-input" type="search" placeholder="Search tasks or playlists" aria-label="Search">
      </div>
    </div>
    <div class="saas-header-right">
      <span id="session-email" class="session-email"></span>
      <button type="button" id="logout-btn" class="bulk-btn">Logout</button>
    </div>
  </header>

  <main id="app-container" class="app-container">
    <section class="page-title-row">
      <h1 class="page-title">Dashboard</h1>
      <p id="current-date" class="date-display"></p>
    </section>

    <section class="dashboard">
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

    <ul id="task-list" class="task-list"></ul>
    <div class="load-more-wrap">
      <button id="load-more-btn" class="bulk-btn">Load More Tasks</button>
    </div>
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

  <script src="script.js?v=20260317-63"></script>
</body>
</html>
