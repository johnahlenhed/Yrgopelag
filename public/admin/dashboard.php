<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/featureRepository.php';
require_once __DIR__ . '/../../src/centralBankClient.php';
require_once __DIR__ . '/../../config/helpers.php';

$centralBankConfig = require __DIR__ . '/../../config/centralbank.php';
$cb = new centralBankClient($centralBankConfig);

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit();
}

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'stars' => $_POST['stars'] ?? null,
        'discounts' => $_POST['discounts'] ?? null,
        'economy_price' => $_POST['economy_price'] ?? null,
        'standard_price' => $_POST['standard_price'] ?? null,
        'luxury_price' => $_POST['luxury_price'] ?? null,
    ];

    switch ($_POST['action'] ?? null) {
        case 'fetch_from_centralbank':
            try {
                $response = $cb->getIslandFeatures();

                error_log("Centralbank island data: " . json_encode($response, JSON_PRETTY_PRINT));

                // Update star rating
                if (isset($response['island']['stars'])) {
                    $stmt = $pdo->prepare('UPDATE settings SET value = :stars WHERE `key` = :key');
                    $stmt->execute([
                        ':stars' => (string)$response['island']['stars'],
                        ':key' => 'star_rating'
                    ]);
                }

                // Sync features
                $centralBankFeatures = $response['features'] ?? [];
                $localFeatures = featureRepository::getAllFeatures($pdo);

                $activeFeatures = [];
                foreach ($centralBankFeatures as $feature) {
                    $key = $feature['activity'] . '_' . $feature['tier'];
                    $activeFeatures[$key] = true;
                }

                // Update local database based on Centralbank data
                foreach ($localFeatures as $localFeature) {
                    $key = $localFeature['activity'] . '_' . $localFeature['tier'];
                    $isActive = isset($activeFeatures[$key]);

                    featureRepository::updateFeature(
                        $pdo,
                        (int)$localFeature['id'],
                        (int)$localFeature['price'],
                        $isActive
                    );
                }
                $successMessage = 'Data fetched from Centralbank and local database updated.';
            } catch (Exception $e) {
                error_log("Centralbank fetch error: " . $e->getMessage());
                $errorMessage = 'Error fetching from Centralbank: ' . htmlspecialchars($e->getMessage());
            }
            break;

        case 'update_settings':
            if (isset($_POST['stars'], $_POST['discounts'])) {
                try {
                    $stmt = $pdo->prepare('UPDATE settings SET value = :stars WHERE `key` = :key');
                    $stmt->execute([
                        ':stars' => $data['stars'],
                        ':key' => 'star_rating'
                    ]);

                    $stmt = $pdo->prepare('UPDATE settings SET value = :discounts WHERE `key` = :key');
                    $stmt->execute([
                        ':discounts' => $data['discounts'],
                        ':key' => 'loyalty_discount'
                    ]);
                    $successMessage = 'Hotel info updated successfully.';
                } catch (PDOException $e) {
                    $errorMessage = 'Error updating hotel info: ' . htmlspecialchars($e->getMessage());
                }
            }
            break;

        case 'update_rooms':
            if (isset($_POST['economy_price'], $_POST['standard_price'], $_POST['luxury_price'])) {
                try {
                    $stmt = $pdo->prepare('UPDATE rooms SET price = :price WHERE type = :type');

                    $stmt->execute([
                        ':price' => $data['economy_price'],
                        ':type' => 'economy'
                    ]);

                    $stmt->execute([
                        ':price' => $data['standard_price'],
                        ':type' => 'standard'
                    ]);

                    $stmt->execute([
                        ':price' => $data['luxury_price'],
                        ':type' => 'luxury'
                    ]);

                    $successMessage = 'Room prices updated successfully.';
                } catch (PDOException $e) {
                    $errorMessage = 'Error updating room prices: ' . htmlspecialchars($e->getMessage());
                }
            }
            break;

        case 'update_features':
            if (isset($_POST['feature_ids']) && is_array($_POST['feature_ids'])) {
                try {
                    $featureIds = $_POST['feature_ids'];
                    $prices = $_POST['prices'] ?? [];
                    $availabilities = $_POST['availabilities'] ?? [];

                    foreach ($featureIds as $index => $featureId) {
                        $price = (int)($prices[$index] ?? 0);
                        $enabled = in_array($featureId, $availabilities);

                        featureRepository::updateFeature($pdo, (int)$featureId, $price, $enabled);
                    }

                    $successMessage = 'All features updated successfully.';
                } catch (PDOException $e) {
                    $errorMessage = 'Error updating features: ' . htmlspecialchars($e->getMessage());
                }
            }
            break;
    }
}

