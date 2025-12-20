<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/bookingValidation.php';
require_once __DIR__ . '/../src/bookingRepository.php';
require_once __DIR__ . '/../src/featureRepository.php';
require_once __DIR__ . '/../src/roomRepository.php';

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

if (count($selectedRooms) != 1) {
    exit('Please select only one room to book.');
}

$checkinTime = new DateTime('15:00');
$arrival = new DateTime($data[array_key_first($selectedRooms) . '_checkin'] . ' ' . $checkinTime->format('H:i'));
$departure = (clone $arrival)->modify('+20 hours');



$errors = bookingValidation::validateBookingData($data);
if ($errors) {
    http_response_code(400);
    foreach ($errors as $error) {
        echo htmlspecialchars($error) . '<br>';
    }
    exit;
}

$roomType = array_key_first($selectedRooms);

// Validate and fetch features
$features = $_POST['features'] ?? [];

$featureRows = featureRepository::getByNames($pdo, $features);

// Price calculation
$featurePriceTotal = array_sum(array_column($featureRows, 'price'));
$roomPrice = roomRepository::getRoomPriceByType($pdo, $roomType);

$price = ($roomPrice ?? 0) + $featurePriceTotal;

// Create booking
$bookingId = BookingRepository::create(
    $pdo,
    $guestName = $data['name'],
    $roomType,
    $arrival,
    $departure,
    $totalPrice = $price
);

// Attach features to booking
$featureIds = array_column($featureRows, 'id');
FeatureRepository::attachToBooking($pdo, $bookingId, $featureIds);


if (!empty($errors)) {
    http_response_code(400);
    foreach ($errors as $error) {
        echo htmlspecialchars($error) . '<br>';
    }
    exit;
}

?>

<?php require __DIR__ . '/../includes/header.php'; ?>

<section>
    <h1>Booking Confirmation</h1>
    <p>Your booking has been received. We hope you enjoy your stay.</p>

    <h3>Make sure you visit our bar "Bolaget".</h3>
</section>


<?php require __DIR__ . '/../includes/footer.php'; ?>