<?php
require_once __DIR__ . '/config.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="en-GB">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    
    <!-- SEO Tags -->
    <title><?php echo ($pageTitle === $appName) ? $appName : $pageTitle . " - " . $appName; ?></title>
    <meta name="description" content="<?php echo $pageDesc; ?>">
    <meta name="author" content="Davidbombal">
    <meta name="copyright" content="DavidBombal">
    <meta name="coverage" content="Worldwide">
    <meta name="distribution" content="Global">
    <meta name="allow-search" content="yes" />
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $baseUrl; ?>">
    <meta property="og:title" content="<?php echo $pageTitle; ?>">
    <meta property="og:description" content="<?php echo $pageDesc; ?>">
    <meta property="og:image" content="https://ccnax.com/wp-content/themes/ccnax/assets/images/apple-touch-icon.png">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo $baseUrl; ?>">
    <meta property="twitter:title" content="<?php echo $pageTitle; ?>">
    <meta property="twitter:description" content="<?php echo $pageDesc; ?>">
    <meta property="twitter:image" content="https://ccnax.com/wp-content/themes/ccnax/assets/images/apple-touch-icon.png">

    <link rel="shortcut icon" href="https://ccnax.com/wp-content/themes/ccnax/assets/images/favicon.png">
    <link rel="icon" href="https://ccnax.com/wp-content/themes/ccnax/assets/images/favicon.png">
    <link rel="apple-touch-icon" href="https://ccnax.com/wp-content/themes/ccnax/assets/images/apple-touch-icon.png">
    
    <!-- API Base Meta -->
    <meta name="todo-api-base" content="<?php echo $baseUrl; ?>">
    
    <?php if (!empty($gaId)): ?>
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $gaId; ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?php echo $gaId; ?>');
    </script>
    <?php endif; ?>
    
    <!-- External Resources -->
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@5.15.4/css/all.min.css' media='all' />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" rel="stylesheet">
    <link href="assets/css/app-main.css?v=<?php echo time(); ?>" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    
    <!-- jQuery must be in head because inline scripts in quiz.php depend on it -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- CRITICAL OVERRIDES TO BYPASS ALL CACHING/THEMES -->
    <style>
        @media (max-width: 1279px) {
            .navbar {
                background: #111111 !important;
            }
            .mobile-nav-active .navbar {
                background: #111111 !important;
                padding-top: 100px !important;
            }
            .mobile-nav-active .navbar::before {
                background: rgba(0,0,0,0.8) !important;
            }
            .mobile-nav-hide {
                color: #ffffff !important;
                z-index: 10002 !important;
                display: none;
                position: fixed !important;
                top: 20px !important;
                right: 20px !important;
                font-size: 32px !important;
            }
            .mobile-nav-active .mobile-nav-hide {
                display: block !important;
            }
            .mobile-nav-active .mobile-nav-show {
                display: none !important;
            }
            #navbar ul li a i {
                color: #ee8331 !important;
            }
        }

        .logout-btn {
            background: rgba(220, 53, 69, 0.1) !important;
            color: #ff4d4f !important;
            border: 1px solid rgba(220, 53, 69, 0.2) !important;
            transition: all 0.3s ease !important;
            margin-top: 10px !important;
            border-radius: 12px !important;
            font-size: 16px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 10px !important;
            width: 100% !important;
            height: 50px !important;
        }

        .logout-btn i {
            color: #ff4d4f !important;
        }

        .logout-btn:hover {
            background: #dc3545 !important;
            color: #ffffff !important;
        }

        .logout-btn:hover i {
            color: #ffffff !important;
        }

        /* Mobile Playlist Fixes */
        .bottom-sheet {
            background: #1a1a1a !important;
        }
        .bottom-sheet-overlay {
            background: rgba(0, 0, 0, 0.85) !important;
        }
        .modal-overlay.active .bottom-sheet {
            background: #1a1a1a !important;
        }
    </style>
</head>

<body>
    <header id="header" class="header d-flex align-items-center">
        <div class="container-fluid container-xl d-flex align-items-center justify-content-between">
            <a href="<?php echo $baseUrl; ?>" class="logo d-flex align-items-center">
                <span><?php echo $appName; ?></span>
            </a>
            <nav id="navbar" class="navbar">
                <div class="search-bar-container me-3 d-none d-lg-block">
                    <input type="text" id="saas-search" class="form-control" placeholder="Search tasks..." style="width: 250px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: #fff;">
                </div>
                <ul>
                    <li><a href="<?php echo $baseUrl; ?>" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>"><i class="fas fa-home d-lg-none"></i> Dashboard</a></li>
                    <li><a href="quiz.php" class="<?php echo ($currentPage == 'quiz.php') ? 'active' : ''; ?>"><i class="fas fa-question-circle d-lg-none"></i> Quiz</a></li>
                    
                    <div class="session-info-wrap ms-lg-3 d-flex flex-column flex-lg-row align-items-center w-100 mt-4 mt-lg-0 px-3 px-lg-0">
                        <span id="session-email" class="session-email d-none me-lg-3 mb-2 mb-lg-0 text-center w-100" style="color: rgba(255,255,255,0.6); font-size: 14px;"></span>
                        <button id="logout-btn" class="logout-btn">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </div>
                </ul>
            </nav>
            <i class="mobile-nav-toggle mobile-nav-show bi bi-list"></i>
            <i class="mobile-nav-toggle mobile-nav-hide d-none bi bi-x"></i>
        </div>
    </header>
