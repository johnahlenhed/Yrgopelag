<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Database connection - works with both SQLite and MySQL
try {
    $driver = $_ENV['DB_DRIVER'] ?? 'sqlite';
    
    if ($driver === 'mysql') {
        // MySQL connection (production on one.com)
        $pdo = new PDO(
            $_ENV['DB_DSN'],
            $_ENV['DB_USER'],
            $_ENV['DB_PASS'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    } else {
        // SQLite connection (local development)
        $dsn = 'sqlite:' . __DIR__ . '/../database/hotel.sqlite';
        $pdo = new PDO($dsn);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    die('Database connection failed: ' . $e->getMessage());
}