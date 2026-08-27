<?php

declare(strict_types=1);

// Thrown by BookingService when a booking attempt must be stopped and (if a
// row was already reserved) rolled back, with an HTTP status and a message
// safe to show the guest.
final class BookingRejected extends RuntimeException
{
    public function __construct(string $message, private readonly int $httpStatus)
    {
        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}
