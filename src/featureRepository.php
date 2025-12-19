<?php

declare(strict_types=1);

final class featureRepository
{
    public static function getByNames(PDO $pdo, array $names): array
    {
        if (empty($names)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($names), '?'));

        $stmt = $pdo->prepare(
            "SELECT * FROM features WHERE name IN ($placeholders)"
        );
        $stmt->execute($names);
        return $stmt->fetchAll();
    }

    public static function attachToBooking(PDO $pdo, int $bookingId, array $featureIds): void
    {
        $stmt = $pdo->prepare(
            'INSERT INTO booking_features (booking_id, feature_id) VALUES (:booking_id, :feature_id)'
        );

        foreach ($featureIds as $featureId) {
            $stmt->execute([
                ':booking_id' => $bookingId,
                ':feature_id' => $featureId,
            ]);
        }
    }
}