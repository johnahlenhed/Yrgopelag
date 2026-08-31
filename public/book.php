<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_once __DIR__ . '/../src/bookingValidation.php';
require_once __DIR__ . '/../src/bookingRepository.php';
require_once __DIR__ . '/../src/featureRepository.php';
require_once __DIR__ . '/../src/roomRepository.php';
require_once __DIR__ . '/../src/centralBankClient.php';
require_once __DIR__ . '/../src/BookingRejected.php';
require_once __DIR__ . '/../src/BookingPaymentPending.php';
require_once __DIR__ . '/../src/BookingService.php';

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

// Check availability (a real race is still closed by the unique index BookingService::reserve() relies on)
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

$config = require __DIR__ . '/../config/centralbank.php';
$cb = new CentralBankClient($config);
$bookingService = new BookingService($pdo, $cb);

$pricing = $bookingService->calculatePrice($roomType, $featureRows, $data['name']);
$totalPrice = $pricing['totalPrice'];

try {
    $featureIds = array_column($featureRows, 'id');
    $bookingId = $bookingService->reserve($data['name'], $roomType, $arrival, $departure, $totalPrice, $featureIds);

    if ($data['payment_method'] === 'service') {
        // Throttle Centralbank credential attempts from this session.
        $attempts = array_filter($_SESSION['cb_service_attempts'] ?? [], fn($t) => $t > time() - 60);
        if (count($attempts) >= 10) {
            bookingRepository::delete($pdo, $bookingId);
            http_response_code(429);
            exit('Too many attempts. Please wait a minute and try again.');
        }
        $attempts[] = time();
        $_SESSION['cb_service_attempts'] = $attempts;

        $transferCode = $bookingService->payViaService($bookingId, $data['name'], $data['guest_api_key'], $totalPrice);
        unset($data['guest_api_key']); // Immediately drop it from memory now that it's been used.
    } else {
        $transferCode = $bookingService->payViaManualCode($bookingId, $data['transfer_code'], $totalPrice);
    }

    $bookingService->deposit($bookingId, $transferCode);
    $bookingService->sendReceiptBestEffort($data['name'], $arrival, $departure, $featureRows, (int)getSetting($pdo, 'star_rating'));
} catch (BookingPaymentPending $e) {
    http_response_code(202);
    require __DIR__ . '/../includes/header.php'; ?>

    <section class="booking-confirmation">
        <h1>We've got your booking, but hit a snag</h1>
        <p>Thank you, <?php echo htmlspecialchars($data['name']); ?>. Your date is reserved (reference #<?php echo $e->bookingId(); ?>),
            but we couldn't confirm the deposit with Centralbank just now.</p>
        <p>We'll follow up to resolve this -- please don't attempt to pay again for the same booking.</p>
    </section>

    <?php require __DIR__ . '/../includes/footer.php';
    exit;
} catch (BookingRejected $e) {
    http_response_code($e->httpStatus());
    exit(htmlspecialchars($e->getMessage()));
}

?>

<?php require __DIR__ . '/../includes/header.php'; ?>


<section class="booking-confirmation">
    <div class="confirmation-container1">
    <h1>Booking Confirmation</h1>
    <p>Thank you, <?php echo htmlspecialchars($data['name']); ?>.</p>
    <p>Your booking has been confirmed.</p>

    <?php if ($pricing['isReturningCustomer'] && $pricing['previousBookings'] >= 1): ?>
        <p>You are a returning customer! A loyalty discount of <?php echo $pricing['loyaltyDiscount']; ?>% has been applied to your booking.</p>
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
