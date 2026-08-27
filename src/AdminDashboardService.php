<?php

declare(strict_types=1);

// Backs the admin dashboard's actions (settings/rooms/features/Centralbank
// sync). Keeps that logic out of public/admin/dashboard.php, which only
// dispatches $_POST['action'] to these methods and renders the result.
final class AdminDashboardService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly CentralBankClient $cb
    ) {
    }

    /** @return array{success: bool, message: string} */
    public function fetchFromCentralbank(): array
    {
        try {
            $response = $this->cb->getIslandFeatures();
            error_log('Centralbank island data fetched: ' . count($response['features'] ?? []) . ' feature(s)');

            if (isset($response['island']['stars'])) {
                $stmt = $this->pdo->prepare('UPDATE settings SET value = :stars WHERE `key` = :key');
                $stmt->execute([
                    ':stars' => (string)$response['island']['stars'],
                    ':key' => 'star_rating',
                ]);
            }

            $centralBankFeatures = $response['features'] ?? [];
            $localFeatures = featureRepository::getAllFeatures($this->pdo);

            $activeFeatures = [];
            foreach ($centralBankFeatures as $feature) {
                $activeFeatures[$feature['activity'] . '_' . $feature['tier']] = true;
            }

            foreach ($localFeatures as $localFeature) {
                $key = $localFeature['activity'] . '_' . $localFeature['tier'];
                featureRepository::updateFeature(
                    $this->pdo,
                    (int)$localFeature['id'],
                    (int)$localFeature['price'],
                    isset($activeFeatures[$key])
                );
            }

            return ['success' => true, 'message' => 'Data fetched from Centralbank and local database updated.'];
        } catch (Exception $e) {
            error_log('Centralbank fetch error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Error fetching from Centralbank: ' . $e->getMessage()];
        }
    }

    /** @return array{success: bool, message: string} */
    public function updateSettings(int $stars, int $discount): array
    {
        $stars = max(1, min(5, $stars));
        $discount = max(0, min(100, $discount));

        try {
            $stmt = $this->pdo->prepare('UPDATE settings SET value = :stars WHERE `key` = :key');
            $stmt->execute([':stars' => $stars, ':key' => 'star_rating']);

            $stmt = $this->pdo->prepare('UPDATE settings SET value = :discounts WHERE `key` = :key');
            $stmt->execute([':discounts' => $discount, ':key' => 'loyalty_discount']);

            return ['success' => true, 'message' => 'Hotel info updated successfully.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error updating hotel info: ' . $e->getMessage()];
        }
    }

    /** @return array{success: bool, message: string} */
    public function updateRooms(int $economyPrice, int $standardPrice, int $luxuryPrice): array
    {
        try {
            $stmt = $this->pdo->prepare('UPDATE rooms SET price = :price WHERE type = :type');
            $stmt->execute([':price' => max(0, $economyPrice), ':type' => 'economy']);
            $stmt->execute([':price' => max(0, $standardPrice), ':type' => 'standard']);
            $stmt->execute([':price' => max(0, $luxuryPrice), ':type' => 'luxury']);

            return ['success' => true, 'message' => 'Room prices updated successfully.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error updating room prices: ' . $e->getMessage()];
        }
    }

    /**
     * @param string[] $featureIds
     * @param string[] $prices
     * @param string[] $availabilities
     * @return array{success: bool, message: string}
     */
    public function updateFeatures(array $featureIds, array $prices, array $availabilities): array
    {
        try {
            foreach ($featureIds as $index => $featureId) {
                $price = (int)($prices[$index] ?? 0);
                $enabled = in_array($featureId, $availabilities);
                featureRepository::updateFeature($this->pdo, (int)$featureId, $price, $enabled);
            }

            return ['success' => true, 'message' => 'All features updated successfully.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error updating features: ' . $e->getMessage()];
        }
    }
}
