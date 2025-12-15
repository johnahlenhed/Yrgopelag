<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bookingValidation.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

$data = [
    'economy_checkin' => $_POST['economy_checkin'] ?? null,
    'standard_checkin' => $_POST['standard_checkin'] ?? null,
    'luxury_checkin' => $_POST['luxury_checkin'] ?? null,
    'name' => $_POST['name'] ?? null,
    'transfer_code' => $_POST['transfer_code'] ?? null,
];

$errors = bookingValidation::validateBookingData($data);

if (!empty($errors)) {
    http_response_code(400);
    foreach ($errors as $error) {
        echo htmlspecialchars($error) . '<br>';
    }
    exit;
}

var_dump($data);