<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token, X-Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function loadEnvFile(string $filePath): void {
    if (!is_file($filePath)) return;
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!$lines) return;
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) continue;
        [$key, $value] = explode('=', $trimmed, 2);
        $key = trim($key);
        $value = trim($value);
        if ($value !== '' && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

function envValue(string $key, string $default = ''): string {
    // If not in standard environment variable, check $_ENV which our loader populates
    $value = getenv($key);
    if ($value === false && isset($_ENV[$key])) {
        $value = $_ENV[$key];
    }
    return $value === false ? $default : (string) $value;
}

function jsonResponse(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    $decoded = [];
    if (is_string($raw) && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) $decoded = [];
    }
    // Merge $_POST for multipart/form-data support
    return array_merge($decoded, $_POST);
}

function normalizeText(string $value): string {
    return mb_strtolower(trim($value), 'UTF-8');
}

function getClientIp(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($parts[0]);
    } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    return $ip;
}

function parsePlaylistId(string $value): string {
    $trimmed = trim($value);
    if ($trimmed === '') return '';
    if (preg_match('/^[\w-]+$/', $trimmed)) return $trimmed;
    $parts = parse_url($trimmed);
    if (!is_array($parts) || !isset($parts['query'])) return '';
    parse_str($parts['query'], $query);
    $list = (string) ($query['list'] ?? '');
    return preg_match('/^[\w-]+$/', $list) ? $list : '';
}

function extractPlaylistVideosFromFeed(string $playlistId): array {
    $feedUrl = 'https://www.youtube.com/feeds/videos.xml?playlist_id=' . urlencode($playlistId);
    $context = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true]]);
    $xml = @file_get_contents($feedUrl, false, $context);
    if (!is_string($xml) || $xml === '') return [];
    preg_match_all('/<entry>[\s\S]*?<\/entry>/', $xml, $entries);
    $videos = [];
    foreach ($entries[0] ?? [] as $entry) {
        if (!preg_match('/<title>([\s\S]*?)<\/title>/', $entry, $titleMatch)) continue;
        $title = trim(html_entity_decode($titleMatch[1], ENT_QUOTES | ENT_XML1, 'UTF-8'));
        if ($title === '') continue;
        $videoId = '';
        if (preg_match('/<yt:videoId>([\s\S]*?)<\/yt:videoId>/', $entry, $videoMatch)) {
            $videoId = trim($videoMatch[1]);
        }
        $videos[] = [
            'title' => $title,
            'videoId' => $videoId,
            'videoUrl' => $videoId !== '' ? 'https://www.youtube.com/watch?v=' . urlencode($videoId) : '',
            'thumbnailUrl' => $videoId !== '' ? 'https://i.ytimg.com/vi/' . $videoId . '/mqdefault.jpg' : '',
            'description' => ''
        ];
    }
    return $videos;
}

function extractPlaylistVideosFromYouTubeApi(string $playlistId, int $limit, string $apiKey): array {
    if ($apiKey === '') return [];
    $videos = [];
    $pageToken = '';
    while (count($videos) < $limit) {
        $query = http_build_query([
            'part' => 'snippet',
            'playlistId' => $playlistId,
            'maxResults' => 50,
            'pageToken' => $pageToken,
            'key' => $apiKey
        ]);
        $context = stream_context_create(['http' => ['timeout' => 20, 'ignore_errors' => true]]);
        $raw = @file_get_contents('https://www.googleapis.com/youtube/v3/playlistItems?' . $query, false, $context);
        if (!is_string($raw) || $raw === '') break;
        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['items']) || !is_array($decoded['items'])) break;
        foreach ($decoded['items'] as $item) {
            $snippet = $item['snippet'] ?? [];
            $title = trim((string) ($snippet['title'] ?? ''));
            if ($title === '' || $title === 'Private video' || $title === 'Deleted video') continue;
            $videoId = (string) ($snippet['resourceId']['videoId'] ?? '');
            
            // Get best thumbnail
            $thumbnails = $snippet['thumbnails'] ?? [];
            $thumbUrl = '';
            if (isset($thumbnails['maxres'])) $thumbUrl = $thumbnails['maxres']['url'];
            elseif (isset($thumbnails['standard'])) $thumbUrl = $thumbnails['standard']['url'];
            elseif (isset($thumbnails['high'])) $thumbUrl = $thumbnails['high']['url'];
            elseif (isset($thumbnails['medium'])) $thumbUrl = $thumbnails['medium']['url'];
            elseif (isset($thumbnails['default'])) $thumbUrl = $thumbnails['default']['url'];

            $videos[] = [
                'title' => $title,
                'videoId' => $videoId,
                'videoUrl' => $videoId !== '' ? 'https://www.youtube.com/watch?v=' . urlencode($videoId) : '',
                'thumbnailUrl' => $thumbUrl,
                'description' => (string) ($snippet['description'] ?? '')
            ];
            if (count($videos) >= $limit) break;
        }
        $pageToken = (string) ($decoded['nextPageToken'] ?? '');
        if ($pageToken === '') break;
    }
    return $videos;
}

function getApiPathSegments(): array {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = is_string($path) ? trim($path, '/') : '';
    if ($path === '') return [];
    $parts = array_values(array_filter(explode('/', $path), fn($part) => $part !== ''));
    $apiIndex = array_search('api', $parts, true);
    if ($apiIndex === false) return [];
    return array_slice($parts, $apiIndex + 1);
}

function convertXmlToText(string $xml): string {
    if (!class_exists('DOMDocument')) return "DOM extension is missing on this server.";
    $dom = new DOMDocument();
    @$dom->loadXML($xml);
    $text = '';
    $nodes = $dom->getElementsByTagName('text');
    foreach ($nodes as $node) {
        $text .= html_entity_decode($node->nodeValue, ENT_QUOTES | ENT_HTML5, 'UTF-8') . " ";
    }
    return trim($text);
}

function convertXmlToSrt(string $xml): string {
    if (!class_exists('DOMDocument')) return "DOM extension is missing on this server.";
    $dom = new DOMDocument();
    @$dom->loadXML($xml);
    $srt = '';
    $nodes = $dom->getElementsByTagName('text');
    $i = 1;
    foreach ($nodes as $node) {
        $start = (float) $node->getAttribute('start');
        $dur = (float) $node->getAttribute('dur');
        $end = $start + $dur;
        
        $formatTime = function($seconds) {
            $hours = floor($seconds / 3600);
            $mins = floor(($seconds % 3600) / 60);
            $secs = floor($seconds % 60);
            $ms = floor(($seconds - floor($seconds)) * 1000);
            return sprintf("%02d:%02d:%02d,%03d", $hours, $mins, $secs, $ms);
        };
        
        $srt .= $i . "\r\n";
        $srt .= $formatTime($start) . " --> " . $formatTime($end) . "\r\n";
        $srt .= html_entity_decode($node->nodeValue, ENT_QUOTES | ENT_HTML5, 'UTF-8') . "\r\n\r\n";
        $i++;
    }
    return trim($srt);
}

function downloadCaptions(string $videoId): ?string {
    if ($videoId === '') return null;
    
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header' => "User-Agent: {$userAgent}\r\nAccept-Language: en-US,en;q=0.9\r\n"
        ]
    ]);

    // Step 1: Fetch the video page to find the caption tracks JSON
    $videoUrl = "https://www.youtube.com/watch?v=" . $videoId;
    $html = @file_get_contents($videoUrl, false, $context);
    if (!$html) return null;

    // Look for player response JSON which contains caption info
    if (preg_match('/"captionTracks":\s*(\[.*?\])/', $html, $matches)) {
        $tracks = json_decode($matches[1], true);
        if ($tracks && is_array($tracks)) {
            // Prefer English, then first available
            $targetTrack = null;
            foreach ($tracks as $track) {
                if (($track['languageCode'] ?? '') === 'en') {
                    $targetTrack = $track;
                    break;
                }
            }
            if (!$targetTrack) $targetTrack = $tracks[0];

            if (isset($targetTrack['baseUrl'])) {
                $captionUrl = $targetTrack['baseUrl'] . "&fmt=srv3"; // Use srv3 for easier XML
                $content = @file_get_contents($captionUrl, false, $context);
                
                if ($content && strlen($content) > 100) {
                    $fileName = "captions_" . $videoId . "_" . time() . ".xml";
                    $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'captions';
                    if (!is_dir($dir)) @mkdir($dir, 0777, true);
                    
                    $filePath = $dir . DIRECTORY_SEPARATOR . $fileName;
                    if (@file_put_contents($filePath, $content)) {
                        return $fileName;
                    }
                }
            }
        }
    }
    
    // Fallback to old internal API if scraping fails
    $oldApiUrl = "https://www.youtube.com/api/timedtext?v=" . urlencode($videoId) . "&lang=en&fmt=srv3";
    $content = @file_get_contents($oldApiUrl, false, $context);
    if ($content && !str_contains($content, '<error') && strlen($content) > 100) {
        $fileName = "captions_" . $videoId . "_" . time() . ".xml";
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'captions';
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        $filePath = $dir . DIRECTORY_SEPARATOR . $fileName;
        if (@file_put_contents($filePath, $content)) {
            return $fileName;
        }
    }

    return null;
}

function resolveImportLimit($value): int {
    $default = 300;
    $max = 500;
    if ($value === null || $value === '') return $default;
    $parsed = (int) $value;
    if ($parsed < 1) return $default;
    return min($parsed, $max);
}

function normalizeVisibility($value): string {
    $v = strtolower(trim((string) $value));
    return $v === 'public' ? 'public' : 'private';
}

