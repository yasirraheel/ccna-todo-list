<?php
/**
 * Master Layout Template
 * 
 * Usage:
 * $pageTitle = "Page Title";
 * $pageDesc = "Page Description";
 * ob_start();
 * ?>
 * <!-- Your content here -->
 * <?php
 * $content = ob_get_clean();
 * include 'includes/layout.php';
 */

require_once __DIR__ . '/header.php';

// Output the main content
echo $content;

require_once __DIR__ . '/footer.php';
?>
