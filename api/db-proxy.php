<?php
/**
 * Database API Proxy
 * Upload file này lên hosting: public_html/api/db-proxy.php
 * 
 * Local app sẽ gọi HTTP request tới file này để truy cập database
 */

// ============= CẤU HÌNH =============
define('API_SECRET_KEY', 'IT_SOLO_LEVELING_DB_PROXY_2026'); // Đổi key này!
define('DB_HOST', 'localhost'); // Trên hosting, localhost = MySQL server
define('DB_NAME', 'tannhuyonline_tenuser_tuyendung');
define('DB_USER', 'tannhuyonline_trannhuy8425');
define('DB_PASS', 'fggjn8425');

// ============= CORS Headers =============
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');

// Preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ============= Xác thực API Key =============
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
if ($apiKey !== API_SECRET_KEY) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Invalid API key']);
    exit;
}

// ============= Chỉ cho phép POST =============
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// ============= Lấy request body =============
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body']);
    exit;
}

$action = $input['action'] ?? '';
$query = $input['query'] ?? '';
$params = $input['params'] ?? [];

// ============= Kết nối Database =============
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
    exit;
}

// ============= Xử lý Actions =============
try {
    switch ($action) {
        case 'select':
            // SELECT query
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $results = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $results, 'count' => count($results)]);
            break;

        case 'insert':
            // INSERT query
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'insertId' => $pdo->lastInsertId()]);
            break;

        case 'update':
        case 'delete':
            // UPDATE/DELETE query
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'affectedRows' => $stmt->rowCount()]);
            break;

        case 'tables':
            // Lấy danh sách tables
            $stmt = $pdo->query('SHOW TABLES');
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo json_encode(['success' => true, 'tables' => $tables]);
            break;

        case 'ping':
            // Test connection
            echo json_encode(['success' => true, 'message' => 'Database connected!', 'time' => date('Y-m-d H:i:s')]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Unknown action', 'valid_actions' => ['select', 'insert', 'update', 'delete', 'tables', 'ping']]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Query failed', 'message' => $e->getMessage()]);
}