try {
    $features = featureRepository::getAllFeatures($pdo);
    error_log("Features count: " . count($features));
    error_log("Features: " . print_r($features, true));
} catch (Exception $e) {
    error_log("Error fetching features: " . $e->getMessage());
    $features = [];
}

$currentStars = (int)getSetting($pdo, 'star_rating');
$currentDiscount = (int)getSetting($pdo, 'loyalty_discount');

require __DIR__ . '/../../includes/header.php';
?>

<h1>Admin page</h1>

<?php if ($successMessage): ?>
    <div class="success-message">
        <?php echo htmlspecialchars($successMessage); ?>
    </div>
<?php endif; ?>

<?php if ($errorMessage): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($errorMessage); ?>
    </div>
<?php endif; ?>

<section>
    <h2>Hotel Settings</h2>
    <p class="current-settings">
        Current: <?php echo $currentStars; ?> | Discount: <?php echo $currentDiscount; ?>%
    </p>
    <form method="POST">
        <input type="hidden" name="action" value="update_settings">

        <label>
            Star rating
            <select name="stars">
                <option value="1">1 Star</option>
                <option value="2">2 Stars</option>
                <option value="3">3 Stars</option>
                <option value="4">4 Stars</option>
                <option value="5">5 Stars</option>
            </select>
        </label>

        <label>
            Loyalty Discount (%)
            <input type="number" name="discounts" min="0" max="100" step="1" value="<?php echo $currentDiscount; ?>" required>
        </label>

        <button type="submit">Save Settings</button>
    </form>
</section>

<section>
    <h2>Room Prices</h2>
    <form method="POST">
        <input type="hidden" name="action" value="update_rooms">

        <label>
            Economy (Current price: <?php
                $stmt = $pdo->prepare('SELECT price FROM rooms WHERE type = :type');
                $stmt->execute([':type' => 'economy']);
                $economyPrice = $stmt->fetchColumn();
                echo htmlspecialchars((string)$economyPrice); ?>)
            <input type="number" name="economy_price">
        </label>
        <label>
            Standard (Current price: <?php
                $stmt = $pdo->prepare('SELECT price FROM rooms WHERE type = :type');
                $stmt->execute([':type' => 'standard']);
                $standardPrice = $stmt->fetchColumn();
                echo htmlspecialchars((string)$standardPrice); ?>)
            <input type="number" name="standard_price">
        </label>
        <label>
            Luxury (Current price: <?php
                $stmt = $pdo->prepare('SELECT price FROM rooms WHERE type = :type');
                $stmt->execute([':type' => 'luxury']);
                $luxuryPrice = $stmt->fetchColumn();
                echo htmlspecialchars((string)$luxuryPrice); ?>)
            <input type="number" name="luxury_price">
        </label>

        <button type="submit">Update Prices</button>
    </form>
</section>

<section>
    <h2>Features</h2>
    <form method="POST">
        <input type="hidden" name="action" value="update_features">

        <?php foreach ($features as $feature): ?>
            <fieldset>
                <legend><?php echo htmlspecialchars($feature['name']); ?></legend>

                <input type="hidden" name="feature_ids[]" value="<?php echo (int)$feature['id']; ?>">

                <label>
                    Price
                    <input type="number" name="prices[]" value="<?php echo (int)$feature['price']; ?>" min="0">
                </label>

                <label>
                    Enabled
                    <input type="checkbox" name="availabilities[]" value="<?php echo (int)$feature['id']; ?>" <?php echo $feature['is_active'] ? 'checked' : ''; ?>>
                </label>
            </fieldset>
        <?php endforeach; ?>

        <button type="submit">Update All Features</button>
    </form>
</section>

<section>
    <h2>Centralbanken</h2>

    <form method="POST" style="display: inline-block; margin-right: 10px;">
        <input type="hidden" name="action" value="fetch_from_centralbank">
        <button type="submit" style="background-color: #18d41eff;">Fetch from Centralbank</button>
    </form>

    <p class="info-text">
        <strong>Note:</strong> To add or modify features at Centralbank, please use the
        <a href="https://www.yrgopelag.se/centralbank/" target="_blank">Centralbank UI</a> directly.
        Use "Fetch" above to sync those changes to your local database.
    </p>
</section>

<?php require __DIR__ . '/../../includes/footer.php'; ?>