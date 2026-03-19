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
  <link rel="icon" href="/favicon.ico">
  <link rel="stylesheet" href="style.css?v=20260317-40">
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
        <a href="#todo-form" class="saas-nav-item">Tasks</a>
        <a href="#playlist-url" class="saas-nav-item">Playlists</a>
      </nav>
    </div>
    <div class="saas-header-center">
      <div class="saas-search-wrap">
        <i class="fas fa-search"></i>
        <input type="search" placeholder="Search tasks or playlists" aria-label="Search">
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
              <option value="personal">Personal</option>
              <option value="work">Work</option>
              <option value="shopping">Shopping</option>
            </select>
            <select id="task-visibility">
              <option value="private">Private Task</option>
              <option value="public">Public Task</option>
            </select>
            <button type="submit" class="playlist-btn">Add Task</button>
          </div>
        </div>
      </form>

      <div class="playlist-import">
        <h3 class="section-title">Import YouTube Playlist</h3>
        <div class="playlist-input-wrapper">
          <div class="playlist-url-row">
            <input type="text" id="playlist-url" placeholder="Paste YouTube Playlist URL here..." autocomplete="off">
          </div>
          <div class="playlist-attributes-row">
            <input type="text" id="playlist-name" placeholder="Playlist Name (optional)">
            <input type="date" id="playlist-date">
            <select id="playlist-priority">
              <option value="">Priority</option>
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
            </select>
            <select id="playlist-type">
              <option value="">Category</option>
              <option value="personal">Personal</option>
              <option value="work">Work</option>
              <option value="education">Education</option>
            </select>
            <select id="playlist-visibility">
              <option value="private">Private</option>
              <option value="public">Public</option>
            </select>
            <button type="button" id="import-playlist-btn" class="playlist-btn">Import Playlist</button>
          </div>
          <div id="playlist-status" class="playlist-status"></div>
        </div>
      </div>
    </section>

    <section class="toolbar">
      <div class="filters">
        <div class="playlist-toolbar">
          <select id="playlist-filter" class="filter-select">
            <option value="all">All Playlists</option>
          </select>
          <select id="scope-filter" class="filter-select">
            <option value="my">My Tasks</option>
            <option value="public">Public Library</option>
          </select>
          <button type="button" id="playlist-visibility-btn" class="bulk-btn" title="Toggle Playlist Visibility">
            <i class="fas fa-eye"></i> Visibility
          </button>
          <button type="button" id="playlist-rename-btn" class="bulk-btn" title="Rename Playlist">
            <i class="fas fa-edit"></i> Rename
          </button>
          <button type="button" id="playlist-delete-btn" class="bulk-btn danger" title="Clear Playlist (move to Unassigned)">
            <i class="fas fa-trash-can"></i> Delete
          </button>
        </div>
        <div class="status-filters">
          <button class="filter-btn active" data-filter="all">All</button>
          <button class="filter-btn" data-filter="active">Active</button>
          <button class="filter-btn" data-filter="completed">Completed</button>
          <button class="filter-btn" data-filter="has-notes">Has Notes</button>
        </div>
      </div>

      <div class="bulk-actions">
        <div class="select-all-wrap">
          <input type="checkbox" id="select-all-tasks" class="task-select">
          <label for="select-all-tasks">Select Visible</label>
        </div>
        <button id="delete-selected-btn" class="bulk-btn danger" disabled>Delete Selected</button>
        <button id="delete-all-btn" class="bulk-btn danger">Delete All</button>
      </div>
    </section>

    <ul id="task-list"></ul>
    
    <div class="pagination-footer">
      <button id="load-more-btn" class="playlist-btn">Load More Tasks</button>
    </div>
  </main>

  <button id="scroll-top-btn" class="scroll-top-btn" title="Scroll to top">
    <i class="fas fa-arrow-up"></i>
  </button>

  <footer class="app-footer">
    <div class="footer-content">
      <div class="footer-brand">
        <h3 id="footer-title">© 2025 Team Hifsa</h3>
        <p id="footer-description">The ultimate task management for learners.</p>
      </div>
      <div class="footer-links">
        <a href="#">Privacy</a>
        <a href="#">Terms</a>
        <a href="#">Support</a>
      </div>
    </div>
  </footer>

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

  <script src="script.js?v=20260317-40"></script>
</body>
</html>
