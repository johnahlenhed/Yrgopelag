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

    public static function validateFeatures(array $featureRows, string $roomType): void
    {
        $roomFeatureMap = [
            'economy' => ['pool', 'yahtzee', 'unicycle', 'custom_1'],
            'standard' => ['scuba_diving', 'ping_pong_table', 'bicycle', 'custom_2'],
            'luxury' => ['olympic_pool', 'ps5', 'motorcycle', 'custom_3'],
            'superior' => ['waterpark', 'casino', 'sports_car', 'custom_4'],
        ];

        $allowedFeatures = $roomFeatureMap[$roomType] ?? [];

        foreach ($featureRows as $feature) {
            if (!in_array($feature['name'], $allowedFeatures, true)) {
                throw new InvalidArgumentException(
                    sprintf('Feature "%s" is not allowed for room type "%s".', $feature['name'], $roomType)
                );
            }
        }
    }
}