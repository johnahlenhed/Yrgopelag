<?php

declare(strict_types=1);

final class roomRepository
{
    public static function getAllRooms(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT * FROM rooms ORDER BY price ASC');
        return $stmt->fetchAll();
    }

    public static function getRoomPrices(PDO $pdo): array
    {
        $rooms = self::getAllRooms($pdo);
        $prices = [];
        foreach ($rooms as $room) {
            $prices[$room['type']] = (int)$room['price'];
    }
        return $prices;
    }

    public static function getRoomPriceByType(PDO $pdo, string $type): ?int
    {
        $stmt = $pdo->prepare('SELECT price FROM rooms WHERE type = :type');
        $stmt->execute([':type' => $type]);
        $price = $stmt->fetchColumn();
        return $price !== false ? (int)$price : null;
    }
}