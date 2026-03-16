<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

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
    $value = getenv($key);
    return $value === false ? $default : (string) $value;
}

function jsonResponse(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || trim($raw) === '') return [];
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function normalizeText(string $value): string {
    return mb_strtolower(trim($value), 'UTF-8');
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
    $dbName = trim(envValue('DB_NAME', ''));
    $dbUser = trim(envValue('DB_USER', ''));
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
        jsonResponse(500, ['message' => 'Failed to connect database']);
    }
    return $pdo;
}

function ensureTables(PDO $pdo): void {
    $pdo->exec('CREATE TABLE IF NOT EXISTS `users` (
      `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
      `name` VARCHAR(120) NOT NULL,
      `email` VARCHAR(190) NOT NULL,
      `password_hash` VARCHAR(255) NOT NULL,
      `created_at` BIGINT NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_users_email` (`email`)
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
      `caption_path` TEXT NULL,
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
        $pdo->exec('ALTER TABLE `tasks` ADD COLUMN `caption_path` TEXT NULL');
    } catch (Throwable $e) {}
    try {
        $pdo->exec('ALTER TABLE `tasks` ADD COLUMN `visibility` VARCHAR(16) NOT NULL DEFAULT "private"');
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
        'captionPath' => (string) ($row['caption_path'] ?? ''),
        'ownerId' => isset($row['owner_id']) ? (int) $row['owner_id'] : (isset($row['user_id']) ? (int) $row['user_id'] : 0),
        'ownerEmail' => (string) ($row['owner_email'] ?? ''),
        'completed' => $resolvedCompleted === 1
    ];
}

function getAuthorizationToken(): string {
    $header = '';
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) $header = (string) $_SERVER['HTTP_AUTHORIZATION'];
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
    $token = getAuthorizationToken();
    if ($token === '') jsonResponse(401, ['message' => 'Unauthorized']);
    $now = (int) round(microtime(true) * 1000);
    $stmt = $pdo->prepare('SELECT u.`id`, u.`name`, u.`email` FROM `user_tokens` t INNER JOIN `users` u ON u.`id` = t.`user_id` WHERE t.`token` = :token AND t.`expires_at` > :now LIMIT 1');
    $stmt->execute([':token' => $token, ':now' => $now]);
    $user = $stmt->fetch();
    if (!$user) jsonResponse(401, ['message' => 'Unauthorized']);
    return [
        'id' => (int) $user['id'],
        'name' => (string) $user['name'],
        'email' => (string) $user['email']
    ];
}

$projectRoot = dirname(__DIR__);
loadEnvFile($projectRoot . DIRECTORY_SEPARATOR . '.env');
$publicApiBase = rtrim(envValue('PUBLIC_API_BASE_URL', ''), '/');
$youtubeApiKey = trim(envValue('YOUTUBE_API_KEY', ''));
$pdo = dbConnect();
ensureTables($pdo);

$segments = getApiPathSegments();
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$body = getJsonBody();

if (count($segments) === 1 && $segments[0] === 'config' && $method === 'GET') {
    jsonResponse(200, ['apiBaseUrl' => $publicApiBase, 'runtime' => 'php']);
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
    $now = (int) round(microtime(true) * 1000);
    $insert = $pdo->prepare('INSERT INTO `users` (`name`, `email`, `password_hash`, `created_at`) VALUES (:name, :email, :password_hash, :created_at)');
    $insert->execute([
        ':name' => $name,
        ':email' => $email,
        ':password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ':created_at' => $now
    ]);
    $userId = (int) $pdo->lastInsertId();
    $token = issueUserToken($pdo, $userId);
    jsonResponse(201, ['token' => $token, 'user' => ['id' => $userId, 'name' => $name, 'email' => $email]]);
}

