<?php
$envFile = __DIR__ . '/.env';

if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$name, $value] = explode('=', $line, 2);
        $_ENV[$name] = trim($value);
        putenv("$name=$value");
    }
}

session_start();

try {
    $dbUrl = getenv('DATABASE_URL');

    if (!$dbUrl) {
        die("DATABASE_URL not found");
    }

    $url = parse_url($dbUrl);

    $host = $url['host'];
    $port = $url['port'] ?? 5432;
    $db   = ltrim($url['path'], '/');
    $user = $url['user'];
    $pass = $url['pass'];

    $endpoint = explode('.', $host)[0];

    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require;options=endpoint=$endpoint";

    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

/* ================== FUNCTIONS ================== */

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login_neon.php ");
        exit();
    }
}

function generateOrderNumber($conn) {
    $year = date('Y');
    $month = date('m');
    $prefix = "ORD{$year}{$month}";

    $sql = "SELECT order_number FROM orders 
            WHERE order_number LIKE :prefix 
            ORDER BY order_number DESC 
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->execute(['prefix' => $prefix . '%']);
    $result = $stmt->fetch();

    $newNumber = $result
        ? intval(substr($result['order_number'], -4)) + 1
        : 1;

    return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
}

function thaiDate($date) {
    if (empty($date)) return '';

    $thaiMonths = [
        '01'=>'ม.ค.','02'=>'ก.พ.','03'=>'มี.ค.',
        '04'=>'เม.ย.','05'=>'พ.ค.','06'=>'มิ.ย.',
        '07'=>'ก.ค.','08'=>'ส.ค.','09'=>'ก.ย.',
        '10'=>'ต.ค.','11'=>'พ.ย.','12'=>'ธ.ค.'
    ];

    $dateArr = explode('-', $date);

    if (count($dateArr) == 3) {
        return intval($dateArr[2]) . " "
            . $thaiMonths[$dateArr[1]] . " "
            . ($dateArr[0] + 543);
    }

    return $date;
}

function executeQuery($conn, $sql, $params = []) {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function fetchAll($conn, $sql, $params = []) {
    return executeQuery($conn, $sql, $params)->fetchAll();
}

function fetchOne($conn, $sql, $params = []) {
    return executeQuery($conn, $sql, $params)->fetch();
}