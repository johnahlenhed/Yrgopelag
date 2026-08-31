<?php

declare(strict_types=1);

// Orchestrates a booking attempt: pricing, reserving the date, and paying
// through Centralbank. Keeps that logic (and its failure/rollback rules) out
// of public/book.php, which only translates HTTP <-> this service.
final class BookingService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly CentralBankClient $cb
    ) {
    }

    /** @param array<int, array{price:int}> $featureRows */
    public function calculatePrice(string $roomType, array $featureRows, string $guestName): array
    {
        $isReturningCustomer = bookingRepository::isReturningCustomer($this->pdo, $guestName);
        $previousBookings = bookingRepository::getBookingCountByGuest($this->pdo, $guestName);
        $loyaltyDiscount = (int)getSetting($this->pdo, 'loyalty_discount');

        $featurePriceTotal = array_sum(array_column($featureRows, 'price'));
        $roomPrice = roomRepository::getRoomPriceByType($this->pdo, $roomType);
        $subtotal = ($roomPrice ?? 0) + $featurePriceTotal;

        $discountAmount = 0;
        if ($isReturningCustomer && $previousBookings >= 1) {
            $discountAmount = (int)ceil($subtotal * ($loyaltyDiscount / 100));
        }

        return [
            'roomPrice' => $roomPrice,
            'featurePriceTotal' => $featurePriceTotal,
            'subtotal' => $subtotal,
            'discountAmount' => $discountAmount,
            'totalPrice' => $subtotal - $discountAmount,
            'isReturningCustomer' => $isReturningCustomer,
            'previousBookings' => $previousBookings,
            'loyaltyDiscount' => $loyaltyDiscount,
        ];
    }

    /**
     * Reserves the room/date before any money moves. Nothing is charged yet,
     * so on failure there is nothing to roll back with Centralbank -- only
     * the local transaction.
     *
     * @param int[] $featureIds
     * @throws BookingRejected if the date was taken by a concurrent request
     */
    public function reserve(
        string $guestName,
        string $roomType,
        DateTime $arrival,
        DateTime $departure,
        int $totalPrice,
        array $featureIds
    ): int {
        try {
            $this->pdo->beginTransaction();

            $bookingId = bookingRepository::create($this->pdo, $guestName, $roomType, $arrival, $departure, $totalPrice);
            featureRepository::attachToBooking($this->pdo, $bookingId, $featureIds);

            $this->pdo->commit();

            return $bookingId;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log('Failed to create booking: ' . $e->getMessage());
            throw new BookingRejected('That date just became unavailable. Please choose another date.', 409);
        }
    }

    /**
     * Withdraws the guest's funds via their own Centralbank API key.
     *
     * @throws BookingRejected on missing input or a Centralbank failure; the
     *         reserved booking is deleted since nothing was charged.
     */
    public function payViaService(int $bookingId, string $guestName, ?string $guestApiKey, int $totalPrice): string
    {
        if (empty($guestApiKey)) {
            bookingRepository::delete($this->pdo, $bookingId);
            throw new BookingRejected('API key is required for TransferCode Service.', 400);
        }

        try {
            $transferCode = $this->cb->createTransferCodeForGuest($guestName, $guestApiKey, $totalPrice);
        } catch (RuntimeException $e) {
            error_log('Failed to create transfer code: ' . $e->getMessage());
            bookingRepository::delete($this->pdo, $bookingId);
            throw new BookingRejected('Failed to create a transfer code. Please check your API key and try again.', 400);
        }

        // Money has now left the guest's account. Persist the code
        // immediately so it's never held only in a PHP variable.
        bookingRepository::setTransferCode($this->pdo, $bookingId, $transferCode);

        return $transferCode;
    }

    /**
     * Validates a transfer code the guest already created themselves.
     *
     * @throws BookingRejected on missing input or a Centralbank failure; the
     *         reserved booking is deleted since nothing was charged.
     */
    public function payViaManualCode(int $bookingId, ?string $transferCode, int $totalPrice): string
    {
        if (empty($transferCode)) {
            bookingRepository::delete($this->pdo, $bookingId);
            throw new BookingRejected('Transfer code is required.', 400);
        }

        try {
            $this->cb->validateTransferCode($transferCode, $totalPrice);
        } catch (RuntimeException $e) {
            error_log('Invalid transfer code: ' . $e->getMessage());
            bookingRepository::delete($this->pdo, $bookingId);
            throw new BookingRejected('That transfer code could not be validated. Please double-check it and try again.', 400);
        }

        bookingRepository::setTransferCode($this->pdo, $bookingId, $transferCode);

        return $transferCode;
    }

    /**
     * Claims the (already-moved) funds into the hotel's account.
     *
     * @throws BookingPaymentPending if the deposit call fails. The booking is
     *         kept, not deleted -- Centralbank has no refund/reversal
     *         endpoint, so at this point money has already moved and this
     *         needs manual follow-up rather than a silent rollback.
     */
    public function deposit(int $bookingId, string $transferCode): void
    {
        try {
            $this->cb->deposit($transferCode);
        } catch (RuntimeException $e) {
            error_log("Deposit to hotel account failed for booking #{$bookingId}: " . $e->getMessage());
            bookingRepository::markPaymentFailed($this->pdo, $bookingId);
            throw new BookingPaymentPending($bookingId);
        }

        bookingRepository::markConfirmed($this->pdo, $bookingId);
    }

    /** @param array<int, array{activity:string, tier:string}> $featureRows */
    public function sendReceiptBestEffort(
        string $guestName,
        DateTime $arrival,
        DateTime $departure,
        array $featureRows,
        int $starRating
    ): void {
        $featuresUsed = array_map(
            fn($f) => ['activity' => $f['activity'], 'tier' => $f['tier']],
            $featureRows
        );

        try {
            $this->cb->sendReceipt(
                $guestName,
                $arrival->format('Y-m-d'),
                $departure->format('Y-m-d'),
                $featuresUsed,
                $starRating
            );
        } catch (RuntimeException $e) {
            error_log('Failed to send receipt to Central Bank: ' . $e->getMessage());
        }
    }
}
