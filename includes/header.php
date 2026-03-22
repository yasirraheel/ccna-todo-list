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
    <link rel="apple-touch-icon" href="https://ccnax.com/wp-content/themes/ccnax/assets/images/apple-touch-icon.png">
    
    <!-- API Base Meta -->
    <meta name="todo-api-base" content="<?php echo $baseUrl; ?>">
    
    <!-- External Resources -->
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css' media='all' />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" rel="stylesheet">
    <link href="assets/css/app-main.css?v=<?php echo $assetVersion; ?>" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo $assetVersion; ?>">
    
    <!-- jQuery must be in head because inline scripts in quiz.php depend on it -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
                    <li><a href="<?php echo $baseUrl; ?>" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Dashboard</a></li>
                    <li><a href="quiz.php" class="<?php echo ($currentPage == 'quiz.php') ? 'active' : ''; ?>">Quiz</a></li>
                    
                    <div class="session-info-wrap ms-3">
                        <span id="session-email" class="session-email d-none me-3"></span>
                        <button id="logout-btn" class="bulk-btn danger" style="padding: 6px 15px; font-size: 0.9rem;">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </div>
                </ul>
            </nav>
            <i class="mobile-nav-toggle mobile-nav-show bi bi-list"></i>
            <i class="mobile-nav-toggle mobile-nav-hide d-none bi bi-x"></i>
        </div>
    </header>
