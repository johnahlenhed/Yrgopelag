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
        // Rows start 'pending': reserved, but not yet confirmed as paid.
        $stmt = $pdo->prepare(
            'INSERT INTO bookings (guest_name, room_type, arrival_date, departure_date, total_price, status, created_at)
            VALUES (:guest_name, :room_type, :arrival_date, :departure_date, :total_price, \'pending\', CURRENT_TIMESTAMP)'
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

    // No money has moved for this booking yet, so it's safe to free the date.
    public static function delete(PDO $pdo, int $bookingId): void
    {
        $stmt = $pdo->prepare('DELETE FROM bookings WHERE id = :id');
        $stmt->execute([':id' => $bookingId]);
    }

    // Persists the transfer code as soon as money has moved, so it's never
    // held only in a PHP variable that could be lost if the process dies
    // before deposit() runs.
    public static function setTransferCode(PDO $pdo, int $bookingId, string $transferCode): void
    {
        $stmt = $pdo->prepare('UPDATE bookings SET transfer_code = :transfer_code WHERE id = :id');
        $stmt->execute([':transfer_code' => $transferCode, ':id' => $bookingId]);
    }

    public static function markConfirmed(PDO $pdo, int $bookingId): void
    {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = :id");
        $stmt->execute([':id' => $bookingId]);
    }

    // Money already moved (or was validated) but deposit() failed. Centralbank
    // exposes no refund/reversal endpoint, so this can't be auto-compensated --
    // the booking is kept, flagged for manual follow-up, with its transfer_code
    // preserved so the deposit can be retried or the guest contacted.
    public static function markPaymentFailed(PDO $pdo, int $bookingId): void
    {
        $stmt = $pdo->prepare("UPDATE bookings SET status = 'payment_failed' WHERE id = :id");
        $stmt->execute([':id' => $bookingId]);
    }

    public static function getBookedDatesByRoom(PDO $pdo): array
    {
        $stmt = $pdo->query(
            "SELECT room_type, arrival_date FROM bookings WHERE arrival_date BETWEEN '2026-01-01' AND '2026-01-31'"
        );

        $blockedDates = [
            'economy' => [],
            'standard' => [],
            'luxury' => [],
        ];

        foreach ($stmt->fetchAll() as $row) {
            $blockedDates[$row['room_type']][] = substr($row['arrival_date'], 0, 10);
        }

        return $blockedDates;
    }

    public static function isDateBooked(PDO $pdo, string $roomType, DateTime $arrivalDate): bool
    {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM bookings WHERE room_type = :room_type AND DATE(arrival_date) = :arrival_date LIMIT 1'
        );

        $stmt->execute([':room_type' => $roomType, ':arrival_date' => $arrivalDate->format('Y-m-d')]);

        return (bool)$stmt->fetchColumn();
    }

    public static function getBookingCountByGuest(PDO $pdo, string $guestName): int
    {
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM bookings WHERE guest_name = :guest_name'
        );

        $stmt->execute([':guest_name' => $guestName]);

        return (int)$stmt->fetchColumn();
    }

    public static function isReturningCustomer(PDO $pdo, string $guestName): bool
    {
        return self::getBookingCountByGuest($pdo, $guestName) > 0;
    }
}
