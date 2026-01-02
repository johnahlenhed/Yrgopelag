<?php

declare(strict_types=1);

return [
    'api_base_url' => 'https://www.yrgopelag.se/centralbank',
    'user' => $_ENV['CENTRAL_BANK_USER'],
    'api_key' => $_ENV['CENTRAL_BANK_API_KEY'],
];