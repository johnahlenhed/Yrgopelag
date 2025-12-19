<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/config.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit();
}

$data = [
    'stars' => $_POST['stars'] ?? null,
    'discounts' => $_POST['discounts'] ?? null,
    'economy_price' => $_POST['economy_price'] ?? null,
    'standard_price' => $_POST['standard_price'] ?? null,
    'luxury_price' => $_POST['luxury_price'] ?? null,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['stars'], $_POST['discounts'])) {
        try {
            $stmt = $pdo->prepare('UPDATE settings SET value = :stars WHERE key = :key'); 
            $stmt->execute([
                ':stars' => $data['stars'],
                'key' => 'star_rating'
            ]);

            $stmt = $pdo->prepare('UPDATE settings SET value = :discounts WHERE key = :key');
            $stmt->execute([
                ':discounts' => $data['discounts'],
                'key' => 'loyalty_discount'
            ]);
            echo 'Hotel info updated successfully.';
        } catch (PDOException $e) {
            echo 'Error updating hotel info: ' . htmlspecialchars($e->getMessage());
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

            echo 'Room prices updated successfully.';
        } catch (PDOException $e) {
            echo 'Error updating room prices: ' . htmlspecialchars($e->getMessage());
        }
    }
}


require __DIR__ . '/../../includes/header.php';
?>

<h1>Admin page</h1>

<section>
    <form method="POST">
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
        
        <button type="submit">Save</button>

    </form>
</section>

<section>
    <h2>Room Prices</h2>
    <form method="POST">
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

<?php 
require __DIR__ . '/../../includes/footer.php';
?>

<?php
var_dump($data);
?>