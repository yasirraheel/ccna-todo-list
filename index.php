<?php
require_once 'includes/config.php';
$pageTitle = "Dashboard";
$pageDesc = "Manage your CCNA study tasks and track your progress efficiently.";

ob_start();
?>

<div id="page-loader" class="page-loader">
    <div class="loader-spinner"></div>
    <div class="loader-text">Loading workspace...</div>
</div>
<div id="flash-stack" class="flash-stack"></div>

<main id="app-container" class="app-container">
    <section class="dashboard">
        <div class="stats-grid mb-4">
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
                <div class="progress-wrap w-100">
                    <div class="progress-bar">
                        <div id="stat-progress-fill" class="progress-fill" style="width:0%"></div>
                    </div>
                    <div id="stat-progress-label" class="progress-label mt-2">0%</div>
                </div>
            </div>
        </div>

        <form id="todo-form" class="mb-5">
            <h3 class="section-title">Add New Task</h3>
            <div class="entry-grid">
                <div class="input-group">
                    <input type="text" id="task-input" class="form-control" placeholder="What needs to be done?" required autocomplete="off">
                </div>
                <div class="input-group">
                    <input type="date" id="task-date" class="form-control">
                </div>
                <div class="input-group">
                    <select id="task-priority" class="form-select">
                        <option value="low">Low Priority</option>
                        <option value="medium" selected>Medium Priority</option>
                        <option value="high">High Priority</option>
                    </select>
                </div>
                <div class="input-group">
                    <select id="task-category" class="form-select">
                        <option value="General" selected>General</option>
                        <option value="Work">Work</option>
                        <option value="Personal">Personal</option>
                        <option value="Learning">Learning</option>
                    </select>
                </div>
                <div class="input-group">
                    <input type="url" id="playlist-url" class="form-control" placeholder="Paste YouTube Playlist URL (Optional)" autocomplete="off">
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mt-4 action-buttons-wrap">
                <button type="button" id="import-playlist-btn" class="bulk-btn success w-100 mb-2 mb-md-0 me-md-2" style="justify-content: center;">
                    <i class="fas fa-file-import"></i> Import Playlist
                </button>
                <button type="submit" id="add-task-btn" class="bulk-btn primary w-100" style="padding: 12px 30px; font-size: 1.1rem; justify-content: center;">
                    <i class="fas fa-plus"></i> Add Task
                </button>
            </div>
        </form>

        <div class="view-controls">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <h3 class="section-title mb-0">My Tasks</h3>
                    <div class="active-playlist-badge d-flex align-items-center gap-2" style="background: rgba(238, 131, 49, 0.1); border: 1px solid rgba(238, 131, 49, 0.3); color: #ee8331; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                        <i class="fas fa-list"></i> <span id="stat-playlist">All</span>
                    </div>
                </div>
                <div class="dropdown-container">
                    <button type="button" class="dropdown-btn" id="bulk-actions-btn" onclick="document.getElementById('bulk-actions-menu').classList.toggle('show')">
                        <i class="fas fa-ellipsis-v"></i> Actions
                    </button>
                    <div id="bulk-actions-menu" class="dropdown-content">
                        <button type="button" id="delete-selected-btn" class="dropdown-item danger" disabled>
                            <i class="fas fa-trash-can"></i> Delete Selected
                        </button>
                        <button type="button" id="delete-all-btn" class="dropdown-item danger">
                            <i class="fas fa-dumpster"></i> Delete All
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="active">Active</button>
                    <button class="filter-btn" data-filter="completed">Completed</button>
                    <button class="filter-btn" data-filter="has-notes">Notes</button>
                </div>
                <div class="playlist-filter-wrap d-flex align-items-center gap-3">
                    <label class="select-all-wrap mb-0">
                        <input type="checkbox" id="select-all-tasks" class="custom-checkbox">
                        <span class="ms-2">Select All</span>
                    </label>
                    <select id="playlist-filter" class="playlist-select">
                        <option value="all">All Playlists</option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <ul id="task-list" class="task-list mt-4"></ul>
    <div class="load-more-wrap mt-4 text-center">
        <button id="load-more-btn" class="bulk-btn">Load More Tasks</button>
    </div>
</main>

<?php 
$content = ob_get_clean();
include 'includes/layout.php'; 
?>
