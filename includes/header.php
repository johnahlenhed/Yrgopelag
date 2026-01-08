<?php

// Display star rating
$stmt = $pdo->prepare('SELECT value FROM settings WHERE `key` = :key');
$stmt->execute([':key' => 'star_rating']);
$starRating = $stmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/public/css/styles.css">
    <link rel="stylesheet" href="/public/css/booking-calendar.css">
    <link rel="stylesheet" href="/public/css/bolaget.css">
    <link rel=stylesheet href="/public/css/dashboard.css">
    <link rel="icon" href="/public/images/borta-bra-logo2.png">
    <title>Borta bra, hemma bäst!</title>
</head>

<body>

    <header>
        <nav>
            <a href="/public/bolaget.php">Bolaget</a>
            <a href="/public/index.php"><img src="/public/images/borta-bra-logo.png" alt="Hotel Logo" /></a>
            <a href="/public/admin/login.php">Login</a>

            <?php if ($starRating): ?>
                <div class="star-rating">
                    <h3>Star rating:</h3>
                    <?php for ($i = 0; $i < $starRating; $i++): ?>
                        <span class="star">&#9733;</span>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </nav>
    </header>