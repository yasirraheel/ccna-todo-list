<?php
$appName = "Team Hifsa";
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$assetVersion = "1.1.35";
?>
<!doctype html>
<html lang="en-GB">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="author" content="Davidbombal">
    <meta name="copyright" content="DavidBombal">
    <meta name="coverage" content="Worldwide">
    <meta name="distribution" content="Global">
    <meta name="allow-search" content="yes" />
    <link rel="shortcut icon" href="https://ccnax.com/wp-content/themes/ccnax/assets/images/favicon.png">
    <link rel="apple-touch-icon" href="https://ccnax.com/wp-content/themes/ccnax/assets/images/apple-touch-icon.png">
    <title><?php echo $appName; ?> - Dashboard</title>
    
    <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.0.8/css/all.css' media='all' />
    <link href="https://ccnax.com/wp-content/themes/ccnax/assets/vendor/bootstrap/css/bootstrap.min.css"
        rel="stylesheet">
    <link href="https://ccnax.com/wp-content/themes/ccnax/assets/vendor/bootstrap-icons/bootstrap-icons.css"
        rel="stylesheet">
    <link href="https://ccnax.com/wp-content/themes/ccnax/assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="https://ccnax.com/wp-content/themes/ccnax/assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="https://ccnax.com/wp-content/themes/ccnax/assets/css/main.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo $assetVersion; ?>">

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
                <span><?php echo $appName; ?></span>
            </a>
            <nav id="navbar" class="navbar">
                <ul>
                    <li><a href="<?php echo $baseUrl; ?>" class="active">Home</a></li>
                    <li><a href="<?php echo $baseUrl; ?>/quiz.php">Subnet Quiz</a></li>
                    <div class="session-info-wrap">
                        <span id="session-email" class="session-email d-none d-md-inline"></span>
                        <button type="button" id="logout-btn" class="logout-btn">Logout</button>
                    </div>
                </ul>
            </nav>
            <i class="mobile-nav-toggle mobile-nav-show bi bi-list"></i>
            <i class="mobile-nav-toggle mobile-nav-hide d-none bi bi-x"></i>
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
                        <div class="progress-bar">
                            <div id="stat-progress-fill" class="progress-fill" style="width:0%"></div>
                        </div>
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
            <div class="row gy-3">
                <div class="col-lg-12 footer-links">
                    <center>
                        <h4>Give us a follow</h4>
                    </center>
                    <div class="row">
                        <div class="d-flex justify-content-center footersocial">
                            <div class="d-flex" style="text-align:center;">
                                <a href="https://www.youtube.com/davidbombal" target="_blank" class="social-icon fab fa-youtube"></a>
                                <a href="https://x.com/davidbombal" target="_blank" class="social-icon"><img src="https://ccnax.com/wp-content/uploads/2025/04/x.png" width="27"></a>
                                <a href="https://www.linkedin.com/in/davidbombal" target="_blank"
                                    class="social-icon fab fa-linkedin-in"></a>
                                <a href="https://www.facebook.com/davidbombal.co" target="_blank"
                                    class="social-icon fab fa-facebook-f"></a>
                                <a href="https://www.instagram.com/davidbombal/" target="_blank"
                                    class="social-icon fab fa-instagram"></a>
                                <a href="https://www.tiktok.com/@davidbombal" target="_blank" class="social-icon fas fa-music"></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container">
            <p style="text-align:center;"><a href="https://ccnax.com/terms-and-conditions/">Terms & Conditions</a> <span
                    style="margin-left:5px;margin-right:5px;">|</span> <a
                    href="https://ccnax.com/privacy-policy/">Privacy Policy</a></p>
            <div class="copyright" style="text-align:center;">
                If you have other issues or non-course questions, send us an
            </div>
            <div class="credits" style="text-align:center;">
                email at support@davidbombal.com. ↑
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

    <script src='https://ccnax.com/wp-content/themes/ccnax/assets/js/jquery.min.js'></script>
    <script src="https://ccnax.com/wp-content/themes/ccnax/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="https://ccnax.com/wp-content/themes/ccnax/assets/vendor/aos/aos.js"></script>
    <script src="https://ccnax.com/wp-content/themes/ccnax/assets/vendor/swiper/swiper-bundle.min.js"></script>
    <script src="https://ccnax.com/wp-content/themes/ccnax/assets/js/main.js"></script>
    <script src="script.js?v=<?php echo $assetVersion; ?>"></script>
</body>

</html>
