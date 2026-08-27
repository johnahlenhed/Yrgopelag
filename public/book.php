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

header('Cache-Control: no-store');

if (!csrfVerify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Your session expired. Please go back and submit the form again.');
}

$data = [
    'economy_checkin' => $_POST['economy_checkin'] ?? null,
    'standard_checkin' => $_POST['standard_checkin'] ?? null,
    'luxury_checkin' => $_POST['luxury_checkin'] ?? null,
    'name' => $_POST['name'] ?? null,
    'payment_method' => $_POST['payment_method'] ?? null,
    'transfer_code' => $_POST['transfer_code'] ?? null,
    'guest_api_key' => $_POST['guest_api_key'] ?? null,
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

if (count($selectedRooms) !== 1) {
    http_response_code(400);
    exit('Please select only one room to book.');
}

$roomType = array_key_first($selectedRooms);

$arrival = DateTime::createFromFormat('Y-m-d H:i', $data[$roomType . '_checkin'] . ' 15:00');
$departure = (clone $arrival)->modify('+20 hours');

// Check availability
if (bookingRepository::isDateBooked($pdo, $roomType, $arrival)) {
    http_response_code(409);
    exit('The date is already booked.');
}

// Validate and fetch features
$features = $_POST['features'] ?? [];
if (!is_array($features)) {
    http_response_code(400);
    exit('Invalid features submitted.');
}
$featureRows = featureRepository::getByNames($pdo, $features);

// Validate that all features are active
foreach ($featureRows as $feature) {
    if (!$feature['is_active']) {
        http_response_code(400);
        exit('Feature "' . htmlspecialchars($feature['name']) . '" is no longer available.');
    }
}

// Check if returning customer for discount
$isReturningCustomer = bookingRepository::isReturningCustomer($pdo, $data['name']);
$previousBookings = bookingRepository::getBookingCountByGuest($pdo, $data['name']);
$loyaltyDiscount = (int)getSetting($pdo, 'loyalty_discount');

// Price calculation
$featurePriceTotal = array_sum(array_column($featureRows, 'price'));
$roomPrice = roomRepository::getRoomPriceByType($pdo, $roomType);
$subtotal = ($roomPrice ?? 0) + $featurePriceTotal;

// Apply discount if applicable
$discountAmount = 0;
if ($isReturningCustomer && $previousBookings >= 1) {
    $discountAmount = (int)ceil($subtotal * ($loyaltyDiscount / 100));
}

$totalPrice = $subtotal - $discountAmount;

// Validate transfer code with central bank
$config = require __DIR__ . '/../config/centralbank.php';
$cb = new CentralBankClient($config);

// Handle payment method
$usedTransferCodeService = false;

if ($data['payment_method'] === 'service') {
    // TransferCode Service - create code for guest
    if (empty($data['guest_api_key'])) {
        http_response_code(400);
        exit('API key is required for TransferCode Service.');
    }

    // Throttle Centralbank credential attempts from this session.
    $attempts = $_SESSION['cb_service_attempts'] ?? [];
    $attempts = array_filter($attempts, fn($t) => $t > time() - 60);
    if (count($attempts) >= 10) {
        http_response_code(429);
        exit('Too many attempts. Please wait a minute and try again.');
    }
    $attempts[] = time();
    $_SESSION['cb_service_attempts'] = $attempts;

    try {
        // Create transferCode using guest's API key
        $transferCode = $cb->createTransferCodeForGuest(
            $data['name'],
            $data['guest_api_key'],
            $totalPrice
        );
        
        // Immediately unset the API key from memory
        unset($data['guest_api_key']);
        
        $usedTransferCodeService = true;
        
    } catch (RuntimeException $e) {
        error_log('Failed to create transfer code: ' . $e->getMessage());
        http_response_code(400);
        exit('Failed to create a transfer code. Please check your API key and try again.');
    }
    
} else {
    // Manual transferCode - validate it
    if (empty($data['transfer_code'])) {
        http_response_code(400);
        exit('Transfer code is required.');
    }
    
    $transferCode = $data['transfer_code'];
    
    try {
        $cb->validateTransferCode($transferCode, $totalPrice);
    } catch (RuntimeException $e) {
        error_log('Invalid transfer code: ' . $e->getMessage());
        http_response_code(400);
        exit('That transfer code could not be validated. Please double-check it and try again.');
    }
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
    $cb->deposit($transferCode);
} catch (RuntimeException $e) {
    error_log('Deposit to hotel account failed: ' . $e->getMessage());
    http_response_code(400);
    exit('Payment failed. Please contact us if you believe this is a mistake.');
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


<section class="booking-confirmation">
    <div class="confirmation-container1">
    <h1>Booking Confirmation</h1>
    <p>Thank you, <?php echo htmlspecialchars($data['name']); ?>.</p>
    <p>Your booking has been confirmed.</p>

    <?php if ($isReturningCustomer && $previousBookings >= 1): ?>
        <p>You are a returning customer! A loyalty discount of <?php echo $loyaltyDiscount; ?>% has been applied to your booking.</p>
    <?php endif; ?>

    <h3>Booking Details:</h3>
    <ul>
        <li>Room Type: <?php echo htmlspecialchars($roomType); ?></li>
        <li>Arrival Date: <?php echo htmlspecialchars($arrival->format('Y-m-d')); ?></li>
        <li>Departure Date: <?php echo htmlspecialchars($departure->format('Y-m-d')); ?></li>
        <li>Total Price: <?php echo number_format($totalPrice, 2); ?></li>
    </ul>

    <?php if (!empty($featureRows)): ?>
        <h4>Additional Features:</h4>
        <ul>
            <?php foreach ($featureRows as $feature): ?>
                <li><?php echo htmlspecialchars($feature['name']); ?> (<?php echo ($feature['price']); ?>)</li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    </div>

    <div class="confirmation-container2">
    <h3>Make sure you visit our bar <a href="/public/bolaget.php">Bolaget</a>.</h3>
    <p>E-Type will welcome you personally.</p>
    <img src="/public/images/E-Type-welcome.png" alt="E-Type Welcoming You" style="max-width:600px;">

    <h4>We hope you enjoy your stay!</h4>

    <h2><a href="/public/index.php">Book another night!</a></h2>
    </div>
</section>


<?php require __DIR__ . '/../includes/footer.php'; ?>