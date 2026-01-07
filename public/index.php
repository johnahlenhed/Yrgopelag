<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/featureRepository.php';
require_once __DIR__ . '/../src/roomRepository.php';
require_once __DIR__ . '/../src/bookingRepository.php';
require_once __DIR__ . '/../config/helpers.php';

$activeFeatures = featureRepository::getActiveFeaturesByCategory($pdo);
$roomPrices = roomRepository::getRoomPrices($pdo);

$waterFeatures = $activeFeatures['water'] ?? [];
$wheelsFeatures = $activeFeatures['wheels'] ?? [];
$gamesFeatures = $activeFeatures['games'] ?? [];
$hotelSpecificFeatures = $activeFeatures['hotel-specific'] ?? [];

$blockedDates = bookingRepository::getBookedDatesByRoom($pdo);

$loyaltyDiscount = (int)getSetting($pdo, 'loyalty_discount');

require __DIR__ . '/../includes/header.php'; ?>

<main>

    <div class="welcome-container">
        <h1>Welcome to Borta bra, hemma bäst!</h1>
        <div class="hero">
            <img src="/public/images/hotel.png" alt="full view of hotel">
            <div class="welcome-text">
                <h2>A place to feel at home</h2>
                <p><b>Welcome to the New Sweden Island Resort. We have gone to great lengths to bring the modest charm of a Falu-red cottage, just as the market demand is hinting at.</b></p>
                <p>We hope you enjoy your stay with us, though we trust you will do so with appropriate moderation. Our four star hotel offers a serene environment, and we find that guests who manage their expectations—and their enthusiasm—tend to fit in best here.</p>
                <p>We are ready to receive you. Please try to arrive on time; the tropical sun, unlike our patience, is quite consistent.</p>
                <p>What are you waiting for? Book your room!</p>
            </div>
        </div>
    </div>

    <div class="booking-container">
        <section>
            <form method="POST" action="/public/book.php" class="booking-form">

                <fieldset class="economy-room">
                    <legend>Economy</legend>

                    <!-- Hidden real date input -->
                    <input
                        type="date"
                        name="economy_checkin"
                        id="economy_checkin"
                        min="2026-01-01"
                        max="2026-01-31"
                        data-room-type="economy"
                        data-price="<?php echo $roomPrices['economy']; ?>"
                        hidden>

                    <!-- Visual grid -->
                    <div class="date-grid" data-target="economy_checkin">
                        <?php for ($day = 1; $day <= 31; $day++): ?>
                            <button
                                type="button"
                                class="date-cell"
                                data-date="2026-01-<?php echo str_pad((string)$day, 2, '0', STR_PAD_LEFT); ?>">
                                <?php echo $day; ?>
                            </button>
                        <?php endfor; ?>
                    </div>
                </fieldset>

                <fieldset class="standard-room">
                    <legend>Standard</legend>

                    <!-- Hidden real date input -->
                    <input
                        type="date"
                        name="standard_checkin"
                        id="standard_checkin"
                        min="2026-01-01"
                        max="2026-01-31"
                        data-room-type="standard"
                        data-price="<?php echo $roomPrices['standard']; ?>"
                        hidden>

                    <!-- Visual grid -->
                    <div class="date-grid" data-target="standard_checkin">
                        <?php for ($day = 1; $day <= 31; $day++): ?>
                            <button
                                type="button"
                                class="date-cell"
                                data-date="2026-01-<?php echo str_pad((string)$day, 2, '0', STR_PAD_LEFT); ?>">
                                <?php echo $day; ?>
                            </button>
                        <?php endfor; ?>
                    </div>
                </fieldset>

                <fieldset class="luxury-room">
                    <legend>Luxury</legend>

                    <!-- Hidden real date input -->
                    <input
                        type="date"
                        name="luxury_checkin"
                        id="luxury_checkin"
                        min="2026-01-01"
                        max="2026-01-31"
                        data-room-type="luxury"
                        data-price="<?php echo $roomPrices['luxury']; ?>"
                        hidden>

                    <!-- Visual grid -->
                    <div class="date-grid" data-target="luxury_checkin">
                        <?php for ($day = 1; $day <= 31; $day++): ?>
                            <button
                                type="button"
                                class="date-cell"
                                data-date="2026-01-<?php echo str_pad((string)$day, 2, '0', STR_PAD_LEFT); ?>">
                                <?php echo $day; ?>
                            </button>
                        <?php endfor; ?>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Features</legend>
                    <h5>Water:</h5>
                    <?php foreach ($waterFeatures as $feature): ?>
                        <label>
                            <input type="checkbox" name="features[]" value="<?php echo htmlspecialchars($feature['name']); ?>" class="feature-checkbox" data-price="<?php echo $feature['price']; ?>">
                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $feature['name']))); ?>
                            (<?php echo htmlspecialchars(ucfirst($feature['tier'])); ?>)
                            ($<?php echo ($feature['price']); ?>)
                        </label>
                    <?php endforeach; ?>
                    <h5>Games:</h5>
                    <?php foreach ($gamesFeatures as $feature): ?>
                        <label>
                            <input type="checkbox" name="features[]" value="<?php echo htmlspecialchars($feature['name']); ?>" class="feature-checkbox" data-price="<?php echo $feature['price']; ?>">
                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $feature['name']))); ?>
                            (<?php echo htmlspecialchars(ucfirst($feature['tier'])); ?>)
                            ($<?php echo ($feature['price']); ?>)
                        </label>
                    <?php endforeach; ?>
                    <h5>Wheels:</h5>
                    <?php foreach ($wheelsFeatures as $feature): ?>
                        <label>
                            <input type="checkbox" name="features[]" value="<?php echo htmlspecialchars($feature['name']); ?>" class="feature-checkbox" data-price="<?php echo $feature['price']; ?>">
                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $feature['name']))); ?>
                            (<?php echo htmlspecialchars(ucfirst($feature['tier'])); ?>)
                            ($<?php echo ($feature['price']); ?>)
                        </label>
                    <?php endforeach; ?>
                    <h5>Hotel-Specific:</h5>
                    <?php foreach ($hotelSpecificFeatures as $feature): ?>
                        <label>
                            <input type="checkbox" name="features[]" value="<?php echo htmlspecialchars($feature['name']); ?>" class="feature-checkbox" data-price="<?php echo $feature['price']; ?>">
                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $feature['name']))); ?>
                            (<?php echo htmlspecialchars(ucfirst($feature['tier'])); ?>)
                            ($<?php echo ($feature['price']); ?>)
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <fieldset>
                    <legend>Contact details</legend>
                    <label>
                        Your name (Centralbank username)
                        <input type="text" name="name" required>
                    </label>

                    <div class="payment-method-selector">
                        <h4>Payment Method</h4>
                        <label>
                            <input type="radio" name="payment_method" value="manual" checked>
                            <strong>I have a transfer code</strong>
                            <span class="option-desc">Already created at Centralbank</span>
                        </label>

                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="service">
                            <strong>Use Centralbank Service</strong>
                            <span class="option-desc">We'll create it for you (requires your API key)</span>
                        </label>
                    </div>

                    <div id="manual-payment" class="payment-fields">
                        <label>
                            Transfer code
                            <input type="text" name="transfer_code" id="transfer_code" placeholder="Enter your transferCode">
                        </label>
                    </div>

                    <div id="service-payment" class="payment-fields" style="display: none;">
                        <div class="service-notice">
                            <p>⚠️ <strong>Security Notice:</strong> Your API is only used to create a transfer code for this booking. It is never stored.</p>
                        </div>
                        <label>
                            Your centralbank API Key
                            <input type="password" name="guest_api_key" id="guest_api_key" placeholder="Enter your Centralbank API Key">
                        </label>
                        <p class="helper-text">Amount needed: <strong>$<span id="total-amount-display">0</span></strong></p>
                    </div>
                </fieldset>

                <button type="submit">Book Now</button>
            </form>
        </section>

        <div class="price-calculator">
            <h2>Your Booking</h2>
            <div class="price-breakdown">
                <div class="price-line">
                    <span>Room:</span>
                    <span id="room-price-display">Select a room</span>
                </div>
                <div class="price-line features-section" style="display: none;">
                    <span>Features:</span>
                    <span id="features-price-display">$0</span>
                </div>
                <div class="price-line total-line">
                    <strong>Total:</strong>
                    <strong id="total-price-display">$0</strong>
                </div>
            </div>
            <div class="discount-info">
                <h3>Are you a returning customer? Then you'll get a <?php echo $loyaltyDiscount; ?>% discount!</h3>
            </div>
        </div>

        <section class="room-info-container">
            <article>
                <img src="/public/images/economy-room.png" alt="Economy Room">
                <div class="price-tag">
                    <h3>Economy Room</h3>
                    <p>Price: $<?php echo $roomPrices['economy']; ?> per night</p>
                </div>
            </article>
            <article>
                <img src="/public/images/standard-room.png" alt="Standard Room">
                <div class="price-tag">
                    <h3>Standard Room</h3>
                    <p>Price: $<?php echo $roomPrices['standard']; ?> per night</p>
                </div>
            </article>
            <article>
                <img src="/public/images/luxury-room.png" alt="Luxury Room">
                <div class="price-tag">
                    <h3>Luxury Room</h3>
                    <p>Price: $<?php echo $roomPrices['luxury']; ?> per night</p>
                </div>
            </article>
        </section>

    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>

