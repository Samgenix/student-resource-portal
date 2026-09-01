<?php
// www/inc/db.php
// Docker credentials
$DB_HOST = 'db';           // Compose service name
$DB_NAME = 'student_portal';
$DB_USER = 'sp_user';
$DB_PASS = 'sp_pass';

$dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500); // to tell apache this is a server error 
    error_log('DB connection failed: ' . $e->getMessage()); // to force PHP error to print into logs
    exit('DB connection failed: ' . $e->getMessage());
}
