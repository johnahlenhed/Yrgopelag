<?php

declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../config/config.php';

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit();
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
    </form>
</section>

<?php 
require __DIR__ . '/../../includes/footer.php';
?>