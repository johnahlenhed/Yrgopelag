<?php

declare(strict_types=1);

final class CentralBankClient
{
    private string $baseUrl;
    private string $user;
    private string $apiKey;

    public function validateTransferCode(string $transferCode, int $totalCost): void
    {
        $this->post('/transferCode', [
            'transfer_code' => $transferCode,
            'total_price' => $totalCost,
        ]);
    }

    public function __construct(array $config)
    {
        $this->baseUrl = $config['api_base_url'];
        $this->user = $config['user'];
        $this->apiKey = $config['api_key'];
    }

    private function post(string $endpoint, array $payload): array
    {
        $ch = curl_init($this->baseUrl . $endpoint);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_THROW_ON_ERROR),
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            throw new RuntimeException('cURL error: ' . curl_error($ch));
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        if ($status >= 400) {
            throw new RuntimeException($data['error'] ?? 'Centralbank error', $status);
        }

        return $data;
    }

    public function deposit(string $transferCode): void
    {
        $this->post('/deposit', [
            'user' => $this->user,
            'transfer_code' => $transferCode,
        ]);
    }

    public function sendReceipt(
        string $guestName,
        string $arrival,
        string $departure,
        array $features,
        int $stars
    ): void {
        $this->post('/receipt', [
            'user' => $this->user,
            'api_key' => $this->apiKey,
            'guest_name' => $guestName,
            'arrival_date' => $arrival,
            'departure_date' => $departure,
            'features_used' => $features,
            'star_rating' => $stars,
        ]);
    }
    
}
