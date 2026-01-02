<?php

declare(strict_types=1);

final class CentralBankClient
{
    private string $baseUrl;
    private string $user;
    private string $apiKey;

    public function __construct(array $config)
    {
        $this->baseUrl = rtrim($config['api_base_url'], '/');
        $this->user = $config['user'];
        $this->apiKey = $config['api_key'];
    }

    private function post(string $endpoint, array $payload): array
    {
        $url = $this->baseUrl . $endpoint;
        error_log("POST to: " . $url);
        error_log("Payload: " . json_encode($payload, JSON_PRETTY_PRINT));

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('cURL error: ' . $error);
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        error_log("Response status: " . $status);
        error_log("Response body: " . $response);

        $data = json_decode($response, true);

        if ($status >= 400) {
            throw new RuntimeException($data['error'] ?? 'Centralbank error', $status);
        }

        return $data ?? [];
    }

    public function validateTransferCode(string $transferCode, int $totalCost): void
    {
        $this->post('/transferCode', [
            'transferCode' => $transferCode,
            'totalCost' => $totalCost,
        ]);
    }

    public function deposit(string $transferCode): void
    {
        $this->post('/deposit', [
            'user' => $this->user,
            'transferCode' => $transferCode,
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

    public function syncIsland(array $payload): array
    {
        return $this->post('/islands', $payload);
    }

    public function getIslandFeatures(): array
    {
        return $this->post('/islandFeatures', [
            'user' => $this->user,
            'api_key' => $this->apiKey,
        ]);
    }
}