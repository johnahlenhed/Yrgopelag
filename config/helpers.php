<?php

declare(strict_types=1);

function getSetting(PDO $pdo, string $key): ?string
{
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE key = :key');
    $stmt->execute([':key' => $key]);
    return $stmt->fetchColumn() ?: null;
}