if (count($segments) === 2 && $segments[0] === 'auth' && $segments[1] === 'login' && $method === 'POST') {
    $email = mb_strtolower(trim((string) ($body['email'] ?? '')), 'UTF-8');
    $password = (string) ($body['password'] ?? '');
    if ($email === '' || $password === '') jsonResponse(400, ['message' => 'Email and password are required']);
    $stmt = $pdo->prepare('SELECT `id`, `name`, `email`, `password_hash` FROM `users` WHERE `email` = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        jsonResponse(401, ['message' => 'Invalid email or password']);
    }
    $token = issueUserToken($pdo, (int) $user['id']);
    jsonResponse(200, [
        'token' => $token,
        'user' => ['id' => (int) $user['id'], 'name' => (string) $user['name'], 'email' => (string) $user['email']]
    ]);
}

if (count($segments) === 2 && $segments[0] === 'auth' && $segments[1] === 'me' && $method === 'GET') {
    $user = getAuthenticatedUser($pdo);
    jsonResponse(200, ['user' => $user]);
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

$authUser = getAuthenticatedUser($pdo);
$userId = (int) $authUser['id'];

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
        $stmt = $pdo->prepare('SELECT t.`id`, t.`user_id`, t.`text`, t.`date`, t.`priority`, t.`category`, t.`visibility`, t.`playlist_name`, t.`video_url`, t.`thumbnail_url`, t.`description`, t.`caption_path`,
            CASE WHEN t.`visibility` = "public" THEN COALESCE(tc.`completed`, 0) ELSE t.`completed` END AS `viewer_completed`
            FROM `tasks` t
            LEFT JOIN `task_completions` tc ON tc.`task_id` = t.`id` AND tc.`user_id` = :viewer_id
            WHERE t.`user_id` = :user_id AND t.`playlist_name` = :playlist
            ORDER BY t.`created_at` DESC');
        $stmt->execute([':viewer_id' => $userId, ':user_id' => $userId, ':playlist' => $playlist]);
    } else {
        $stmt = $pdo->prepare('SELECT t.`id`, t.`user_id`, t.`text`, t.`date`, t.`priority`, t.`category`, t.`visibility`, t.`playlist_name`, t.`video_url`, t.`thumbnail_url`, t.`description`, t.`caption_path`,
            CASE WHEN t.`visibility` = "public" THEN COALESCE(tc.`completed`, 0) ELSE t.`completed` END AS `viewer_completed`
            FROM `tasks` t
            LEFT JOIN `task_completions` tc ON tc.`task_id` = t.`id` AND tc.`user_id` = :viewer_id
            WHERE t.`user_id` = :user_id
            ORDER BY t.`created_at` DESC');
        $stmt->execute([':viewer_id' => $userId, ':user_id' => $userId]);
    }
    jsonResponse(200, array_map('mapTaskRow', $stmt->fetchAll() ?: []));
}

if (count($segments) === 2 && $segments[0] === 'tasks' && $segments[1] === 'public' && $method === 'GET') {
    $playlist = trim((string) ($_GET['playlist'] ?? ''));
    if ($playlist !== '' && strtolower($playlist) !== 'all') {
        $stmt = $pdo->prepare('SELECT t.`id`, t.`user_id`, t.`user_id` AS `owner_id`, u.`email` AS `owner_email`, t.`text`, t.`date`, t.`priority`, t.`category`, t.`visibility`, t.`playlist_name`, t.`video_url`, t.`thumbnail_url`, t.`description`, t.`caption_path`,
            COALESCE(tc.`completed`, 0) AS `viewer_completed`
            FROM `tasks` t
            INNER JOIN `users` u ON u.`id` = t.`user_id`
            LEFT JOIN `task_completions` tc ON tc.`task_id` = t.`id` AND tc.`user_id` = :viewer_id
            WHERE t.`visibility` = "public" AND t.`playlist_name` = :playlist
            ORDER BY t.`created_at` DESC');
        $stmt->execute([':viewer_id' => $userId, ':playlist' => $playlist]);
    } else {
        $stmt = $pdo->prepare('SELECT t.`id`, t.`user_id`, t.`user_id` AS `owner_id`, u.`email` AS `owner_email`, t.`text`, t.`date`, t.`priority`, t.`category`, t.`visibility`, t.`playlist_name`, t.`video_url`, t.`thumbnail_url`, t.`description`, t.`caption_path`,
            COALESCE(tc.`completed`, 0) AS `viewer_completed`
            FROM `tasks` t
            INNER JOIN `users` u ON u.`id` = t.`user_id`
            LEFT JOIN `task_completions` tc ON tc.`task_id` = t.`id` AND tc.`user_id` = :viewer_id
            WHERE t.`visibility` = "public"
            ORDER BY t.`created_at` DESC');
        $stmt->execute([':viewer_id' => $userId]);
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
        'completed' => (bool) ($body['completed'] ?? false)
    ];
    $insert = $pdo->prepare('INSERT INTO `tasks` (`id`, `user_id`, `text`, `date`, `priority`, `category`, `visibility`, `playlist_name`, `video_url`, `thumbnail_url`, `description`, `completed`, `created_at`) VALUES (:id, :user_id, :text, :date, :priority, :category, :visibility, :playlist_name, :video_url, :thumbnail_url, :description, :completed, :created_at)');
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

if (count($segments) === 2 && $segments[0] === 'tasks') {
    $taskId = (string) $segments[1];
    $select = $pdo->prepare('SELECT `id`, `user_id`, `text`, `date`, `priority`, `category`, `visibility`, `playlist_name`, `video_url`, `thumbnail_url`, `description`, `caption_path`, `completed` FROM `tasks` WHERE `id` = :id LIMIT 1');
    $select->execute([':id' => $taskId]);
    $current = $select->fetch();
    if (!$current) jsonResponse(404, ['message' => 'Task not found']);
    $ownerId = (int) ($current['user_id'] ?? 0);
    $isOwner = $ownerId === $userId;
    $isPublic = ((string) ($current['visibility'] ?? 'private')) === 'public';

    if ($method === 'PATCH') {
        $requestedVisibility = isset($body['visibility']) ? normalizeVisibility($body['visibility']) : (string) $current['visibility'];
        $onlyCompletedToggle = array_key_exists('completed', $body) && count(array_diff(array_keys($body), ['completed'])) === 0;

        if (!$isOwner) {
            if (!$isPublic || !$onlyCompletedToggle) {
                jsonResponse(403, ['message' => 'You can only update your own completion status on public tasks']);
            }
            $nextCompleted = (bool) $body['completed'];
            upsertTaskCompletion($pdo, $taskId, $userId, $nextCompleted);
            $current['viewer_completed'] = $nextCompleted ? 1 : 0;
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
        $effectiveCompleted = $next['visibility'] === 'public' ? 0 : $next['completed'];
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
            ':completed' => $effectiveCompleted,
            ':id' => $taskId,
            ':user_id' => $userId
        ]);
        if ($next['visibility'] === 'public') {
            upsertTaskCompletion($pdo, $taskId, $userId, $next['completed'] === 1);
            $nextCompletedForViewer = $next['completed'] === 1;
        } else {
            $nextCompletedForViewer = $effectiveCompleted === 1;
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
            'viewer_completed' => $nextCompletedForViewer ? 1 : 0
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
    $insert = $pdo->prepare('INSERT INTO `tasks` (`id`, `user_id`, `text`, `date`, `priority`, `category`, `visibility`, `playlist_name`, `video_url`, `thumbnail_url`, `description`, `caption_path`, `completed`, `created_at`) VALUES (:id, :user_id, :text, :date, :priority, :category, :visibility, :playlist_name, :video_url, :thumbnail_url, :description, :caption_path, :completed, :created_at)');
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

jsonResponse(404, ['message' => 'Not found']);
