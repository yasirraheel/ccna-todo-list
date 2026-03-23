<?php
// Global Configuration
require_once __DIR__ . '/db.php';
$siteSettings = getSiteSettings();

$appName = $siteSettings['APP_NAME'] ?? "Team Hifsa";
$appDesc = $siteSettings['APP_DESCRIPTION'] ?? "Comprehensive tool for CCNA students and network engineers. Task management, subnetting quizzes, and more.";
$footerText = $siteSettings['FOOTER_TEXT'] ?? "If you have other issues or non-course questions, send us an";
$siteEmail = $siteSettings['SITE_EMAIL'] ?? "support@davidbombal.com";
$siteMobile = $siteSettings['SITE_MOBILE'] ?? "";
$gaId = $siteSettings['GA_ID'] ?? "";

// Social links
$socialYoutube = $siteSettings['SOCIAL_YOUTUBE'] ?? "https://www.youtube.com/davidbombal";
$socialTwitter = $siteSettings['SOCIAL_TWITTER'] ?? "https://x.com/davidbombal";
$socialLinkedin = $siteSettings['SOCIAL_LINKEDIN'] ?? "https://www.linkedin.com/in/davidbombal";
$socialInstagram = $siteSettings['SOCIAL_INSTAGRAM'] ?? "https://www.instagram.com/davidbombal/";
$socialFacebook = $siteSettings['SOCIAL_FACEBOOK'] ?? "https://www.facebook.com/davidbombal.co";

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = $_SERVER['HTTP_HOST'];
$dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$baseUrl = $protocol . "://" . $host . ($dir === '/' ? "" : $dir);
$assetVersion = "1.1.46"; // Incremented version to bust cache

// Default SEO values
if (!isset($pageTitle)) $pageTitle = $appName;
if (!isset($pageDesc)) $pageDesc = $appDesc;
?>
