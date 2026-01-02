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

        case 'sync_centralbank':
            try {
                $features = featureRepository::getAllFeatures($pdo);

                $payload = [
                    'user' => $centralBankConfig['user'],
                    'api_key' => $centralBankConfig['api_key'],
                    'islandName' => 'New Sweden',
                    'hotelName' => 'Borta bra, hemma bäst',
                    'url' => 'http://johnahlenhed.se/yrgopelag',
                    'stars' => (int) getSetting($pdo, 'star_rating'),
                    'features' => []
                ];
            
                foreach ($features as $feature) {
                    if ($feature['is_active']) {
                        $payload['features'][$feature['category']][$feature['tier']] = $feature['name'];
                    }
                }

                error_log("Syncing to Centralbank with payload: " . json_encode($payload, JSON_PRETTY_PRINT));

                $response = $cb->syncIsland($payload);

                error_log("Centralbank response: " . json_encode($response, JSON_PRETTY_PRINT));

                $successMessage = 'Centralbank synchronized successfully.';
            } catch (RuntimeException $e) {
                error_log("Centralbank sync error: " . $e->getMessage());
                $errorMessage = 'Error synchronizing with Centralbank: ' . htmlspecialchars($e->getMessage());
            }
            break;

        case 'update_settings':
            if (isset($_POST['stars'], $_POST['discounts'])) {
                try {
                    $stmt = $pdo->prepare('UPDATE settings SET value = :stars WHERE key = :key');
                    $stmt->execute([
                        ':stars' => $data['stars'],
                        ':key' => 'star_rating'
                    ]);

                    $stmt = $pdo->prepare('UPDATE settings SET value = :discounts WHERE key = :key');
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
            Discounts (%)
            <input type="number" name="discounts" min="0" max="100" step="1">
        </label>

        <button type="submit">Save Settings</button>
    </form>
</section>

<section>
    <h2>Room Prices</h2>
    <form method="POST">
        <input type="hidden" name="action" value="update_rooms">
        
        <label>
            Economy
            <input type="number" name="economy_price">
        </label>
        <label>
            Standard
            <input type="number" name="standard_price">
        </label>
        <label>
            Luxury
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
                
                <input type="hidden" name="feature_ids[]" value="<?php echo ($feature['id']); ?>">
                
                <label>
                    Price
                    <input type="number" name="prices[]" value="<?php echo ($feature['price']); ?>" min="0">
                </label>
                
                <label>
                    Enabled
                    <input type="checkbox" name="availabilities[]" value="<?php echo ($feature['id']); ?>" <?php echo $feature['is_active'] ? 'checked' : ''; ?>>
                </label>
            </fieldset>
        <?php endforeach; ?>
        
        <button type="submit">Update All Features</button>
    </form>
</section>

<section>
    <h2>Centralbanken</h2>
    <form method="POST">
        <input type="hidden" name="action" value="sync_centralbank">
        <button type="submit">Synchronize with Centralbank</button>
    </form>
</section>


<?php require __DIR__ . '/../../includes/footer.php'; ?>