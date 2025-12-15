<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/header.php'; ?>

<main>
    <p>Welcome to Hotel Borta bra, hemma bäst!</p>

    <section>
        <form>
            <label type="date" name="checkin">
                Check-in Date:
            </label>

            <label type="date" name="checkout">
                Check-out Date:
            </label>
        </form>
    </section>

    <section>
        <form method="POST" action="/book">
            <fieldset>
                <legend>Contact details</legend>
                <label>
                    Full name:
                    <input type="text" name="name" required>
                </label>

                <label>
                    Email:
                    <input type="email" name="email" required>
                </label>
            </fieldset>

            <fieldset>
                <legend>Features</legend>
                <label>
                    <input type="checkbox" name="">
                    <input type="checkbox" name="">
                    <input type="checkbox" name="">
                    <input type="checkbox" name="">
                </label>
            </fieldset>

            <button type="submit">Book Now</button>
        </form>
    </section>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>