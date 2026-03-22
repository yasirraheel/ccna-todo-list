<?php
// Global Configuration
$appName = "Team Hifsa";
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$assetVersion = "1.1.38"; // Incremented version

// Default SEO values
if (!isset($pageTitle)) $pageTitle = "Master Your Skills";
if (!isset($pageDesc)) $pageDesc = "Comprehensive tool for CCNA students and network engineers. Task management, subnetting quizzes, and more.";
?>