<script>
    const blockedDates = <?php echo json_encode($blockedDates, JSON_THROW_ON_ERROR); ?>;

    let selectedRoomPrice = 0;
    let selectedRoomType = null;

    document.querySelectorAll('.date-grid').forEach(grid => {
        const targetInput = document.getElementById(grid.dataset.target);
        const roomType = grid.dataset.target.replace('_checkin', '');
        const blocked = blockedDates[roomType] ?? [];

        grid.querySelectorAll('.date-cell').forEach(btn => {
            const date = btn.dataset.date;

            if (blocked.includes(date)) {
                btn.disabled = true;
                btn.classList.add('blocked');
            }
        });

        grid.addEventListener('click', e => {
            if (
                !e.target.classList.contains('date-cell') ||
                e.target.classList.contains('blocked')
            ) return;

            // Deselect all days
            document.querySelectorAll('.date-grid .date-cell').forEach(btn => {
                btn.classList.remove('selected');
            });

            document.querySelectorAll('input[type="date"]').forEach(input => {
                if (input !== targetInput) {
                    input.value = '';
                }
            });

            // Select clicked day
            e.target.classList.add('selected');

            // Set hidden input value
            targetInput.value = e.target.dataset.date;

            // Update price calculator
            selectedRoomType = targetInput.dataset.roomType;
            selectedRoomPrice = parseInt(targetInput.dataset.price);
            updatePriceDisplay();
        });
    });

    document.querySelectorAll('.feature-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updatePriceDisplay);
    });

    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const manualPayment = document.getElementById('manual-payment');
            const servicePayment = document.getElementById('service-payment');
            const transferCodeInput = document.getElementById('transfer_code');
            const apiKeyInput = document.getElementById('guest_api_key');

            if (this.value === 'manual') {
                manualPayment.style.display = 'block';
                servicePayment.style.display = 'none';
                transferCodeInput.setAttribute('required', '');
                apiKeyInput.removeAttribute('required');
            } else {
                manualPayment.style.display = 'none';
                servicePayment.style.display = 'block';
                transferCodeInput.removeAttribute('required');
                apiKeyInput.setAttribute('required', '');
            }
        });
    });

    function updatePriceDisplay() {
        const roomPriceDisplay = document.getElementById('room-price-display');
        const featuresPriceDisplay = document.getElementById('features-price-display');
        const totalPriceDisplay = document.getElementById('total-price-display');
        const featuresSection = document.querySelector('.features-section');

        let featuresTotal = 0;
        document.querySelectorAll('.feature-checkbox:checked').forEach(checkbox => {
            featuresTotal += parseInt(checkbox.dataset.price);
        });

        if (selectedRoomPrice > 0) {
            roomPriceDisplay.textContent = `$${selectedRoomPrice} (${selectedRoomType})`;
        } else {
            roomPriceDisplay.textContent = 'Select a room';
        }

        if (featuresTotal > 0) {
            featuresSection.style.display = 'flex';
            featuresPriceDisplay.textContent = `$${featuresTotal}`;
        } else {
            featuresSection.style.display = 'none';
            featuresPriceDisplay.textContent = '$0';
        }

        const total = selectedRoomPrice + featuresTotal;
        totalPriceDisplay.textContent = `$${total}`;

        document.getElementById('total-amount-display').textContent = total;
    }

    updatePriceDisplay();
</script>