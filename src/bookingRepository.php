<?php

declare(strict_types=1);

final class bookingRepository
{
    public static function save(pdo $pdo, array $data, string $roomType, int $price): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO bookings (guest_name, room_type, arrival_date, departure_date, total_price, created_at)
            VALUES (:name, :room_type, :arrival_date, :departure_date, :total_price, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':room_type' => $roomType,
            ':arrival_date' => $data[$roomType . '_checkin'],
            ':departure_date' => $data[$roomType . '_checkin'],
            ':total_price' => $price,
        ]);
    }
}