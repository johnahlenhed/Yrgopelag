<?php

declare(strict_types=1);

final class featureRepository
{
    // Booking
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

    // Fetch active features for booking page
    public static function getActiveFeaturesByCategory(PDO $pdo): array
    {
        $stmt = $pdo->query(
            'SELECT * FROM features WHERE is_active = 1 ORDER BY category, tier'
        );
        $features = $stmt->fetchAll();

        $groupedFeatures = [];
        foreach ($features as $feature) {
            $groupedFeatures[$feature['category']][] = $feature;
        }
        return $groupedFeatures;
    }
    

    // Admin
    public static function getAllFeatures(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT * FROM features ORDER BY category, tier');
        return $stmt->fetchAll();
    }

    public static function updateFeature(PDO $pdo, int $featureId, int $price, bool $enabled): void
    {
        $stmt = $pdo->prepare(
                'UPDATE features SET price = :price, is_active = :is_active WHERE id = :id'
            );

        $stmt->execute([
            ':price' => $price,
            ':is_active' => $enabled ? 1 : 0,
            ':id' => $featureId,
        ]);
    }
}
