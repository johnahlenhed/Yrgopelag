<?php

declare(strict_types=1);

/**
 * Get a setting value from the database
 */
function getSetting(PDO $pdo, string $key): ?string
{
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE key = :key');
    $stmt->execute([':key' => $key]);
    $result = $stmt->fetchColumn();
    return $result !== false ? $result : null;
}

/**
 * Sync features from Centralbank to local database
 */
function syncFeaturesFromCentralbank(PDO $pdo, CentralBankClient $cb): void
{
    try {
        // Get features from Centralbank
        $response = $cb->getIslandFeatures();
        $centralbankFeatures = $response['features'] ?? [];
        
        // Get all features from local database
        $localFeatures = featureRepository::getAllFeatures($pdo);
        
        // Create a map of centralbank features for quick lookup
        $activeFeatures = [];
        foreach ($centralbankFeatures as $feature) {
            $key = $feature['activity'] . '_' . $feature['tier'];
            $activeFeatures[$key] = true;
        }
        
        // Update local database to match Centralbank
        foreach ($localFeatures as $localFeature) {
            $key = $localFeature['activity'] . '_' . $localFeature['tier'];
            $isActive = isset($activeFeatures[$key]);
            
            // Update the feature's active status
            featureRepository::updateFeature(
                $pdo,
                (int)$localFeature['id'],
                (int)$localFeature['price'],
                $isActive
            );
        }
        
        error_log("Features synced from Centralbank successfully");
    } catch (Exception $e) {
        error_log("Error syncing features from Centralbank: " . $e->getMessage());
        throw $e;
    }
}