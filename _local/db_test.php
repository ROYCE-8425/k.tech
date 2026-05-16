<?php

$dsn = 'mysql:host=127.0.0.1;port=3307;dbname=recruitment_app;charset=utf8mb4';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->query('SELECT 1');
    echo "DB CONNECT OK\n";
} catch (Throwable $e) {
    fwrite(STDERR, "DB CONNECT FAIL: {$e->getMessage()}\n");
    exit(1);
}
