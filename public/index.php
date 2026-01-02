<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/featureRepository.php';
require_once __DIR__ . '/../src/roomRepository.php';
require_once __DIR__ . '/../src/bookingRepository.php';

$activeFeatures = featureRepository::getActiveFeaturesByCategory($pdo);

$waterFeatures = $activeFeatures['water'] ?? [];
$wheelsFeatures = $activeFeatures['wheels'] ?? [];
$gamesFeatures = $activeFeatures['games'] ?? [];
$hotelSpecificFeatures = $activeFeatures['hotel-specific'] ?? [];

$blockedDates = bookingRepository::getBookedDatesByRoom($pdo);

require __DIR__ . '/../includes/header.php'; ?>

<main>

    <div class="welcome-container">
        <h1>Welcome to Borta bra, hemma bäst!</h1>
        <img src="/public/images/hotel.png" alt="full view of hotel">
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
                    <legend>Contact details</legend>
                    <label>
                        Your name (guest_id)
                        <input type="text" name="name" required>
                    </label>

                    <label>
                        Transfer code
                        <input type="text" name="transfer_code" required>
                    </label>
                </fieldset>

                <fieldset>
                    <legend>Features</legend>
                    <h5>Water:</h5>
                    <?php foreach ($waterFeatures as $feature): ?>
                        <label>
                            <input type="checkbox" name="features[]" value="<?php echo htmlspecialchars($feature['name']); ?>">
                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $feature['name']))); ?>
                            (<?php echo htmlspecialchars(ucfirst($feature['tier'])); ?>)
                            ($<?php echo ($feature['price']); ?>)
                        </label>
                    <?php endforeach; ?>
                    <h5>Games:</h5>
                    <?php foreach ($gamesFeatures as $feature): ?>
                        <label>
                            <input type="checkbox" name="features[]" value="<?php echo htmlspecialchars($feature['name']); ?>">
                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $feature['name']))); ?>
                            (<?php echo htmlspecialchars(ucfirst($feature['tier'])); ?>)
                            ($<?php echo ($feature['price']); ?>)
                        </label>
                    <?php endforeach; ?>
                    <h5>Wheels:</h5>
                    <?php foreach ($wheelsFeatures as $feature): ?>
                        <label>
                            <input type="checkbox" name="features[]" value="<?php echo htmlspecialchars($feature['name']); ?>">
                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $feature['name']))); ?>
                            (<?php echo htmlspecialchars(ucfirst($feature['tier'])); ?>)
                            ($<?php echo ($feature['price']); ?>)
                        </label>
                    <?php endforeach; ?>
                    <h5>Hotel-Specific:</h5>
                    <?php foreach ($hotelSpecificFeatures as $feature): ?>
                        <label>
                            <input type="checkbox" name="features[]" value="<?php echo htmlspecialchars($feature['name']); ?>">
                            <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $feature['name']))); ?>
                            (<?php echo htmlspecialchars(ucfirst($feature['tier'])); ?>)
                            ($<?php echo ($feature['price']); ?>)
                        </label>
                    <?php endforeach; ?>
                </fieldset>

                <button type="submit">Book Now</button>
            </form>
        </section>

        <section class="room-info-container">
            <article>
                <img src="/public/images/economy-room.png" alt="Economy Room">
            </article>
            <article>
                <img src="/public/images/standard-room.png" alt="Standard Room">
            </article>
            <article>
                <img src="/public/images/luxury-room.png" alt="Luxury Room">
            </article>
        </section>

    </div>
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>

<script>

    const blockedDates = <?php echo json_encode($blockedDates, JSON_THROW_ON_ERROR); ?>;

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

                // Remove previous selection
                grid.querySelectorAll('.date-cell').forEach(btn =>
                    btn.classList.remove('selected')
                );

                // Select clicked day
                e.target.classList.add('selected');

                // Set hidden input value
                targetInput.value = e.target.dataset.date;
            });
        });
</script>