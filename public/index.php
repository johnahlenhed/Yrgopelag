<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/featureRepository.php';
require_once __DIR__ . '/../src/roomRepository.php';

$activeFeatures = featureRepository::getAllActiveFeatures($pdo);

require __DIR__ . '/../includes/header.php'; ?>

<main>
    <p>Welcome to Hotel Borta bra, hemma bäst!</p>

    <section>
        <form method="POST" action="/public/book.php" class="booking-form">

            <fieldset>
                <label for="economy_checkin">Economy</label>
                <input type="date" id="economy_checkin" name="economy_checkin" min="2026-01-01" max="2026-01-31">
            </fieldset>

            <fieldset class="standard-room">
                <label for="standard_checkin">Standard</label>
                <input type="date" id="standard_checkin" name="standard_checkin" min="2026-01-01" max="2026-01-31">
            </fieldset>

            <fieldset class="luxury-room">
                <label for="luxury_checkin">Luxury</label>
                <input type="date" id="luxury_checkin" name="luxury_checkin" min="2026-01-01" max="2026-01-31">
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
                <label>

                </label>
                <h5>Games:</h5>
                <label>
                    <input type="checkbox" name="features[]" value="yahtzee">Yahtzee (Economy)</input>
                    <input type="checkbox" name="features[]" value="ping_pong_table">Ping Pong Table (Basic)</input>
                    <input type="checkbox" name="features[]" value="ps5">PS5 (Premium)</input>
                    <input type="checkbox" name="features[]" value="casino">Casino (Superior)</input>
                </label>
                <h5>Wheels:</h5>
                <label>
                    <input type="checkbox" name="features[]" value="unicycle">Unicycle (Economy)</input>
                    <input type="checkbox" name="features[]" value="bicycle">Bicycle (Basic)</input>
                    <input type="checkbox" name="features[]" value="trike">Trike (Premium)</input>
                    <input type="checkbox" name="features[]" value="four_wheeled_motorized_beast">Four-Wheeled Motorized Beast (Superior)</input>
                </label>
                <h5>Hotel-Specific:</h5>
                <label>
                    <input type="checkbox" name="features[]" value="svenskt_kaffe_on_arrival">Svenskt kaffe on arrival (Economy)</input>
                    <input type="checkbox" name="features[]" value="smörgåsbord_lunch">Smörgåsbord lunch (Basic)</input>
                    <input type="checkbox" name="features[]" value="kräftskiva">Kräftskiva (Premium)</input>
                    <input type="checkbox" name="features[]" value="Jan-Emanuel_sköter_din_deklaration">Jan-Emanuel sköter din deklaration (Superior)</input>
                </label>
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
</main>

<?php require __DIR__ . '/../includes/footer.php'; ?>