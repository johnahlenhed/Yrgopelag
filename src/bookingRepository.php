<?php

declare(strict_types=1);

final class bookingRepository
{
    public static function create(
        PDO $pdo,
        string $guestName,
        string $roomType,
        DateTime $arrival,
        DateTime $departure,
        int $totalPrice
    ): int {
        $stmt = $pdo->prepare(
            'INSERT INTO bookings (guest_name, room_type, arrival_date, departure_date, total_price, created_at) 
            VALUES (:guest_name, :room_type, :arrival_date, :departure_date, :total_price, CURRENT_TIMESTAMP)'
        );

        $stmt->execute([
            ':guest_name' => $guestName,
            ':room_type' => $roomType,
            ':arrival_date' => $arrival->format('Y-m-d H:i:s'),
            ':departure_date' => $departure->format('Y-m-d H:i:s'),
            ':total_price' => $totalPrice,
        ]);
        return (int)$pdo->lastInsertId();
    }
}