<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../src/bookingValidation.php';
require_once __DIR__ . '/../src/bookingRepository.php';
require_once __DIR__ . '/../src/featureRepository.php';
require_once __DIR__ . '/../src/roomRepository.php';
require_once __DIR__ . '/../src/centralBankClient.php';

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
if ($errors) {
    http_response_code(400);
    foreach ($errors as $error) {
        echo htmlspecialchars($error) . '<br>';
    }
    exit;
}

$selectedRooms = array_filter([
    'economy' => $data['economy_checkin'],
    'standard' => $data['standard_checkin'],
    'luxury' => $data['luxury_checkin'],
]);

if (count($selectedRooms) != 1) {
    exit('Please select only one room to book.');
}

$roomType = array_key_first($selectedRooms);

$checkinTime = new DateTime('15:00');
$arrival = new DateTime($data[$roomType . '_checkin'] . ' ' . $checkinTime->format('H:i'));
$departure = (clone $arrival)->modify('+20 hours');

// Check availability
if (bookingRepository::isDateBooked($pdo, $roomType, $arrival)) {
    http_response_code(409);
    exit('The date is already booked.');
}

// Validate and fetch features
$features = $_POST['features'] ?? [];
$featureRows = featureRepository::getByNames($pdo, $features);

// Validate that all features are active
foreach ($featureRows as $feature) {
    if (!$feature['is_active']) {
        http_response_code(400);
        exit('Feature "' . htmlspecialchars($feature['name']) . '" is no longer available.');
    }
}

// Price calculation
$featurePriceTotal = array_sum(array_column($featureRows, 'price'));
$roomPrice = roomRepository::getRoomPriceByType($pdo, $roomType);
$totalPrice = ($roomPrice ?? 0) + $featurePriceTotal;

// Validate transfer code with central bank
$config = require __DIR__ . '/../config/centralbank.php';
$cb = new CentralBankClient($config);

try {
    $cb->validateTransferCode($data['transfer_code'], $totalPrice);
} catch (RuntimeException $e) {
    http_response_code(400);
    exit('Invalid transfer code: ' . htmlspecialchars($e->getMessage()));
}

// Create booking in database
try {
    $bookingId = BookingRepository::create(
        $pdo,
        $data['name'],
        $roomType,
        $arrival,
        $departure,
        $totalPrice
    );

    // Attach features to booking
    $featureIds = array_column($featureRows, 'id');
    FeatureRepository::attachToBooking($pdo, $bookingId, $featureIds);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Failed to create booking: ' . htmlspecialchars($e->getMessage()));
}

// Deposit funds to hotel account
try {
    $cb->deposit($data['transfer_code']);
} catch (RuntimeException $e) {
    http_response_code(400);
    exit('Payment failed: ' . htmlspecialchars($e->getMessage()));
}

// Send receipt to Central Bank
$featuresUsed = array_map(
    fn($f) => [
        'activity' => $f['activity'],
        'tier' => $f['tier'],
    ],
    $featureRows
);

$starRating = (int)getSetting($pdo, 'star_rating');

try {
    $cb->sendReceipt(
        $data['name'],
        $arrival->format('Y-m-d'),
        $departure->format('Y-m-d'),
        $featuresUsed,
        $starRating
    );
} catch (RuntimeException $e) {
    error_log('Failed to send receipt to Central Bank: ' . $e->getMessage());
}

?>

<?php require __DIR__ . '/../includes/header.php'; ?>

<section>
    <h1>Booking Confirmation</h1>
    <p>Your booking has been received. We hope you enjoy your stay.</p>

    <h3>Make sure you visit our bar "Bolaget".</h3>
</section>


<?php require __DIR__ . '/../includes/footer.php'; ?>