function upsertTaskCompletion(PDO $pdo, string $taskId, int $viewerId, bool $completed): void {
    $nowTs = (int) round(microtime(true) * 1000);
    $stmt = $pdo->prepare('INSERT INTO `task_completions` (`task_id`, `user_id`, `completed`, `updated_at`, `created_at`) VALUES (:task_id, :user_id, :completed, :updated_at, :created_at)
      ON DUPLICATE KEY UPDATE `completed` = VALUES(`completed`), `updated_at` = VALUES(`updated_at`)');
    $stmt->execute([
        ':task_id' => $taskId,
        ':user_id' => $viewerId,
        ':completed' => $completed ? 1 : 0,
        ':updated_at' => $nowTs,
        ':created_at' => $nowTs
    ]);
}

function dbConnect(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $host = trim(envValue('DB_HOST', '127.0.0.1'));
    $port = trim(envValue('DB_PORT', '3306'));
    $dbName = trim(envValue('DB_NAME', 'learn_ccna_todo'));
    $dbUser = trim(envValue('DB_USER', 'root'));
    $dbPass = envValue('DB_PASS', '');
    $charset = trim(envValue('DB_CHARSET', 'utf8mb4'));
    if ($dbName === '' || $dbUser === '') {
        jsonResponse(500, ['message' => 'Database config missing. Set DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS in .env']);
    }
    try {
        $pdo = new PDO("mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}", $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (Throwable $error) {
        if (php_sapi_name() === 'cli') {
            die("Database connection failed: " . $error->getMessage() . "\n");
        }
        jsonResponse(500, ['message' => 'Failed to connect database']);
    }
    return $pdo;
}

function ensureTables(PDO $pdo): void {
    // Check if the newest tables/columns exist to skip heavy initialization
    $needsInit = false;
    try {
        $cols = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'auth_provider'")->fetch();
        if (!$cols) $needsInit = true;
        
        $pq = $pdo->query("SHOW TABLES LIKE 'practice_questions'")->fetch();
        if (!$pq) $needsInit = true;

        $al = $pdo->query("SHOW TABLES LIKE 'user_activity_logs'")->fetch();
        if (!$al) $needsInit = true;
    } catch (Throwable $e) {
        $needsInit = true;
    }

    if (!$needsInit) return;

    $pdo->exec('CREATE TABLE IF NOT EXISTS `users` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `name` VARCHAR(120) NOT NULL,
      `email` VARCHAR(190) NOT NULL,
      `password_hash` VARCHAR(255) NOT NULL,
      `role` VARCHAR(20) NOT NULL DEFAULT "user",
      `status` VARCHAR(20) NOT NULL DEFAULT "active",
      `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
      `verification_token` VARCHAR(64) NULL,
      `auth_provider` VARCHAR(32) NOT NULL DEFAULT "email",
      `created_at` BIGINT NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_users_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `site_settings` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `setting_key` VARCHAR(128) NOT NULL,
      `setting_value` TEXT NOT NULL,
      `updated_at` BIGINT NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_site_settings_key` (`setting_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    try {
        $pdo->exec('ALTER TABLE `users` ADD COLUMN `role` VARCHAR(20) NOT NULL DEFAULT "user"');
    } catch (Throwable $e) {}
    try {
        $pdo->exec('ALTER TABLE `users` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT "active"');
    } catch (Throwable $e) {}
    try {
        $pdo->exec('ALTER TABLE `users` ADD COLUMN `is_verified` TINYINT(1) NOT NULL DEFAULT 0');
    } catch (Throwable $e) {}
    try {
        $pdo->exec('ALTER TABLE `users` ADD COLUMN `verification_token` VARCHAR(64) NULL');
    } catch (Throwable $e) {}
    try {
        $pdo->exec('ALTER TABLE `users` ADD COLUMN `auth_provider` VARCHAR(32) NOT NULL DEFAULT "email"');
    } catch (Throwable $e) {}

    $pdo->exec('CREATE TABLE IF NOT EXISTS `practice_questions` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `question_text` TEXT NOT NULL,
      `options` JSON NOT NULL,
      `correct_answers` JSON NOT NULL,
      `explanation` TEXT NOT NULL,
      `created_at` BIGINT NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $pdo->exec('CREATE TABLE IF NOT EXISTS `user_tokens` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id` BIGINT UNSIGNED NOT NULL,
      `token` VARCHAR(128) NOT NULL,
      `expires_at` BIGINT NOT NULL,
      `created_at` BIGINT NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_user_tokens_token` (`token`),
      INDEX `idx_user_tokens_user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `user_prefs` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id` BIGINT UNSIGNED NOT NULL,
      `pref_key` VARCHAR(128) NOT NULL,
      `pref_value` TEXT NOT NULL,
      `updated_at` BIGINT NOT NULL,
      `created_at` BIGINT NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_user_prefs_user_key` (`user_id`, `pref_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `user_activity_logs` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `user_id` BIGINT UNSIGNED NULL,
      `ip_address` VARCHAR(45) NOT NULL,
      `page_url` TEXT NOT NULL,
      `activity` TEXT NOT NULL,
      `user_agent` TEXT NULL,
      `created_at` BIGINT NOT NULL,
      PRIMARY KEY (`id`),
      INDEX `idx_logs_user_id` (`user_id`),
      INDEX `idx_logs_created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `tasks` (
      `id` VARCHAR(64) NOT NULL,
      `user_id` BIGINT UNSIGNED NOT NULL,
      `text` VARCHAR(1000) NOT NULL,
      `date` VARCHAR(32) NOT NULL DEFAULT "",
      `priority` VARCHAR(16) NOT NULL DEFAULT "medium",
      `category` VARCHAR(32) NOT NULL DEFAULT "personal",
      `visibility` VARCHAR(16) NOT NULL DEFAULT "private",
      `playlist_name` VARCHAR(190) NOT NULL DEFAULT "",
      `video_url` TEXT NULL,
      `thumbnail_url` TEXT NULL,
      `description` TEXT NULL,
      `tags` TEXT NULL,
      `caption_path` TEXT NULL,
      `views` BIGINT NOT NULL DEFAULT 0,
      `completed` TINYINT(1) NOT NULL DEFAULT 0,
      `created_at` BIGINT NOT NULL,
      PRIMARY KEY (`id`),
      INDEX `idx_tasks_user_created` (`user_id`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `task_completions` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `task_id` VARCHAR(64) NOT NULL,
      `user_id` BIGINT UNSIGNED NOT NULL,
      `completed` TINYINT(1) NOT NULL DEFAULT 0,
      `updated_at` BIGINT NOT NULL,
      `created_at` BIGINT NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_task_completions_task_user` (`task_id`, `user_id`),
      INDEX `idx_task_completions_user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $pdo->exec('CREATE TABLE IF NOT EXISTS `task_notes` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `task_id` VARCHAR(64) NOT NULL,
      `author_user_id` BIGINT UNSIGNED NOT NULL,
      `note_text` TEXT NOT NULL,
      `visibility` VARCHAR(16) NOT NULL DEFAULT "private",
      `updated_at` BIGINT NOT NULL,
      `created_at` BIGINT NOT NULL,
      PRIMARY KEY (`id`),
      INDEX `idx_task_notes_task` (`task_id`),
      INDEX `idx_task_notes_author` (`author_user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    try {
        $pdo->exec('ALTER TABLE `tasks` ADD COLUMN `playlist_name` VARCHAR(190) NOT NULL DEFAULT ""');
    } catch (Throwable $e) {}
    try {
        $pdo->exec('ALTER TABLE `tasks` ADD COLUMN `thumbnail_url` TEXT NULL');
    } catch (Throwable $e) {}
    try {
        $pdo->exec('ALTER TABLE `tasks` ADD COLUMN `description` TEXT NULL');
    } catch (Throwable $e) {}
    try {
        $pdo->exec('ALTER TABLE `tasks` ADD COLUMN `tags` TEXT NULL');
    } catch (Throwable $e) {}
    try {
        $pdo->exec('ALTER TABLE `tasks` ADD COLUMN `caption_path` TEXT NULL');
    } catch (Throwable $e) {}
    try {
        $pdo->exec('ALTER TABLE `tasks` ADD COLUMN `visibility` VARCHAR(16) NOT NULL DEFAULT "private"');
    } catch (Throwable $e) {}
    try {
        $pdo->exec('ALTER TABLE `tasks` ADD COLUMN `views` BIGINT NOT NULL DEFAULT 0');
    } catch (Throwable $e) {}
    try {
        $pdo->exec('ALTER TABLE `task_notes` DROP INDEX `uk_task_notes_task_author`');
    } catch (Throwable $e) {}
}

function mapTaskRow(array $row): array {
    $resolvedCompleted = array_key_exists('viewer_completed', $row) ? (int) $row['viewer_completed'] : (int) ($row['completed'] ?? 0);
    return [
        'id' => (string) ($row['id'] ?? ''),
        'text' => (string) ($row['text'] ?? ''),
        'date' => (string) ($row['date'] ?? ''),
        'priority' => (string) ($row['priority'] ?? 'medium'),
        'category' => (string) ($row['category'] ?? 'personal'),
        'visibility' => (string) ($row['visibility'] ?? 'private'),
        'playlistName' => (string) ($row['playlist_name'] ?? ''),
        'videoUrl' => (string) ($row['video_url'] ?? ''),
        'thumbnailUrl' => (string) ($row['thumbnail_url'] ?? ''),
        'description' => (string) ($row['description'] ?? ''),
        'tags' => (string) ($row['tags'] ?? ''),
        'captionPath' => (string) ($row['caption_path'] ?? ''),
        'views' => (int) ($row['views'] ?? 0),
        'ownerId' => isset($row['owner_id']) ? (int) $row['owner_id'] : (isset($row['user_id']) ? (int) $row['user_id'] : 0),
        'ownerEmail' => (string) ($row['owner_email'] ?? ''),
        'noteCount' => (int) ($row['note_count'] ?? 0),
        'completed' => $resolvedCompleted === 1
    ];
}

function mapTaskNoteRow(array $row, int $viewerId): array {
    return [
        'id' => isset($row['id']) ? (int) $row['id'] : 0,
        'taskId' => (string) ($row['task_id'] ?? ''),
        'text' => (string) ($row['note_text'] ?? ''),
        'visibility' => normalizeVisibility($row['visibility'] ?? 'private'),
        'authorUserId' => isset($row['author_user_id']) ? (int) $row['author_user_id'] : 0,
        'isOwn' => isset($row['author_user_id']) ? ((int) $row['author_user_id'] === $viewerId) : false,
        'updatedAt' => isset($row['updated_at']) ? (int) $row['updated_at'] : 0,
        'createdAt' => isset($row['created_at']) ? (int) $row['created_at'] : 0
    ];
}

function getAuthorizationToken(): string {
    $header = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) $header = (string) $_SERVER['HTTP_AUTHORIZATION'];
    if ($header === '' && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $header = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    if ($header === '' && isset($_SERVER['Authorization'])) {
        $header = (string) $_SERVER['Authorization'];
    }
    if ($header === '' && isset($_SERVER['HTTP_X_AUTH_TOKEN'])) {
        $header = 'Bearer ' . (string) $_SERVER['HTTP_X_AUTH_TOKEN'];
    }
    if ($header === '' && isset($_SERVER['HTTP_X_AUTHORIZATION'])) {
        $header = (string) $_SERVER['HTTP_X_AUTHORIZATION'];
    }
    if ($header === '' && isset($_COOKIE['todo_auth_token'])) {
        $header = 'Bearer ' . (string) $_COOKIE['todo_auth_token'];
    }
    if ($header === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        foreach ($headers as $key => $value) {
            if (strtolower((string) $key) === 'authorization') {
                $header = (string) $value;
                break;
            }
        }
    }
    if (!preg_match('/Bearer\s+(.+)/i', $header, $match)) return '';
    return trim($match[1]);
}

function setAuthCookie(string $token): void {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie('todo_auth_token', $token, [
        'expires' => time() + (60 * 60 * 24 * 30),
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function issueUserToken(PDO $pdo, int $userId): string {
    $now = (int) round(microtime(true) * 1000);
    $expires = $now + (1000 * 60 * 60 * 24 * 30);
    $token = bin2hex(random_bytes(32));
    $stmt = $pdo->prepare('INSERT INTO `user_tokens` (`user_id`, `token`, `expires_at`, `created_at`) VALUES (:user_id, :token, :expires_at, :created_at)');
    $stmt->execute([
        ':user_id' => $userId,
        ':token' => $token,
        ':expires_at' => $expires,
        ':created_at' => $now
    ]);
    return $token;
}

function getAuthenticatedUser(PDO $pdo): array {
    $user = getOptionalAuthenticatedUser($pdo);
    if (!$user) jsonResponse(401, ['message' => 'Unauthorized']);
    return $user;
}

function getOptionalAuthenticatedUser(PDO $pdo): ?array {
    if (php_sapi_name() === 'cli') {
        return ['id' => 1, 'name' => 'CLI User', 'email' => 'cli@localhost', 'role' => 'admin'];
    }

    $token = getAuthorizationToken();
    if ($token === '') return null;
    $now = (int) round(microtime(true) * 1000);
    $stmt = $pdo->prepare('SELECT u.`id`, u.`name`, u.`email`, u.`role`, u.`status` FROM `user_tokens` t INNER JOIN `users` u ON u.`id` = t.`user_id` WHERE t.`token` = :token AND t.`expires_at` > :now LIMIT 1');
    $stmt->execute([':token' => $token, ':now' => $now]);
    $user = $stmt->fetch();
    if (!$user) return null;
    if (($user['status'] ?? 'active') === 'suspended') {
        jsonResponse(403, ['message' => 'Your account has been suspended. Please contact admin.']);
    }
    return [
        'id' => (int) $user['id'],
        'name' => (string) $user['name'],
        'email' => (string) $user['email'],
        'role' => (string) ($user['role'] ?? 'user')
    ];
}

$projectRoot = dirname(__DIR__);
loadEnvFile($projectRoot . DIRECTORY_SEPARATOR . '.env');
$publicApiBase = rtrim(envValue('PUBLIC_API_BASE_URL', ''), '/');
$youtubeApiKey = trim(envValue('YOUTUBE_API_KEY', ''));
$appName = trim(envValue('APP_NAME', 'My Tasks'));
$appDescription = trim(envValue('APP_DESCRIPTION', ''));
$appOgImageUrl = trim(envValue('APP_OG_IMAGE_URL', ''));
$appCanonicalUrl = trim(envValue('APP_CANONICAL_URL', ''));
$pdo = dbConnect();
ensureTables($pdo);

// If executing from CLI, we just want to load the DB connection, not process HTTP routes
if (php_sapi_name() === 'cli') {
    return;
}

$segments = getApiPathSegments();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$body = getJsonBody();

if (count($segments) === 1 && $segments[0] === 'config' && $method === 'GET') {
    // Get site settings
    $settingsStmt = $pdo->query('SELECT `setting_key`, `setting_value` FROM `site_settings`');
    $dbSettings = [];
    foreach ($settingsStmt->fetchAll() as $row) {
        $dbSettings[$row['setting_key']] = $row['setting_value'];
    }

    jsonResponse(200, [
        'apiBaseUrl' => $publicApiBase,
        'runtime' => 'php',
        'appName' => $dbSettings['APP_NAME'] ?? ($appName !== '' ? $appName : 'My Tasks'),
        'appDescription' => $dbSettings['APP_DESCRIPTION'] ?? $appDescription,
        'appOgImageUrl' => $appOgImageUrl,
        'appCanonicalUrl' => $appCanonicalUrl,
        'footerText' => $dbSettings['FOOTER_TEXT'] ?? null,
        'logoUrl' => $dbSettings['LOGO_URL'] ?? null,
        'faviconUrl' => $dbSettings['FAVICON_URL'] ?? null,
        'googleClientId' => $dbSettings['GOOGLE_CLIENT_ID'] ?? null,
        'googleLoginEnabled' => ($dbSettings['GOOGLE_LOGIN_ENABLED'] ?? '0') === '1'
    ]);
}

function sendEmail($pdo, $to, $subject, $message): bool {
    $stmt = $pdo->query('SELECT `setting_key`, `setting_value` FROM `site_settings` WHERE `setting_key` LIKE "SMTP_%"');
    $s = [];
    foreach ($stmt->fetchAll() as $row) {
        $s[$row['setting_key']] = $row['setting_value'];
    }
    
    if (($s['SMTP_ENABLED'] ?? '0') !== '1') return true;

    $fromEmail = $s['SMTP_FROM_EMAIL'] ?? 'noreply@example.com';
    $fromName = $s['SMTP_FROM_NAME'] ?? 'Admin';
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . $fromEmail,
        'X-Mailer: PHP/' . phpversion()
    ];

    // This is a basic implementation using mail(). 
    // In production, users should use a library like PHPMailer with the stored SMTP credentials.
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

if (count($segments) === 2 && $segments[0] === 'auth' && $segments[1] === 'register' && $method === 'POST') {
    $name = trim((string) ($body['name'] ?? ''));
    $email = mb_strtolower(trim((string) ($body['email'] ?? '')), 'UTF-8');
    $password = (string) ($body['password'] ?? '');
    if ($name === '' || $email === '' || $password === '') jsonResponse(400, ['message' => 'Name, email and password are required']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) jsonResponse(400, ['message' => 'Valid email is required']);
    if (strlen($password) < 6) jsonResponse(400, ['message' => 'Password must be at least 6 characters']);
    $check = $pdo->prepare('SELECT `id` FROM `users` WHERE `email` = :email LIMIT 1');
    $check->execute([':email' => $email]);
    if ($check->fetch()) jsonResponse(409, ['message' => 'Email already exists']);
    
    // Get site settings for email subject
    $settingsStmt = $pdo->query('SELECT `setting_key`, `setting_value` FROM `site_settings`');
    $dbSettings = [];
    foreach ($settingsStmt->fetchAll() as $row) {
        $dbSettings[$row['setting_key']] = $row['setting_value'];
    }

    $smtpEnabled = (($dbSettings['SMTP_ENABLED'] ?? '0') === '1');
    
    $now = (int) round(microtime(true) * 1000);
    $otp = (string) random_int(100000, 999999);
    $isVerified = $smtpEnabled ? 0 : 1;

    $insert = $pdo->prepare('INSERT INTO `users` (`name`, `email`, `password_hash`, `is_verified`, `verification_token`, `created_at`) VALUES (:name, :email, :password_hash, :is_verified, :v_token, :created_at)');
    $insert->execute([
        ':name' => $name,
        ':email' => $email,
        ':password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ':is_verified' => $isVerified,
        ':v_token' => $isVerified ? null : $otp,
        ':created_at' => $now
    ]);
    $userId = (int) $pdo->lastInsertId();
    
    if ($userId === 1) {
        $pdo->prepare('UPDATE `users` SET `role` = "admin", `is_verified` = 1, `verification_token` = NULL WHERE `id` = 1')->execute();
        $isVerified = 1;
    }

    if ($smtpEnabled && $isVerified === 0) {
        $subject = "Verify your account - " . ($dbSettings['APP_NAME'] ?? 'Team Hifsa');
        $msg = "<h2>Welcome $name!</h2><p>Your verification code is:</p><h1 style='letter-spacing: 5px; background: #f1f5f9; padding: 10px; display: inline-block; border-radius: 8px;'>$otp</h1><p>Please enter this code in the app to verify your account.</p>";
        sendEmail($pdo, $email, $subject, $msg);
        jsonResponse(201, ['message' => 'Account created. Please check your email for the verification code.', 'requireOtp' => true, 'email' => $email]);
    }
    
    $authToken = issueUserToken($pdo, $userId);
    setAuthCookie($authToken);
    jsonResponse(201, ['token' => $authToken, 'user' => ['id' => $userId, 'name' => $name, 'email' => $email]]);
}

if (count($segments) === 2 && $segments[0] === 'auth' && $segments[1] === 'verify-otp' && $method === 'POST') {
    $email = mb_strtolower(trim((string) ($body['email'] ?? '')), 'UTF-8');
    $otp = trim((string) ($body['otp'] ?? ''));
    if ($email === '' || $otp === '') jsonResponse(400, ['message' => 'Email and code are required']);
    
    $stmt = $pdo->prepare('SELECT `id`, `name` FROM `users` WHERE `email` = :email AND `verification_token` = :otp LIMIT 1');
    $stmt->execute([':email' => $email, ':otp' => $otp]);
    $user = $stmt->fetch();
    if (!$user) jsonResponse(400, ['message' => 'Invalid or expired verification code']);
    
    $pdo->prepare('UPDATE `users` SET `is_verified` = 1, `verification_token` = NULL WHERE `id` = :id')->execute([':id' => $user['id']]);
    
    $authToken = issueUserToken($pdo, (int) $user['id']);
    setAuthCookie($authToken);
    jsonResponse(200, [
        'message' => 'Email verified successfully',
        'token' => $authToken,
        'user' => ['id' => (int) $user['id'], 'name' => (string) $user['name'], 'email' => $email]
    ]);
}

if (count($segments) === 2 && $segments[0] === 'auth' && $segments[1] === 'login' && $method === 'POST') {
    $email = mb_strtolower(trim((string) ($body['email'] ?? '')), 'UTF-8');
    $password = (string) ($body['password'] ?? '');
    if ($email === '' || $password === '') jsonResponse(400, ['message' => 'Email and password are required']);
    $stmt = $pdo->prepare('SELECT `id`, `name`, `email`, `password_hash`, `is_verified` FROM `users` WHERE `email` = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        jsonResponse(401, ['message' => 'Invalid email or password']);
    }
    
    // Check if email verification is enabled before blocking login
    $smtpCheck = $pdo->query('SELECT `setting_value` FROM `site_settings` WHERE `setting_key` = "SMTP_ENABLED" LIMIT 1');
    $smtpEnabled = ($smtpCheck->fetchColumn() === '1');

    if ($smtpEnabled && ($user['is_verified'] ?? 0) == 0) {
        jsonResponse(403, ['message' => 'Please verify your email before logging in.']);
    }
    $token = issueUserToken($pdo, (int) $user['id']);
    setAuthCookie($token);
    jsonResponse(200, [
        'token' => $token,
        'user' => ['id' => (int) $user['id'], 'name' => (string) $user['name'], 'email' => (string) $user['email']]
    ]);
}

if (count($segments) === 2 && $segments[0] === 'auth' && $segments[1] === 'google' && $method === 'POST') {
    $credential = (string) ($body['credential'] ?? '');
    if ($credential === '') jsonResponse(400, ['message' => 'Credential is required']);
    
    // Verify Google Token
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $credential;
    $resp = file_get_contents($url);
    if (!$resp) jsonResponse(401, ['message' => 'Invalid Google token']);
    
    $gUser = json_decode($resp, true);
    if (!isset($gUser['email'])) jsonResponse(401, ['message' => 'Invalid Google user data']);
    
    // Check if Client ID matches and if enabled
    $stmt = $pdo->query('SELECT `setting_key`, `setting_value` FROM `site_settings` WHERE `setting_key` IN ("GOOGLE_CLIENT_ID", "GOOGLE_CLIENT_SECRET", "GOOGLE_LOGIN_ENABLED")');
    $oauth = [];
    foreach ($stmt->fetchAll() as $row) {
        $oauth[$row['setting_key']] = $row['setting_value'];
    }

    if (($oauth['GOOGLE_LOGIN_ENABLED'] ?? '0') !== '1') {
        jsonResponse(403, ['message' => 'Google Login is currently disabled']);
    }

    if (isset($oauth['GOOGLE_CLIENT_ID']) && $gUser['aud'] !== $oauth['GOOGLE_CLIENT_ID']) {
        jsonResponse(401, ['message' => 'Invalid Google client audience']);
    }

    $email = mb_strtolower(trim($gUser['email']), 'UTF-8');
    $name = trim($gUser['name'] ?? $gUser['given_name'] ?? 'Google User');
    
    $check = $pdo->prepare('SELECT `id`, `name`, `email`, `role`, `status` FROM `users` WHERE `email` = :email LIMIT 1');
    $check->execute([':email' => $email]);
    $user = $check->fetch();
    
    $now = (int) round(microtime(true) * 1000);
    if (!$user) {
        // Create new user
        $insert = $pdo->prepare('INSERT INTO `users` (`name`, `email`, `password_hash`, `is_verified`, `auth_provider`, `created_at`) VALUES (:name, :email, :pass, 1, "google", :now)');
        $insert->execute([
            ':name' => $name,
            ':email' => $email,
            ':pass' => 'google_oauth_' . bin2hex(random_bytes(8)), // Dummy password
            ':now' => $now
        ]);
        $userId = (int) $pdo->lastInsertId();
        if ($userId === 1) {
            $pdo->prepare('UPDATE `users` SET `role` = "admin" WHERE `id` = 1')->execute();
        }
        $user = ['id' => $userId, 'name' => $name, 'email' => $email, 'role' => 'user'];
    } else {
        if (($user['status'] ?? 'active') === 'suspended') {
            jsonResponse(403, ['message' => 'Your account has been suspended.']);
        }
        $userId = (int) $user['id'];
        // Mark as verified and set auth_provider if logging in via Google
        $pdo->prepare('UPDATE `users` SET `is_verified` = 1, `verification_token` = NULL, `auth_provider` = "google" WHERE `id` = :id')->execute([':id' => $userId]);
    }
    
    $token = issueUserToken($pdo, $userId);
    setAuthCookie($token);
    jsonResponse(200, ['token' => $token, 'user' => ['id' => $userId, 'name' => $user['name'], 'email' => $user['email'], 'role' => $user['role'] ?? 'user']]);
}

if (count($segments) === 2 && $segments[0] === 'auth' && $segments[1] === 'me' && $method === 'GET') {
    $user = getAuthenticatedUser($pdo);
    jsonResponse(200, ['user' => $user]);
}

if (count($segments) === 3 && $segments[0] === 'captions' && $method === 'GET') {
    $fileName = basename((string) $segments[1]);
    $format = strtolower((string) $segments[2]);
    $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'captions' . DIRECTORY_SEPARATOR . $fileName;
    if (!file_exists($filePath)) jsonResponse(404, ['message' => 'Caption not found']);
    
    $content = file_get_contents($filePath);
    if ($format === 'text') {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . str_replace('.xml', '.txt', $fileName) . '"');
        echo convertXmlToText($content);
        exit;
    } elseif ($format === 'srt') {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . str_replace('.xml', '.srt', $fileName) . '"');
        echo convertXmlToSrt($content);
        exit;
    }
    
    header('Content-Type: application/xml');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    echo $content;
    exit;
}

if (count($segments) === 2 && $segments[0] === 'captions' && $method === 'GET') {
    $fileName = basename((string) $segments[1]);
    $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'captions' . DIRECTORY_SEPARATOR . $fileName;
    if (!file_exists($filePath)) jsonResponse(404, ['message' => 'Caption not found']);
    header('Content-Type: application/xml');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    readfile($filePath);
    exit;
}

if (count($segments) === 2 && $segments[0] === 'uploads' && $method === 'GET') {
    $fileName = basename((string) $segments[1]);
    $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $fileName;
    if (!file_exists($filePath)) jsonResponse(404, ['message' => 'File not found']);
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    header("Content-Type: $mime");
    readfile($filePath);
    exit;
}

if (count($segments) === 3 && $segments[0] === 'uploads' && $segments[1] === 'notes' && $method === 'GET') {
    $fileName = basename((string) $segments[2]);
    $filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'notes' . DIRECTORY_SEPARATOR . $fileName;
    if (!file_exists($filePath)) jsonResponse(404, ['message' => 'File not found']);
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $filePath);
    finfo_close($finfo);
    
    header("Content-Type: $mime");
    readfile($filePath);
    exit;
}

$authUser = getOptionalAuthenticatedUser($pdo);
$userId = $authUser ? (int) $authUser['id'] : 0;

if (count($segments) === 1 && $segments[0] === 'logs' && $method === 'POST') {
    $pageUrl = trim((string) ($body['page_url'] ?? ''));
    $activity = trim((string) ($body['activity'] ?? 'Page Visit'));
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $ip = getClientIp();
    $now = (int) round(microtime(true) * 1000);
    
    $stmt = $pdo->prepare('INSERT INTO `user_activity_logs` (`user_id`, `ip_address`, `page_url`, `activity`, `user_agent`, `created_at`) VALUES (:user_id, :ip, :url, :activity, :ua, :created_at)');
    $stmt->execute([
        ':user_id' => $authUser ? (int) $authUser['id'] : null,
        ':ip' => $ip,
        ':url' => $pageUrl,
        ':activity' => $activity,
        ':ua' => $userAgent,
        ':created_at' => $now
    ]);
    jsonResponse(201, ['message' => 'Log recorded']);
}

// Note: We moved the quiz endpoints above the strict auth check so they can be accessed publicly
if (count($segments) === 1 && $segments[0] === 'quiz' && $method === 'GET') {
    // Fetch a random practice question
    $stmt = $pdo->query('SELECT `id`, `question_text`, `options` FROM `practice_questions` ORDER BY RAND() LIMIT 1');
    $question = $stmt->fetch();
    if (!$question) {
        jsonResponse(404, ['message' => 'No practice questions available']);
    }
    
    // Decode options json for frontend
    $question['options'] = json_decode($question['options'], true);
    jsonResponse(200, $question);
}

if (count($segments) === 2 && $segments[0] === 'quiz' && $segments[1] === 'check' && $method === 'POST') {
    $questionId = (int) ($body['id'] ?? 0);
    $selectedOptions = $body['selected'] ?? [];
    
    if (!$questionId || !is_array($selectedOptions)) {
        jsonResponse(400, ['message' => 'Invalid payload']);
    }
    
    $stmt = $pdo->prepare('SELECT `correct_answers`, `explanation` FROM `practice_questions` WHERE `id` = ?');
    $stmt->execute([$questionId]);
    $question = $stmt->fetch();
    
    if (!$question) {
        jsonResponse(404, ['message' => 'Question not found']);
    }
    
    $correctAnswers = json_decode($question['correct_answers'], true);
    $explanation = $question['explanation'];
    
    // Check if the selected options exactly match the correct options (ignoring order)
    $selectedNormalized = array_map('strtoupper', array_map('trim', $selectedOptions));
    $correctNormalized = array_map('strtoupper', array_map('trim', $correctAnswers));
    
    // Some questions might have parsed the answer weirdly during seeding (e.g., ["A", "B", "AND", "C"]). Let's clean out non-letters.
    $correctNormalized = array_filter($correctNormalized, function($val) {
        return preg_match('/^[A-Z]$/', $val);
    });
    
    sort($selectedNormalized);
    sort($correctNormalized);
    
    // In case the parser totally failed and correctNormalized is empty, fallback to not crashing
    if (empty($correctNormalized)) {
        $correctNormalized = ['A']; 
    }

    $isCorrect = ($selectedNormalized === array_values($correctNormalized));
    
    jsonResponse(200, [
        'isCorrect' => $isCorrect,
        'correctAnswers' => array_values($correctNormalized),
        'explanation' => $explanation
    ]);
}

if (!$authUser) {
    jsonResponse(401, ['message' => 'Unauthorized']);
}

if (count($segments) === 2 && $segments[0] === 'notes' && $segments[1] === 'upload-image' && $method === 'POST') {
    $file = $_FILES['upload'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        if (isset($_GET['CKEditorFuncNum'])) {
            $funcNum = $_GET['CKEditorFuncNum'];
            header('Content-Type: text/html; charset=utf-8');
            echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($funcNum, '', 'No file uploaded or upload error.');</script>";
            exit;
        }
        jsonResponse(200, [
            'uploaded' => 0,
            'error' => ['message' => 'No file uploaded or upload error.']
        ]);
    }

    $uploadsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'notes';
    if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0777, true);

    $ext = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($ext, $allowed)) {
        if (isset($_GET['CKEditorFuncNum'])) {
            $funcNum = $_GET['CKEditorFuncNum'];
            header('Content-Type: text/html; charset=utf-8');
            echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($funcNum, '', 'Invalid file type.');</script>";
            exit;
        }
        jsonResponse(200, [
            'uploaded' => 0,
            'error' => ['message' => 'Invalid file type. Allowed: ' . implode(', ', $allowed)]
        ]);
    }

    $fileName = 'note_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $uploadsDir . DIRECTORY_SEPARATOR . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Build the absolute URL for the uploaded image to avoid any confusion
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
        $host = $_SERVER['HTTP_HOST'];
        $scriptName = $_SERVER['SCRIPT_NAME']; 
        $baseUrl = $protocol . "://" . $host . dirname($scriptName);
        $url = rtrim($baseUrl, '/') . '/uploads/notes/' . $fileName;

        if (isset($_GET['CKEditorFuncNum'])) {
            $funcNum = $_GET['CKEditorFuncNum'];
            header('Content-Type: text/html; charset=utf-8');
            echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($funcNum, '$url', 'Image uploaded successfully!');</script>";
            exit;
        }

        jsonResponse(200, [
            'uploaded' => 1,
            'fileName' => $fileName,
            'url' => $url
        ]);
    } else {
        if (isset($_GET['CKEditorFuncNum'])) {
            $funcNum = $_GET['CKEditorFuncNum'];
            header('Content-Type: text/html; charset=utf-8');
            echo "<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($funcNum, '', 'Failed to move uploaded file.');</script>";
            exit;
        }
        jsonResponse(200, [
            'uploaded' => 0,
            'error' => ['message' => 'Failed to move uploaded file.']
        ]);
    }
}
if (count($segments) === 1 && $segments[0] === 'preferences' && $method === 'GET') {
    $stmt = $pdo->prepare('SELECT `pref_key`, `pref_value` FROM `user_prefs` WHERE `user_id` = :user_id');
    $stmt->execute([':user_id' => $userId]);
    $prefs = [];
    foreach ($stmt->fetchAll() ?: [] as $row) {
        $prefs[(string) ($row['pref_key'] ?? '')] = (string) ($row['pref_value'] ?? '');
    }
    $selected = (string) ($prefs['selected_playlist'] ?? 'all');
    jsonResponse(200, ['selectedPlaylist' => $selected]);
}

if (count($segments) === 1 && $segments[0] === 'preferences' && $method === 'POST') {
    $selected = trim((string) ($body['selectedPlaylist'] ?? 'all'));
    $nowTs = (int) round(microtime(true) * 1000);
    $up = $pdo->prepare('INSERT INTO `user_prefs` (`user_id`, `pref_key`, `pref_value`, `updated_at`, `created_at`) VALUES (:user_id, :pref_key, :pref_value, :updated_at, :created_at)
      ON DUPLICATE KEY UPDATE `pref_value` = VALUES(`pref_value`), `updated_at` = VALUES(`updated_at`)');
    $up->execute([
        ':user_id' => $userId,
        ':pref_key' => 'selected_playlist',
        ':pref_value' => $selected,
        ':updated_at' => $nowTs,
        ':created_at' => $nowTs
    ]);
    jsonResponse(200, ['selectedPlaylist' => $selected]);
}

if (count($segments) === 1 && $segments[0] === 'tasks' && $method === 'GET') {
    $playlist = trim((string) ($_GET['playlist'] ?? ''));
    if ($playlist !== '' && strtolower($playlist) !== 'all') {
        $stmt = $pdo->prepare('SELECT t.`id`, t.`user_id`, t.`text`, t.`date`, t.`priority`, t.`category`, t.`visibility`, t.`playlist_name`, t.`video_url`, t.`thumbnail_url`, t.`description`, t.`caption_path`, t.`views`, 
            (SELECT COUNT(*) FROM `task_notes` n WHERE n.`task_id` = t.`id` AND (n.`author_user_id` = :viewer_id_note OR n.`visibility` = "public")) AS `note_count`,
            CASE WHEN t.`visibility` = "public" THEN COALESCE(tc.`completed`, 0) ELSE t.`completed` END AS `viewer_completed`
            FROM `tasks` t
            LEFT JOIN `task_completions` tc ON tc.`task_id` = t.`id` AND tc.`user_id` = :viewer_id
            WHERE t.`user_id` = :user_id AND t.`playlist_name` = :playlist
            ORDER BY t.`created_at` DESC');
        $stmt->execute([':viewer_id' => $userId, ':viewer_id_note' => $userId, ':user_id' => $userId, ':playlist' => $playlist]);
    } else {
        $stmt = $pdo->prepare('SELECT t.`id`, t.`user_id`, t.`text`, t.`date`, t.`priority`, t.`category`, t.`visibility`, t.`playlist_name`, t.`video_url`, t.`thumbnail_url`, t.`description`, t.`caption_path`, t.`views`, 
            (SELECT COUNT(*) FROM `task_notes` n WHERE n.`task_id` = t.`id` AND (n.`author_user_id` = :viewer_id_note OR n.`visibility` = "public")) AS `note_count`,
            CASE WHEN t.`visibility` = "public" THEN COALESCE(tc.`completed`, 0) ELSE t.`completed` END AS `viewer_completed`
            FROM `tasks` t
            LEFT JOIN `task_completions` tc ON tc.`task_id` = t.`id` AND tc.`user_id` = :viewer_id
            WHERE t.`user_id` = :user_id
            ORDER BY t.`created_at` DESC');
        $stmt->execute([':viewer_id' => $userId, ':viewer_id_note' => $userId, ':user_id' => $userId]);
    }
    jsonResponse(200, array_map('mapTaskRow', $stmt->fetchAll() ?: []));
}

if (count($segments) === 2 && $segments[0] === 'tasks' && $segments[1] === 'public' && $method === 'GET') {
    $optionalUser = getOptionalAuthenticatedUser($pdo);
    $viewerId = $optionalUser ? (int) $optionalUser['id'] : 0;
    $playlist = trim((string) ($_GET['playlist'] ?? ''));
    if ($playlist !== '' && strtolower($playlist) !== 'all') {
        $stmt = $pdo->prepare('SELECT t.`id`, t.`user_id`, t.`user_id` AS `owner_id`, u.`email` AS `owner_email`, t.`text`, t.`date`, t.`priority`, t.`category`, t.`visibility`, t.`playlist_name`, t.`video_url`, t.`thumbnail_url`, t.`description`, t.`caption_path`, t.`views`, 
            (SELECT COUNT(*) FROM `task_notes` n WHERE n.`task_id` = t.`id` AND (n.`author_user_id` = :viewer_id_note OR n.`visibility` = "public")) AS `note_count`,
            COALESCE(tc.`completed`, 0) AS `viewer_completed`
            FROM `tasks` t
            INNER JOIN `users` u ON u.`id` = t.`user_id`
            LEFT JOIN `task_completions` tc ON tc.`task_id` = t.`id` AND tc.`user_id` = :viewer_id
            WHERE t.`visibility` = "public" AND t.`playlist_name` = :playlist
            ORDER BY t.`created_at` DESC');
        $stmt->execute([':viewer_id' => $viewerId, ':viewer_id_note' => $viewerId, ':playlist' => $playlist]);
    } else {
        $stmt = $pdo->prepare('SELECT t.`id`, t.`user_id`, t.`user_id` AS `owner_id`, u.`email` AS `owner_email`, t.`text`, t.`date`, t.`priority`, t.`category`, t.`visibility`, t.`playlist_name`, t.`video_url`, t.`thumbnail_url`, t.`description`, t.`caption_path`, t.`views`, 
            (SELECT COUNT(*) FROM `task_notes` n WHERE n.`task_id` = t.`id` AND (n.`author_user_id` = :viewer_id_note OR n.`visibility` = "public")) AS `note_count`,
            COALESCE(tc.`completed`, 0) AS `viewer_completed`
            FROM `tasks` t
            INNER JOIN `users` u ON u.`id` = t.`user_id`
            LEFT JOIN `task_completions` tc ON tc.`task_id` = t.`id` AND tc.`user_id` = :viewer_id
            WHERE t.`visibility` = "public"
            ORDER BY t.`created_at` DESC');
        $stmt->execute([':viewer_id' => $viewerId, ':viewer_id_note' => $viewerId]);
    }
    jsonResponse(200, array_map('mapTaskRow', $stmt->fetchAll() ?: []));
}

if (count($segments) === 1 && $segments[0] === 'tasks' && $method === 'POST') {
    $text = trim((string) ($body['text'] ?? ''));
    if ($text === '') jsonResponse(400, ['message' => 'Task text is required']);
    $now = (int) round(microtime(true) * 1000);
    $playlistNameBody = (string) ($body['playlistName'] ?? ($body['playlist'] ?? ''));
    $playlistNameBody = trim($playlistNameBody);
    $visibility = normalizeVisibility($body['visibility'] ?? 'private');
    $task = [
        'id' => (string) $now . '-' . bin2hex(random_bytes(3)),
        'text' => $text,
        'date' => (string) ($body['date'] ?? ''),
        'priority' => (string) ($body['priority'] ?? 'medium'),
        'category' => (string) ($body['category'] ?? 'personal'),
        'visibility' => $visibility,
        'playlistName' => $playlistNameBody,
        'videoUrl' => (string) ($body['videoUrl'] ?? ''),
        'thumbnailUrl' => (string) ($body['thumbnailUrl'] ?? ''),
        'description' => (string) ($body['description'] ?? ''),
        'views' => 0,
        'completed' => (bool) ($body['completed'] ?? false)
    ];
    $insert = $pdo->prepare('INSERT INTO `tasks` (`id`, `user_id`, `text`, `date`, `priority`, `category`, `visibility`, `playlist_name`, `video_url`, `thumbnail_url`, `description`, `views`, `completed`, `created_at`) VALUES (:id, :user_id, :text, :date, :priority, :category, :visibility, :playlist_name, :video_url, :thumbnail_url, :description, :views, :completed, :created_at)');
    $insert->execute([
        ':id' => $task['id'],
        ':user_id' => $userId,
        ':text' => $task['text'],
        ':date' => $task['date'],
        ':priority' => $task['priority'],
        ':category' => $task['category'],
        ':visibility' => $task['visibility'],
        ':playlist_name' => $task['playlistName'],
        ':video_url' => $task['videoUrl'],
        ':thumbnail_url' => $task['thumbnailUrl'],
        ':description' => $task['description'],
        ':views' => 0,
        ':completed' => $task['completed'] ? 1 : 0,
        ':created_at' => $now
    ]);
    if ($task['visibility'] === 'public') {
        upsertTaskCompletion($pdo, $task['id'], $userId, $task['completed']);
    }
    jsonResponse(201, $task);
}

if (count($segments) === 1 && $segments[0] === 'tasks' && $method === 'DELETE') {
    $ownedIdsStmt = $pdo->prepare('SELECT `id` FROM `tasks` WHERE `user_id` = :user_id');
    $ownedIdsStmt->execute([':user_id' => $userId]);
    $ownedIds = array_map(fn($r) => (string) ($r['id'] ?? ''), $ownedIdsStmt->fetchAll() ?: []);
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM `tasks` WHERE `user_id` = :user_id');
    $countStmt->execute([':user_id' => $userId]);
    $deletedCount = (int) $countStmt->fetchColumn();
    $deleteStmt = $pdo->prepare('DELETE FROM `tasks` WHERE `user_id` = :user_id');
    $deleteStmt->execute([':user_id' => $userId]);
    if (count($ownedIds) > 0) {
        $placeholders = implode(',', array_fill(0, count($ownedIds), '?'));
        $pdo->prepare("DELETE FROM `task_completions` WHERE `task_id` IN ({$placeholders})")->execute($ownedIds);
    }
    jsonResponse(200, ['deletedCount' => $deletedCount]);
}

if (count($segments) === 2 && $segments[0] === 'tasks' && $segments[1] === 'bulk-delete' && $method === 'POST') {
    $ids = $body['ids'] ?? null;
    if (!is_array($ids) || count($ids) === 0) jsonResponse(400, ['message' => 'Task ids are required']);
    $ids = array_values(array_map(fn($id) => (string) $id, $ids));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $countSql = "SELECT COUNT(*) FROM `tasks` WHERE `user_id` = ? AND `id` IN ({$placeholders})";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute(array_merge([$userId], $ids));
    $deletedCount = (int) $countStmt->fetchColumn();
    $deleteSql = "DELETE FROM `tasks` WHERE `user_id` = ? AND `id` IN ({$placeholders})";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute(array_merge([$userId], $ids));
    $pdo->prepare("DELETE FROM `task_completions` WHERE `task_id` IN ({$placeholders})")->execute($ids);
    jsonResponse(200, ['deletedCount' => $deletedCount]);
}

if ((count($segments) === 3 || count($segments) === 4) && $segments[0] === 'tasks' && $segments[2] === 'notes') {
    $taskId = (string) $segments[1];
    $noteId = isset($segments[3]) ? (int) $segments[3] : null;
    
    $taskStmt = $pdo->prepare('SELECT `id`, `user_id`, `visibility` FROM `tasks` WHERE `id` = :id LIMIT 1');
    $taskStmt->execute([':id' => $taskId]);
    $task = $taskStmt->fetch();
    if (!$task) jsonResponse(404, ['message' => 'Task not found']);
    $taskOwnerId = (int) ($task['user_id'] ?? 0);
    $taskVisibility = normalizeVisibility($task['visibility'] ?? 'private');
    $canViewTask = $taskOwnerId === $userId || $taskVisibility === 'public';
    if (!$canViewTask) jsonResponse(403, ['message' => 'Not allowed']);

    if ($noteId === null) {
        if ($method === 'GET') {
            $notesStmt = $pdo->prepare('SELECT `id`, `task_id`, `author_user_id`, `note_text`, `visibility`, `updated_at`, `created_at`
              FROM `task_notes`
              WHERE `task_id` = :task_id AND (`author_user_id` = :viewer_id OR `visibility` = "public")
              ORDER BY `updated_at` DESC');
            $notesStmt->execute([':task_id' => $taskId, ':viewer_id' => $userId]);
            $notes = array_map(fn($row) => mapTaskNoteRow($row, $userId), $notesStmt->fetchAll() ?: []);
            jsonResponse(200, $notes);
        }

        if ($method === 'POST') {
            $text = trim((string) ($body['text'] ?? ''));
            if ($text === '') jsonResponse(400, ['message' => 'Note text is required']);
            $requestedVisibility = normalizeVisibility($body['visibility'] ?? 'private');
            $effectiveVisibility = $taskVisibility === 'public' ? 'private' : $requestedVisibility;
            $nowTs = (int) round(microtime(true) * 1000);
            $insert = $pdo->prepare('INSERT INTO `task_notes` (`task_id`, `author_user_id`, `note_text`, `visibility`, `updated_at`, `created_at`)
              VALUES (:task_id, :author_user_id, :note_text, :visibility, :updated_at, :created_at)');
            $insert->execute([
                ':task_id' => $taskId,
                ':author_user_id' => $userId,
                ':note_text' => $text,
                ':visibility' => $effectiveVisibility,
                ':updated_at' => $nowTs,
                ':created_at' => $nowTs
            ]);
            $newNoteId = (int) $pdo->lastInsertId();
            $selectNote = $pdo->prepare('SELECT `id`, `task_id`, `author_user_id`, `note_text`, `visibility`, `updated_at`, `created_at`
              FROM `task_notes` WHERE `id` = :id LIMIT 1');
            $selectNote->execute([':id' => $newNoteId]);
            $saved = $selectNote->fetch();
            if (!$saved) jsonResponse(500, ['message' => 'Failed to save note']);
            jsonResponse(200, mapTaskNoteRow($saved, $userId));
        }
    } else {
        // Individual note actions
        $selectNote = $pdo->prepare('SELECT * FROM `task_notes` WHERE `id` = :id AND `task_id` = :task_id LIMIT 1');
        $selectNote->execute([':id' => $noteId, ':task_id' => $taskId]);
        $note = $selectNote->fetch();
        if (!$note) jsonResponse(404, ['message' => 'Note not found']);
        
        $isAuthor = (int) $note['author_user_id'] === $userId;
        if (!$isAuthor) jsonResponse(403, ['message' => 'Only the author can modify this note']);

        if ($method === 'PATCH') {
            $text = isset($body['text']) ? trim((string) $body['text']) : null;
            $visibility = isset($body['visibility']) ? normalizeVisibility($body['visibility']) : null;
            
            if ($text === '') jsonResponse(400, ['message' => 'Note text cannot be empty']);
            
            $fields = [];
            $params = [':id' => $noteId];
            if ($text !== null) {
                $fields[] = '`note_text` = :text';
                $params[':text'] = $text;
            }
            if ($visibility !== null) {
                $effectiveVisibility = $taskVisibility === 'public' ? 'private' : $visibility;
                $fields[] = '`visibility` = :visibility';
                $params[':visibility'] = $effectiveVisibility;
            }
            
            if (empty($fields)) jsonResponse(400, ['message' => 'Nothing to update']);
            
            $nowTs = (int) round(microtime(true) * 1000);
            $fields[] = '`updated_at` = :now';
            $params[':now'] = $nowTs;
            
            $update = $pdo->prepare('UPDATE `task_notes` SET ' . implode(', ', $fields) . ' WHERE `id` = :id');
            $update->execute($params);
            
            $selectNote->execute([':id' => $noteId, ':task_id' => $taskId]);
            $updated = $selectNote->fetch();
            jsonResponse(200, mapTaskNoteRow($updated, $userId));
        }

        if ($method === 'DELETE') {
            $delete = $pdo->prepare('DELETE FROM `task_notes` WHERE `id` = :id');
            $delete->execute([':id' => $noteId]);
            jsonResponse(200, ['message' => 'Note deleted']);
        }
    }
}

if (count($segments) === 2 && $segments[0] === 'tasks') {
    $taskId = (string) $segments[1];
    $select = $pdo->prepare('SELECT `id`, `user_id`, `text`, `date`, `priority`, `category`, `visibility`, `playlist_name`, `video_url`, `thumbnail_url`, `description`, `caption_path`, `views`, `completed` FROM `tasks` WHERE `id` = :id LIMIT 1');
    $select->execute([':id' => $taskId]);
    $current = $select->fetch();
    if (!$current) jsonResponse(404, ['message' => 'Task not found']);
    $ownerId = (int) ($current['user_id'] ?? 0);
    $isOwner = $ownerId === $userId;
    $isPublic = ((string) ($current['visibility'] ?? 'private')) === 'public';

    if ($method === 'PATCH') {
        $requestedVisibility = isset($body['visibility']) ? normalizeVisibility($body['visibility']) : (string) $current['visibility'];
        $onlyCompletedToggle = array_key_exists('completed', $body) && count(array_diff(array_keys($body), ['completed'])) === 0;
        $onlyIncrementViews = (($body['incrementViews'] ?? false) === true) && count(array_diff(array_keys($body), ['incrementViews'])) === 0;

        if (!$isOwner) {
            if (!$isPublic || (!$onlyCompletedToggle && !$onlyIncrementViews)) {
                jsonResponse(403, ['message' => 'You can only update your own completion status or track views on public tasks']);
            }
            if ($onlyIncrementViews) {
                $inc = $pdo->prepare('UPDATE `tasks` SET `views` = `views` + 1 WHERE `id` = :id LIMIT 1');
                $inc->execute([':id' => $taskId]);
                $current['views'] = ((int) ($current['views'] ?? 0)) + 1;
                jsonResponse(200, mapTaskRow($current));
            }
            $nextCompleted = (bool) $body['completed'];
            upsertTaskCompletion($pdo, $taskId, $userId, $nextCompleted);
            $current['viewer_completed'] = $nextCompleted ? 1 : 0;
            jsonResponse(200, mapTaskRow($current));
        }

        if ($onlyIncrementViews) {
            $inc = $pdo->prepare('UPDATE `tasks` SET `views` = `views` + 1 WHERE `id` = :id AND `user_id` = :user_id');
            $inc->execute([':id' => $taskId, ':user_id' => $userId]);
            $current['views'] = ((int) ($current['views'] ?? 0)) + 1;
            jsonResponse(200, mapTaskRow($current));
        }

        $next = [
            'text' => isset($body['text']) ? trim((string) $body['text']) : (string) $current['text'],
            'date' => isset($body['date']) ? (string) $body['date'] : (string) $current['date'],
            'priority' => isset($body['priority']) ? (string) $body['priority'] : (string) $current['priority'],
            'category' => isset($body['category']) ? (string) $body['category'] : (string) $current['category'],
            'visibility' => $requestedVisibility,
            'video_url' => isset($body['videoUrl']) ? (string) $body['videoUrl'] : (string) ($current['video_url'] ?? ''),
            'thumbnail_url' => isset($body['thumbnailUrl']) ? (string) $body['thumbnailUrl'] : (string) ($current['thumbnail_url'] ?? ''),
            'description' => isset($body['description']) ? (string) $body['description'] : (string) ($current['description'] ?? ''),
            'completed' => isset($body['completed']) ? ((bool) $body['completed'] ? 1 : 0) : (int) $current['completed']
        ];
        if ($next['text'] === '') jsonResponse(400, ['message' => 'Task text is required']);
        
        $update = $pdo->prepare('UPDATE `tasks` SET `text` = :text, `date` = :date, `priority` = :priority, `category` = :category, `visibility` = :visibility, `video_url` = :video_url, `thumbnail_url` = :thumbnail_url, `description` = :description, `completed` = :completed WHERE `id` = :id AND `user_id` = :user_id');
        $update->execute([
            ':text' => $next['text'],
            ':date' => $next['date'],
            ':priority' => $next['priority'],
            ':category' => $next['category'],
            ':visibility' => $next['visibility'],
            ':video_url' => $next['video_url'],
            ':thumbnail_url' => $next['thumbnail_url'],
            ':description' => $next['description'],
            ':completed' => $next['completed'],
            ':id' => $taskId,
            ':user_id' => $userId
        ]);
        if ($next['visibility'] === 'public') {
            upsertTaskCompletion($pdo, $taskId, $userId, $next['completed'] === 1);
        }
        $updatedRow = [
            'id' => $taskId,
            'user_id' => $userId,
            'text' => $next['text'],
            'date' => $next['date'],
            'priority' => $next['priority'],
            'category' => $next['category'],
            'visibility' => $next['visibility'],
            'playlist_name' => (string) ($current['playlist_name'] ?? ''),
            'video_url' => $next['video_url'],
            'thumbnail_url' => $next['thumbnail_url'],
            'description' => $next['description'],
            'caption_path' => (string) ($current['caption_path'] ?? ''),
            'views' => (int) ($current['views'] ?? 0),
            'viewer_completed' => $next['completed']
        ];
        jsonResponse(200, mapTaskRow($updatedRow));
    }

    if ($method === 'DELETE') {
        if (!$isOwner) jsonResponse(403, ['message' => 'Only owner can delete this task']);
        $delete = $pdo->prepare('DELETE FROM `tasks` WHERE `id` = :id AND `user_id` = :user_id');
        $delete->execute([':id' => $taskId, ':user_id' => $userId]);
        $pdo->prepare('DELETE FROM `task_completions` WHERE `task_id` = :task_id')->execute([':task_id' => $taskId]);
        http_response_code(204);
        exit;
    }
}

if (count($segments) === 2 && $segments[0] === 'import' && $segments[1] === 'youtube-playlist' && $method === 'POST') {
    $playlistUrl = (string) ($body['url'] ?? '');
    $priority = trim((string) ($body['priority'] ?? ''));
    $category = trim((string) ($body['category'] ?? ''));
    $date = (string) ($body['date'] ?? '');
    $visibility = normalizeVisibility($body['visibility'] ?? 'private');
    $importLimit = resolveImportLimit($body['maxVideos'] ?? null);
    $playlistId = parsePlaylistId($playlistUrl);
    if ($playlistId === '') jsonResponse(400, ['message' => 'Valid YouTube playlist URL is required']);
    if ($priority === '') jsonResponse(400, ['message' => 'Priority is required for playlist import']);
    if ($category === '') jsonResponse(400, ['message' => 'Type is required for playlist import']);

    $videos = extractPlaylistVideosFromYouTubeApi($playlistId, $importLimit, $youtubeApiKey);
    $source = 'youtube-data-api';
    $partial = false;
    if (count($videos) === 0) {
        $videos = extractPlaylistVideosFromFeed($playlistId);
        $source = 'feed';
        $partial = true;
    }
    if (count($videos) === 0) jsonResponse(404, ['message' => 'No videos found in playlist']);

    $playlistName = (string) ($body['playlistName'] ?? ($body['playlist'] ?? ''));
    $playlistName = trim($playlistName);
    $existingStmt = $pdo->prepare('SELECT `text` FROM `tasks` WHERE `user_id` = :user_id AND (`playlist_name` = :playlist_name OR :playlist_name = "")');
    $existingStmt->execute([':user_id' => $userId, ':playlist_name' => $playlistName]);
    $existingRows = $existingStmt->fetchAll();
    $existing = [];
    foreach ($existingRows as $row) $existing[normalizeText((string) ($row['text'] ?? ''))] = true;

    $now = (int) round(microtime(true) * 1000);
    $imported = [];
    $index = 0;
    $insert = $pdo->prepare('INSERT INTO `tasks` (`id`, `user_id`, `text`, `date`, `priority`, `category`, `visibility`, `playlist_name`, `video_url`, `thumbnail_url`, `description`, `caption_path`, `views`, `completed`, `created_at`) VALUES (:id, :user_id, :text, :date, :priority, :category, :visibility, :playlist_name, :video_url, :thumbnail_url, :description, :caption_path, :views, :completed, :created_at)');
    foreach ($videos as $video) {
        $title = trim((string) ($video['title'] ?? ''));
        $normalized = normalizeText($title);
        if ($normalized === '' || isset($existing[$normalized])) continue;
        $existing[$normalized] = true;
        
        $videoId = (string) ($video['videoId'] ?? '');
        $captionPath = $videoId !== '' ? downloadCaptions($videoId) : null;

        $taskId = $now . '-' . $index . '-' . bin2hex(random_bytes(2));
        $record = [
            'id' => $taskId,
            'text' => $title,
            'date' => $date,
            'priority' => $priority,
            'category' => $category,
            'visibility' => $visibility,
            'playlistName' => $playlistName,
            'videoUrl' => trim((string) ($video['videoUrl'] ?? '')),
            'thumbnailUrl' => (string) ($video['thumbnailUrl'] ?? ''),
            'description' => (string) ($video['description'] ?? ''),
            'captionPath' => $captionPath,
            'views' => 0,
            'completed' => false
        ];
        $insert->execute([
            ':id' => $record['id'],
            ':user_id' => $userId,
            ':text' => $record['text'],
            ':date' => $record['date'],
            ':priority' => $record['priority'],
            ':category' => $record['category'],
            ':visibility' => $record['visibility'],
            ':playlist_name' => $record['playlistName'],
            ':video_url' => $record['videoUrl'],
            ':thumbnail_url' => $record['thumbnailUrl'],
            ':description' => $record['description'],
            ':caption_path' => $record['captionPath'],
            ':views' => 0,
            ':completed' => 0,
            ':created_at' => $now - $index
        ]);
        if ($record['visibility'] === 'public') {
            upsertTaskCompletion($pdo, $record['id'], $userId, false);
        }
        $imported[] = $record;
        $index++;
    }
    jsonResponse(201, [
        'importedCount' => count($imported),
        'tasks' => $imported,
        'requestedLimit' => $importLimit,
        'partial' => $partial,
        'source' => $source,
        'message' => $partial ? 'Imported available videos. Add YOUTUBE_API_KEY in .env for full playlist pagination.' : 'Playlist imported successfully'
    ]);
}

if (count($segments) === 1 && $segments[0] === 'playlists' && $method === 'GET') {
    $stmt = $pdo->prepare('SELECT `playlist_name` AS `name`, COUNT(*) AS `count` FROM `tasks` WHERE `user_id` = :user_id GROUP BY `playlist_name` ORDER BY `name` ASC');
    $stmt->execute([':user_id' => $userId]);
    $rows = $stmt->fetchAll();
    $result = [];
    foreach ($rows as $row) {
        $result[] = [
            'name' => (string) ($row['name'] ?? ''),
            'count' => (int) ($row['count'] ?? 0)
        ];
    }
    jsonResponse(200, $result);
}

if (count($segments) === 2 && $segments[0] === 'playlists' && $segments[1] === 'rename' && $method === 'POST') {
    $fromName = trim((string) ($body['fromName'] ?? ''));
    $toName = trim((string) ($body['toName'] ?? ''));
    if ($fromName === '' || $toName === '') jsonResponse(400, ['message' => 'Both fromName and toName are required']);
    $upd = $pdo->prepare('UPDATE `tasks` SET `playlist_name` = :to WHERE `user_id` = :user_id AND `playlist_name` = :from');
    $upd->execute([':to' => $toName, ':user_id' => $userId, ':from' => $fromName]);
    $count = $upd->rowCount();
    $nowTs = (int) round(microtime(true) * 1000);
    $prefUpd = $pdo->prepare('INSERT INTO `user_prefs` (`user_id`, `pref_key`, `pref_value`, `updated_at`, `created_at`) VALUES (:user_id, :k, :v, :u, :c)
      ON DUPLICATE KEY UPDATE `pref_value` = VALUES(`pref_value`), `updated_at` = VALUES(`updated_at`)');
    $sel = $pdo->prepare('SELECT `pref_value` FROM `user_prefs` WHERE `user_id` = :user_id AND `pref_key` = :k LIMIT 1');
    $sel->execute([':user_id' => $userId, ':k' => 'selected_playlist']);
    $pv = $sel->fetch();
    if ($pv && (string) ($pv['pref_value'] ?? '') === $fromName) {
        $prefUpd->execute([':user_id' => $userId, ':k' => 'selected_playlist', ':v' => $toName, ':u' => $nowTs, ':c' => $nowTs]);
    }
    jsonResponse(200, ['renamed' => $count, 'from' => $fromName, 'to' => $toName]);
}

if (count($segments) === 2 && $segments[0] === 'playlists' && $segments[1] === 'visibility' && $method === 'POST') {
    $name = trim((string) ($body['name'] ?? ''));
    if ($name === '') jsonResponse(400, ['message' => 'name is required']);
    $visibility = normalizeVisibility($body['visibility'] ?? 'private');
    $upd = $pdo->prepare('UPDATE `tasks` SET `visibility` = :visibility WHERE `user_id` = :user_id AND `playlist_name` = :name');
    $upd->execute([':visibility' => $visibility, ':user_id' => $userId, ':name' => $name]);
    $updated = $upd->rowCount();
    if ($visibility === 'public') {
        $sel = $pdo->prepare('SELECT `id` FROM `tasks` WHERE `user_id` = :user_id AND `playlist_name` = :name');
        $sel->execute([':user_id' => $userId, ':name' => $name]);
        $rows = $sel->fetchAll() ?: [];
        foreach ($rows as $row) {
            $taskId = (string) ($row['id'] ?? '');
            if ($taskId !== '') {
                upsertTaskCompletion($pdo, $taskId, $userId, false);
            }
        }
    }
    jsonResponse(200, ['updated' => $updated, 'name' => $name, 'visibility' => $visibility]);
}

if (count($segments) === 2 && $segments[0] === 'playlists' && $segments[1] === 'delete' && $method === 'POST') {
    $name = trim((string) ($body['name'] ?? ''));
    if ($name === '') jsonResponse(400, ['message' => 'name is required']);
    $upd = $pdo->prepare('UPDATE `tasks` SET `playlist_name` = "" WHERE `user_id` = :user_id AND `playlist_name` = :name');
    $upd->execute([':user_id' => $userId, ':name' => $name]);
    $cleared = $upd->rowCount();
    $nowTs = (int) round(microtime(true) * 1000);
    $prefUpd = $pdo->prepare('INSERT INTO `user_prefs` (`user_id`, `pref_key`, `pref_value`, `updated_at`, `created_at`) VALUES (:user_id, :k, :v, :u, :c)
      ON DUPLICATE KEY UPDATE `pref_value` = VALUES(`pref_value`), `updated_at` = VALUES(`updated_at`)');
    $prefUpd->execute([':user_id' => $userId, ':k' => 'selected_playlist', ':v' => 'all', ':u' => $nowTs, ':c' => $nowTs]);
    jsonResponse(200, ['cleared' => $cleared, 'name' => $name]);
}

function isAdmin(array $user): bool {
    return (string) ($user['role'] ?? 'user') === 'admin';
}

if (count($segments) === 1 && $segments[0] === 'admin' && $method === 'GET') {
    if (!isAdmin($authUser)) jsonResponse(403, ['message' => 'Admin access required']);
    
    // Dashboard Stats
    $totalUsers = (int) $pdo->query('SELECT COUNT(*) FROM `users`')->fetchColumn();
    $totalTasks = (int) $pdo->query('SELECT COUNT(*) FROM `tasks`')->fetchColumn();
    $totalPublicTasks = (int) $pdo->query('SELECT COUNT(*) FROM `tasks` WHERE `visibility` = "public"')->fetchColumn();
    $totalNotes = (int) $pdo->query('SELECT COUNT(*) FROM `task_notes`')->fetchColumn();
    
    // Recent Users
    $recentUsersStmt = $pdo->query('SELECT `id`, `name`, `email`, `role`, `status`, `is_verified`, `created_at` FROM `users` ORDER BY `created_at` DESC LIMIT 10');
    $recentUsers = $recentUsersStmt->fetchAll();
    
    // Site Settings
    $settingsStmt = $pdo->query('SELECT `setting_key`, `setting_value` FROM `site_settings`');
    $settings = [];
    foreach ($settingsStmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    jsonResponse(200, [
        'stats' => [
            'totalUsers' => $totalUsers,
            'totalTasks' => $totalTasks,
            'totalPublicTasks' => $totalPublicTasks,
            'totalNotes' => $totalNotes
        ],
        'recentUsers' => $recentUsers,
        'settings' => $settings
    ]);
}

if (count($segments) === 2 && $segments[0] === 'admin' && $segments[1] === 'settings' && $method === 'POST') {
    if (!isAdmin($authUser)) jsonResponse(403, ['message' => 'Admin access required']);
    $nowTs = (int) round(microtime(true) * 1000);
    $stmt = $pdo->prepare('INSERT INTO `site_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES (:key, :value, :updated_at)
        ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`), `updated_at` = VALUES(`updated_at`)');
    
    // Handle File Uploads first
    $uploadsDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0777, true);

    $fileUploaded = [];
    foreach (['LOGO_FILE', 'FAVICON_FILE'] as $fileKey) {
        if (isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] === UPLOAD_ERR_OK) {
            $ext = pathinfo($_FILES[$fileKey]['name'], PATHINFO_EXTENSION);
            $fileName = strtolower($fileKey) . '_' . time() . '.' . $ext;
            $targetPath = $uploadsDir . DIRECTORY_SEPARATOR . $fileName;
            if (move_uploaded_file($_FILES[$fileKey]['tmp_name'], $targetPath)) {
                $settingKey = str_replace('_FILE', '_URL', $fileKey);
                $body[$settingKey] = '/api/uploads/' . $fileName;
                $fileUploaded[$settingKey] = true;
            }
        }
    }

    // Ensure checkboxes are handled (if not in body, they are unchecked)
    $body['SMTP_ENABLED'] = isset($body['SMTP_ENABLED']) ? '1' : '0';
    $body['GOOGLE_LOGIN_ENABLED'] = isset($body['GOOGLE_LOGIN_ENABLED']) ? '1' : '0';

    foreach ($body as $key => $value) {
        // Skip file upload markers
        if (str_ends_with($key, '_FILE')) continue;
        
        // If it's a URL field, skip if value is empty AND we didn't just upload a file for it
        if (str_ends_with($key, '_URL') && trim((string)$value) === '' && !isset($fileUploaded[$key])) {
            continue;
        }
        
        $stmt->execute([
            ':key' => (string) $key,
            ':value' => (string) $value,
            ':updated_at' => $nowTs
        ]);
    }
    jsonResponse(200, ['message' => 'Settings updated successfully']);
}

if (count($segments) === 2 && $segments[0] === 'admin' && $segments[1] === 'logs' && $method === 'GET') {
    if (!isAdmin($authUser)) jsonResponse(403, ['message' => 'Admin access required']);
    
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = 50;
    $offset = ($page - 1) * $limit;
    
    $total = (int) $pdo->query('SELECT COUNT(*) FROM `user_activity_logs`')->fetchColumn();
    
    $stmt = $pdo->prepare('SELECT l.*, u.email as user_email 
        FROM `user_activity_logs` l 
        LEFT JOIN `users` u ON l.user_id = u.id 
        ORDER BY l.created_at DESC 
        LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll();
    
    jsonResponse(200, [
        'logs' => $logs,
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

if (count($segments) === 2 && $segments[0] === 'admin' && $segments[1] === 'users' && $method === 'GET') {
    if (!isAdmin($authUser)) jsonResponse(403, ['message' => 'Admin access required']);
    $stmt = $pdo->query('SELECT `id`, `name`, `email`, `role`, `status`, `is_verified`, `auth_provider`, `created_at` FROM `users` ORDER BY `created_at` DESC');
    jsonResponse(200, $stmt->fetchAll());
}

if (count($segments) === 3 && $segments[0] === 'admin' && $segments[1] === 'users' && $method === 'PATCH') {
    if (!isAdmin($authUser)) jsonResponse(403, ['message' => 'Admin access required']);
    $targetId = (int) $segments[2];
    
    $role = isset($body['role']) ? trim((string) $body['role']) : null;
    $status = isset($body['status']) ? trim((string) $body['status']) : null;
    $isVerified = isset($body['is_verified']) ? (int)$body['is_verified'] : null;
    
    if ($role && !in_array($role, ['user', 'admin'])) jsonResponse(400, ['message' => 'Invalid role']);
    if ($status && !in_array($status, ['active', 'suspended'])) jsonResponse(400, ['message' => 'Invalid status']);
    
    if ($role) {
        $stmt = $pdo->prepare('UPDATE `users` SET `role` = :role WHERE `id` = :id');
        $stmt->execute([':role' => $role, ':id' => $targetId]);
    }
    
    if ($status) {
        if ($targetId === $userId && $status === 'suspended') jsonResponse(400, ['message' => 'Cannot suspend yourself']);
        $stmt = $pdo->prepare('UPDATE `users` SET `status` = :status WHERE `id` = :id');
        $stmt->execute([':status' => $status, ':id' => $targetId]);
    }

    if ($isVerified !== null) {
        $stmt = $pdo->prepare('UPDATE `users` SET `is_verified` = :v, `verification_token` = NULL WHERE `id` = :id');
        $stmt->execute([':v' => $isVerified, ':id' => $targetId]);
    }
    
    jsonResponse(200, ['message' => 'User updated successfully']);
}

if (count($segments) === 3 && $segments[0] === 'admin' && $segments[1] === 'users' && $method === 'DELETE') {
    if (!isAdmin($authUser)) jsonResponse(403, ['message' => 'Admin access required']);
    $targetId = (int) $segments[2];
    if ($targetId === $userId) jsonResponse(400, ['message' => 'Cannot delete yourself']);
    
    $pdo->prepare('DELETE FROM `users` WHERE `id` = :id')->execute([':id' => $targetId]);
    $pdo->prepare('DELETE FROM `user_tokens` WHERE `user_id` = :id')->execute([':id' => $targetId]);
    $pdo->prepare('DELETE FROM `user_prefs` WHERE `user_id` = :id')->execute([':id' => $targetId]);
    jsonResponse(200, ['message' => 'User deleted successfully']);
}

if (count($segments) === 2 && $segments[0] === 'admin' && $segments[1] === 'tasks' && $method === 'GET') {
    if (!isAdmin($authUser)) jsonResponse(403, ['message' => 'Admin access required']);
    
    $search = trim((string) ($_GET['search'] ?? ''));
    $userSearch = trim((string) ($_GET['user_search'] ?? ''));
    $filter = trim((string) ($_GET['filter'] ?? 'all'));
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    $where = ['1=1'];
    $params = [];
    
    if ($search !== '') {
        $where[] = '(t.`id` = :id_search OR t.`text` LIKE :search OR t.`playlist_name` LIKE :search)';
        $params[':id_search'] = $search;
        $params[':search'] = "%$search%";
    }

    if ($userSearch !== '') {
        $where[] = '(u.`email` LIKE :user_search OR u.`name` LIKE :user_search)';
        $params[':user_search'] = "%$userSearch%";
    }
    
    if ($filter === 'public') {
        $where[] = 't.`visibility` = "public"';
    } elseif ($filter === 'private') {
        $where[] = 't.`visibility` = "private"';
    } elseif ($filter === 'playlist') {
        $where[] = 't.`playlist_name` != ""';
    }
    
    $whereClause = implode(' AND ', $where);
    
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM `tasks` t INNER JOIN `users` u ON u.`id` = t.`user_id` WHERE $whereClause");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT t.*, u.`name` as `owner_name`, u.`email` as `owner_email` 
        FROM `tasks` t 
        INNER JOIN `users` u ON u.`id` = t.`user_id` 
        WHERE $whereClause 
        ORDER BY t.`created_at` DESC 
        LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();
    
    jsonResponse(200, [
        'tasks' => array_map('mapTaskRow', $tasks),
        'pagination' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

if (count($segments) === 3 && $segments[0] === 'admin' && $segments[1] === 'tasks' && $method === 'PATCH') {
    if (!isAdmin($authUser)) jsonResponse(403, ['message' => 'Admin access required']);
    $targetId = (string) $segments[2];
    
    $check = $pdo->prepare('SELECT `id` FROM `tasks` WHERE `id` = :id LIMIT 1');
    $check->execute([':id' => $targetId]);
    if (!$check->fetch()) jsonResponse(404, ['message' => 'Task not found']);
    
    $fields = [];
    $params = [':id' => $targetId];
    
    $allowed = ['text', 'date', 'priority', 'category', 'visibility', 'completed', 'playlist_name'];
    foreach ($allowed as $f) {
        if (isset($body[$f])) {
            $dbField = $f === 'playlist_name' ? 'playlist_name' : $f;
            $fields[] = "`$dbField` = :$f";
            $params[":$f"] = ($f === 'completed') ? ($body[$f] ? 1 : 0) : $body[$f];
        }
    }
    
    if (count($fields) > 0) {
        $sql = "UPDATE `tasks` SET " . implode(', ', $fields) . " WHERE `id` = :id";
        $pdo->prepare($sql)->execute($params);
    }
    
    jsonResponse(200, ['message' => 'Task updated successfully']);
}

if (count($segments) === 3 && $segments[0] === 'admin' && $segments[1] === 'tasks' && $method === 'DELETE') {
    if (!isAdmin($authUser)) jsonResponse(403, ['message' => 'Admin access required']);
    $targetId = (string) $segments[2];
    
    $pdo->prepare('DELETE FROM `tasks` WHERE `id` = :id')->execute([':id' => $targetId]);
    $pdo->prepare('DELETE FROM `task_completions` WHERE `task_id` = :id')->execute([':id' => $targetId]);
    $pdo->prepare('DELETE FROM `task_notes` WHERE `task_id` = :id')->execute([':id' => $targetId]);
    
    jsonResponse(200, ['message' => 'Task deleted successfully']);
}

if (count($segments) === 4 && $segments[0] === 'admin' && $segments[1] === 'tasks' && $segments[3] === 'youtube-refresh' && $method === 'POST') {
    if (!isAdmin($authUser)) jsonResponse(403, ['message' => 'Admin access required']);
    $targetId = (string) $segments[2];
    
    $stmt = $pdo->prepare('SELECT `id`, `video_url` FROM `tasks` WHERE `id` = :id LIMIT 1');
    $stmt->execute([':id' => $targetId]);
    $task = $stmt->fetch();
    if (!$task || empty($task['video_url'])) jsonResponse(404, ['message' => 'Task with video URL not found']);
    
    $videoId = '';
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/', $task['video_url'], $match)) {
        $videoId = $match[1];
    }
    
    if ($videoId === '') jsonResponse(400, ['message' => 'Invalid YouTube URL']);
    
    // Refresh using YouTube Data API if key exists
    $youtubeApiKey = trim(envValue('YOUTUBE_API_KEY', ''));
    $newData = [];
    $tags = [];
    
    if ($youtubeApiKey !== '') {
        $url = "https://www.googleapis.com/youtube/v3/videos?part=snippet,contentDetails,statistics&id=" . urlencode($videoId) . "&key=" . $youtubeApiKey;
        $resp = @file_get_contents($url);
        if ($resp) {
            $decoded = json_decode($resp, true);
            if (!empty($decoded['items'][0])) {
                $snippet = $decoded['items'][0]['snippet'];
                $newData['text'] = $snippet['title'];
                $newData['description'] = $snippet['description'];
                $tags = $snippet['tags'] ?? [];
                $newData['tags'] = implode(', ', $tags);
                $thumbnails = $snippet['thumbnails'];
                $newData['thumbnail_url'] = $thumbnails['maxres']['url'] ?? $thumbnails['high']['url'] ?? $thumbnails['medium']['url'] ?? $thumbnails['default']['url'];
            }
        }
    }
    
    // Download captions
    $captionFile = downloadCaptions($videoId);
    if ($captionFile) {
        $newData['caption_path'] = $captionFile;
    }
    
    if (count($newData) > 0) {
        $fields = [];
        $params = [':id' => $targetId];
        foreach ($newData as $k => $v) {
            $fields[] = "`$k` = :$k";
            $params[":$k"] = $v;
        }
        $sql = "UPDATE `tasks` SET " . implode(', ', $fields) . " WHERE `id` = :id";
        $pdo->prepare($sql)->execute($params);
    }
    
    jsonResponse(200, ['message' => 'YouTube data refreshed', 'data' => $newData, 'tags' => $tags]);
}

jsonResponse(404, ['message' => 'Not found']);
