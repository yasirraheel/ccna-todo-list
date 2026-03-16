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
            'videoUrl' => $videoId !== '' ? 'https://www.youtube.com/watch?v=' . urlencode($videoId) : ''
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
            $title = trim((string) ($item['snippet']['title'] ?? ''));
            if ($title === '' || $title === 'Private video' || $title === 'Deleted video') continue;
            $videoId = (string) ($item['snippet']['resourceId']['videoId'] ?? '');
            $videos[] = [
                'title' => $title,
                'videoUrl' => $videoId !== '' ? 'https://www.youtube.com/watch?v=' . urlencode($videoId) : ''
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

function resolveImportLimit($value): int {
    $default = 300;
    $max = 500;
    if ($value === null || $value === '') return $default;
    $parsed = (int) $value;
    if ($parsed < 1) return $default;
    return min($parsed, $max);
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
    $pdo->exec('CREATE TABLE IF NOT EXISTS `tasks` (
      `id` VARCHAR(64) NOT NULL,
      `user_id` BIGINT UNSIGNED NOT NULL,
      `text` VARCHAR(1000) NOT NULL,
      `date` VARCHAR(32) NOT NULL DEFAULT "",
      `priority` VARCHAR(16) NOT NULL DEFAULT "medium",
      `category` VARCHAR(32) NOT NULL DEFAULT "personal",
      `video_url` TEXT NULL,
      `completed` TINYINT(1) NOT NULL DEFAULT 0,
      `created_at` BIGINT NOT NULL,
      PRIMARY KEY (`id`),
      INDEX `idx_tasks_user_created` (`user_id`, `created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
}

function mapTaskRow(array $row): array {
    return [
        'id' => (string) ($row['id'] ?? ''),
        'text' => (string) ($row['text'] ?? ''),
        'date' => (string) ($row['date'] ?? ''),
        'priority' => (string) ($row['priority'] ?? 'medium'),
        'category' => (string) ($row['category'] ?? 'personal'),
        'videoUrl' => (string) ($row['video_url'] ?? ''),
        'completed' => ((int) ($row['completed'] ?? 0)) === 1
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

$authUser = getAuthenticatedUser($pdo);
$userId = (int) $authUser['id'];

if (count($segments) === 1 && $segments[0] === 'tasks' && $method === 'GET') {
    $stmt = $pdo->prepare('SELECT `id`, `text`, `date`, `priority`, `category`, `video_url`, `completed` FROM `tasks` WHERE `user_id` = :user_id ORDER BY `created_at` DESC');
    $stmt->execute([':user_id' => $userId]);
    jsonResponse(200, array_map('mapTaskRow', $stmt->fetchAll() ?: []));
}

if (count($segments) === 1 && $segments[0] === 'tasks' && $method === 'POST') {
    $text = trim((string) ($body['text'] ?? ''));
    if ($text === '') jsonResponse(400, ['message' => 'Task text is required']);
    $now = (int) round(microtime(true) * 1000);
    $task = [
        'id' => (string) $now . '-' . bin2hex(random_bytes(3)),
        'text' => $text,
        'date' => (string) ($body['date'] ?? ''),
        'priority' => (string) ($body['priority'] ?? 'medium'),
        'category' => (string) ($body['category'] ?? 'personal'),
        'videoUrl' => (string) ($body['videoUrl'] ?? ''),
        'completed' => (bool) ($body['completed'] ?? false)
    ];
    $insert = $pdo->prepare('INSERT INTO `tasks` (`id`, `user_id`, `text`, `date`, `priority`, `category`, `video_url`, `completed`, `created_at`) VALUES (:id, :user_id, :text, :date, :priority, :category, :video_url, :completed, :created_at)');
    $insert->execute([
        ':id' => $task['id'],
        ':user_id' => $userId,
        ':text' => $task['text'],
        ':date' => $task['date'],
        ':priority' => $task['priority'],
        ':category' => $task['category'],
        ':video_url' => $task['videoUrl'],
        ':completed' => $task['completed'] ? 1 : 0,
        ':created_at' => $now
    ]);
    jsonResponse(201, $task);
}

if (count($segments) === 1 && $segments[0] === 'tasks' && $method === 'DELETE') {
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM `tasks` WHERE `user_id` = :user_id');
    $countStmt->execute([':user_id' => $userId]);
    $deletedCount = (int) $countStmt->fetchColumn();
    $deleteStmt = $pdo->prepare('DELETE FROM `tasks` WHERE `user_id` = :user_id');
    $deleteStmt->execute([':user_id' => $userId]);
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
    jsonResponse(200, ['deletedCount' => $deletedCount]);
}

if (count($segments) === 2 && $segments[0] === 'tasks') {
    $taskId = (string) $segments[1];
    $select = $pdo->prepare('SELECT `id`, `text`, `date`, `priority`, `category`, `video_url`, `completed` FROM `tasks` WHERE `id` = :id AND `user_id` = :user_id LIMIT 1');
    $select->execute([':id' => $taskId, ':user_id' => $userId]);
    $current = $select->fetch();
    if (!$current) jsonResponse(404, ['message' => 'Task not found']);

    if ($method === 'PATCH') {
        $next = [
            'text' => isset($body['text']) ? trim((string) $body['text']) : (string) $current['text'],
            'date' => isset($body['date']) ? (string) $body['date'] : (string) $current['date'],
            'priority' => isset($body['priority']) ? (string) $body['priority'] : (string) $current['priority'],
            'category' => isset($body['category']) ? (string) $body['category'] : (string) $current['category'],
            'video_url' => isset($body['videoUrl']) ? (string) $body['videoUrl'] : (string) ($current['video_url'] ?? ''),
            'completed' => isset($body['completed']) ? ((bool) $body['completed'] ? 1 : 0) : (int) $current['completed']
        ];
        if ($next['text'] === '') jsonResponse(400, ['message' => 'Task text is required']);
        $update = $pdo->prepare('UPDATE `tasks` SET `text` = :text, `date` = :date, `priority` = :priority, `category` = :category, `video_url` = :video_url, `completed` = :completed WHERE `id` = :id AND `user_id` = :user_id');
        $update->execute([
            ':text' => $next['text'],
            ':date' => $next['date'],
            ':priority' => $next['priority'],
            ':category' => $next['category'],
            ':video_url' => $next['video_url'],
            ':completed' => $next['completed'],
            ':id' => $taskId,
            ':user_id' => $userId
        ]);
        jsonResponse(200, [
            'id' => $taskId,
            'text' => $next['text'],
            'date' => $next['date'],
            'priority' => $next['priority'],
            'category' => $next['category'],
            'videoUrl' => $next['video_url'],
            'completed' => $next['completed'] === 1
        ]);
    }

    if ($method === 'DELETE') {
        $delete = $pdo->prepare('DELETE FROM `tasks` WHERE `id` = :id AND `user_id` = :user_id');
        $delete->execute([':id' => $taskId, ':user_id' => $userId]);
        http_response_code(204);
        exit;
    }
}

if (count($segments) === 2 && $segments[0] === 'import' && $segments[1] === 'youtube-playlist' && $method === 'POST') {
    $playlistUrl = (string) ($body['url'] ?? '');
    $priority = trim((string) ($body['priority'] ?? ''));
    $category = trim((string) ($body['category'] ?? ''));
    $date = (string) ($body['date'] ?? '');
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

    $existingStmt = $pdo->prepare('SELECT `text` FROM `tasks` WHERE `user_id` = :user_id');
    $existingStmt->execute([':user_id' => $userId]);
    $existingRows = $existingStmt->fetchAll();
    $existing = [];
    foreach ($existingRows as $row) $existing[normalizeText((string) ($row['text'] ?? ''))] = true;

    $now = (int) round(microtime(true) * 1000);
    $imported = [];
    $index = 0;
    $insert = $pdo->prepare('INSERT INTO `tasks` (`id`, `user_id`, `text`, `date`, `priority`, `category`, `video_url`, `completed`, `created_at`) VALUES (:id, :user_id, :text, :date, :priority, :category, :video_url, :completed, :created_at)');
    foreach ($videos as $video) {
        $title = trim((string) ($video['title'] ?? ''));
        $normalized = normalizeText($title);
        if ($normalized === '' || isset($existing[$normalized])) continue;
        $existing[$normalized] = true;
        $taskId = $now . '-' . $index . '-' . bin2hex(random_bytes(2));
        $record = [
            'id' => $taskId,
            'text' => $title,
            'date' => $date,
            'priority' => $priority,
            'category' => $category,
            'videoUrl' => trim((string) ($video['videoUrl'] ?? '')),
            'completed' => false
        ];
        $insert->execute([
            ':id' => $record['id'],
            ':user_id' => $userId,
            ':text' => $record['text'],
            ':date' => $record['date'],
            ':priority' => $record['priority'],
            ':category' => $record['category'],
            ':video_url' => $record['videoUrl'],
            ':completed' => 0,
            ':created_at' => $now + $index
        ]);
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

jsonResponse(404, ['message' => 'Not found']);
