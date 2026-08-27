<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../src/featureRepository.php';
require_once __DIR__ . '/../../src/centralBankClient.php';
require_once __DIR__ . '/../../src/AdminDashboardService.php';
require_once __DIR__ . '/../../config/helpers.php';

$centralBankConfig = require __DIR__ . '/../../config/centralbank.php';
$cb = new centralBankClient($centralBankConfig);
$dashboardService = new AdminDashboardService($pdo, $cb);

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit();
}

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrfVerify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Invalid or expired form submission. Please go back and try again.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = match ($_POST['action'] ?? null) {
        'fetch_from_centralbank' => $dashboardService->fetchFromCentralbank(),
        'update_settings' => isset($_POST['stars'], $_POST['discounts'])
            ? $dashboardService->updateSettings((int)$_POST['stars'], (int)$_POST['discounts'])
            : null,
        'update_rooms' => isset($_POST['economy_price'], $_POST['standard_price'], $_POST['luxury_price'])
            ? $dashboardService->updateRooms(
                (int)$_POST['economy_price'],
                (int)$_POST['standard_price'],
                (int)$_POST['luxury_price']
            )
            : null,
        'update_features' => isset($_POST['feature_ids']) && is_array($_POST['feature_ids'])
            ? $dashboardService->updateFeatures($_POST['feature_ids'], $_POST['prices'] ?? [], $_POST['availabilities'] ?? [])
            : null,
        default => null,
    };

    if ($result !== null) {
        if ($result['success']) {
            $successMessage = $result['message'];
        } else {
            $errorMessage = $result['message'];
        }
    }
}

try {
    $features = featureRepository::getAllFeatures($pdo);
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
        <?php echo csrfField(); ?>
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
        <?php echo csrfField(); ?>
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
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="update_features">

        <?php 
        // Group features by category
        $groupedFeatures = [];
        foreach ($features as $feature) {
            $category = $feature['category'];
            if (!isset($groupedFeatures[$category])) {
                $groupedFeatures[$category] = [];
            }
            $groupedFeatures[$category][] = $feature;
        }
        
        // Sort each category by tier (economy -> basic -> premium -> superior)
        $tierOrder = ['economy' => 1, 'basic' => 2, 'premium' => 3, 'superior' => 4];
        foreach ($groupedFeatures as $category => $categoryFeatures) {
            usort($categoryFeatures, function($a, $b) use ($tierOrder) {
                return ($tierOrder[$a['tier']] ?? 99) <=> ($tierOrder[$b['tier']] ?? 99);
            });
            $groupedFeatures[$category] = $categoryFeatures;
        }
        
        // Display order for categories
        $categoryOrder = ['water', 'games', 'wheels', 'hotel-specific'];
        $categoryLabels = [
            'water' => '💧 Water Activities',
            'games' => '🎮 Games',
            'wheels' => '🚲 Wheels',
            'hotel-specific' => '🏨 Hotel-Specific'
        ];
        ?>

        <?php foreach ($categoryOrder as $category): ?>
            <?php if (isset($groupedFeatures[$category])): ?>
                <div class="feature-category">
                    <h3><?php echo $categoryLabels[$category]; ?></h3>
                    
                    <?php foreach ($groupedFeatures[$category] as $feature): ?>
                        <fieldset class="feature-item">
                            <legend>
                                <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $feature['name']))); ?>
                                <span class="tier-badge tier-<?php echo htmlspecialchars($feature['tier']); ?>">
                                    <?php echo ucfirst($feature['tier']); ?>
                                </span>
                            </legend>

                            <input type="hidden" name="feature_ids[]" value="<?php echo (int)$feature['id']; ?>">

                            <div class="feature-controls">
                                <label>
                                    Price ($)
                                    <input type="number" name="prices[]" value="<?php echo (int)$feature['price']; ?>" min="0" max="100">
                                </label>

                                <label class="checkbox-label">
                                    <input type="checkbox" name="availabilities[]" value="<?php echo (int)$feature['id']; ?>" <?php echo $feature['is_active'] ? 'checked' : ''; ?>>
                                    <span>Enabled</span>
                                </label>
                            </div>
                        </fieldset>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <button type="submit">Update All Features</button>
    </form>
</section>

<section>
    <h2>Centralbanken</h2>

    <form method="POST" style="display: inline-block; margin-right: 10px;">
        <?php echo csrfField(); ?>
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