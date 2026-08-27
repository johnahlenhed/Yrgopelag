<?php

declare(strict_types=1);

// Thrown by BookingService::deposit() when money has already moved (or was
// validated) but the Centralbank deposit call itself failed. Centralbank has
// no refund/reversal endpoint, so the booking is kept rather than rolled
// back -- this carries its id so the controller can show the guest a
// reference number instead of a false success or a false failure.
final class BookingPaymentPending extends RuntimeException
{
    public function __construct(private readonly int $bookingId)
    {
        parent::__construct('Payment could not be confirmed; booking kept for manual follow-up.');
    }

    public function bookingId(): int
    {
        return $this->bookingId;
    }
}
