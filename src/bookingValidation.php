<?php

declare(strict_types=1);

final class bookingValidation
{
    public static function validateBookingData(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Name is required.';
        }

        if (empty($data['transfer_code'])) {
            $errors[] = 'Transfer code is required.';
        }

        if (
            empty($data['economy_checkin']) &&
            empty($data['standard_checkin']) &&
            empty($data['luxury_checkin'])
        ) {
            $errors[] = 'At least one room type must be selected.';
        }
        
        return $errors;
    }
}