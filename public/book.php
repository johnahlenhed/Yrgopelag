<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/bookingValidation.php';
require_once __DIR__ . '/../src/bookingRepository.php';

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

$selectedRooms = array_filter([
    'economy' => $data['economy_checkin'],
    'standard' => $data['standard_checkin'],
    'luxury' => $data['luxury_checkin'],
]);

$checkinTime = new DateTime('15:00');
$arrival = new DateTime($data[array_key_first($selectedRooms) . '_checkin'] . ' ' . $checkinTime->format('H:i'));
$departure = (clone $arrival)->modify('+8 hours');

$roomType = array_key_first($selectedRooms);

// For demonstration, we use a fixed price. In a real application, fetch the price from the database.
$price = 10;

bookingRepository::save(
    $pdo, 
    $data, 
    $roomType,
    $price
);

if (count($selectedRooms) != 1) {
    exit('Please select only one room to book.');
}

$errors = bookingValidation::validateBookingData($data);

if (!empty($errors)) {
    http_response_code(400);
    foreach ($errors as $error) {
        echo htmlspecialchars($error) . '<br>';
    }
    exit;
}

var_dump($data);
var_dump($selectedRooms); ?>

<?php require __DIR__ . '/../includes/header.php'; ?>

<section>
    <h1>Booking Confirmation</h1>
    <p>Your booking has been received. We hope you enjoy your stay.</p>

    <h3>Make sure you visit our bar "Bolaget".</h3>
</section>


<?php require __DIR__ . '/../includes/footer.php'; ?>