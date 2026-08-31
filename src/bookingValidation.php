<?php

declare(strict_types=1);

final class bookingValidation
{
    private const MIN_CHECKIN_DATE = '2026-01-01';
    private const MAX_CHECKIN_DATE = '2026-01-31';

    public static function validateBookingData(array $data): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Name is required.';
        }

        if (
            empty($data['economy_checkin']) &&
            empty($data['standard_checkin']) &&
            empty($data['luxury_checkin'])
        ) {
            $errors[] = 'At least one room type must be selected.';
        }

        foreach (['economy_checkin', 'standard_checkin', 'luxury_checkin'] as $field) {
            if (!empty($data[$field]) && !self::isValidCheckinDate($data[$field])) {
                $errors[] = 'The selected date is invalid.';
                break;
            }
        }

        return $errors;
    }

    public static function isValidCheckinDate(string $date): bool
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);

        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            return false;
        }

        return $date >= self::MIN_CHECKIN_DATE && $date <= self::MAX_CHECKIN_DATE;
    }
}