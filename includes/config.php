<?php
// Global Configuration
$appName = "Team Hifsa";
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];
$dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$baseUrl = $protocol . "://" . $host . ($dir === '/' ? "" : $dir);
$assetVersion = "1.1.41"; // Incremented version

// Default SEO values
if (!isset($pageTitle)) $pageTitle = "Master Your Skills";
if (!isset($pageDesc)) $pageDesc = "Comprehensive tool for CCNA students and network engineers. Task management, subnetting quizzes, and more.";
?>
