<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

// Test database connection

$stmt = $pdo->query('SELECT * FROM rooms');
$rooms = $stmt->fetchAll();

echo '<pre>';
var_dump($rooms);