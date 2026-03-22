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
    
    <!-- External Resources -->
    <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.0.8/css/all.css' media='all' />
    <link href="https://ccnax.com/wp-content/themes/ccnax/assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://ccnax.com/wp-content/themes/ccnax/assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
    <link href="https://ccnax.com/wp-content/themes/ccnax/assets/vendor/aos/aos.css" rel="stylesheet">
    <link href="https://ccnax.com/wp-content/themes/ccnax/assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
    <link href="https://ccnax.com/wp-content/themes/ccnax/assets/css/main.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo $assetVersion; ?>">
</head>

<body>
    <header id="header" class="header d-flex align-items-center">
        <div class="container-fluid container-xl d-flex align-items-center justify-content-between">
            <a href="<?php echo $baseUrl; ?>" class="logo d-flex align-items-center">
                <span><?php echo $appName; ?></span>
            </a>
            <nav id="navbar" class="navbar">
                <ul>
                    <li><a href="<?php echo $baseUrl; ?>" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="<?php echo $baseUrl; ?>/quiz.php" class="<?php echo ($currentPage == 'quiz.php') ? 'active' : ''; ?>">Subnet Quiz</a></li>
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
