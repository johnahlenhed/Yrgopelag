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

    public static function getBookedDatesByRoom(PDO $pdo): array
    {
        $stmt = $pdo->query(
            "SELECT room_type, arrival_date AS date from bookings WHERE arrival_date BETWEEN '2026-01-01' AND '2026-01-31'"
        );

        $blockedDates = [
            'economy' => [],
            'standard' => [],
            'luxury' => [],
        ];

        foreach ($stmt->fetchAll()as $row) {
            $blockedDates[$row['room_type']][] = $row = ['date'];
        }

        return $blockedDates;
    }
}