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

    private const REDACTED_KEYS = ['api_key', 'transferCode'];

    private static function redact(array $data): array
    {
        foreach (self::REDACTED_KEYS as $key) {
            if (isset($data[$key])) {
                $data[$key] = '***REDACTED***';
            }
        }

        return $data;
    }

    private function post(string $endpoint, array $payload): array
    {
        $url = $this->baseUrl . $endpoint;
        $maxRetries = 3;
        $retryDelay = 1;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            error_log("POST to: " . $url . " (attempt {$attempt}/{$maxRetries})");
            error_log("Payload: " . json_encode(self::redact($payload), JSON_PRETTY_PRINT));

            $ch = curl_init($url);

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,  // Force HTTP/1.1
                CURLOPT_TIMEOUT => 60,
                CURLOPT_CONNECTTIMEOUT => 20,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_FOLLOWLOCATION => true,
            ]);

            $response = curl_exec($ch);

            if ($response === false) {
                $error = curl_error($ch);
                curl_close($ch);
                
                // If this was the last attempt, throw the error
                if ($attempt === $maxRetries) {
                    throw new RuntimeException('cURL error after ' . $maxRetries . ' attempts: ' . $error);
                }
                
                // Otherwise, log and retry
                error_log("cURL error on attempt {$attempt}: {$error}. Retrying in {$retryDelay}s...");
                sleep($retryDelay);
                continue;
            }

            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($response, true);

            error_log("Response status: " . $status);
            error_log("Response body: " . json_encode(
                is_array($data) ? self::redact($data) : ['raw' => $response],
                JSON_PRETTY_PRINT
            ));

            if ($status >= 400) {
                throw new RuntimeException($data['error'] ?? 'Centralbank error', $status);
            }

            return $data ?? [];
        }
        
        throw new RuntimeException('Failed to connect to Centralbank after ' . $maxRetries . ' attempts');
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

    public function createTransferCodeForGuest(string $guestUsername, string $guestApiKey, int $amount): string
    {
        $response = $this->post('/withdraw', [
            'user' => $guestUsername,
            'api_key' => $guestApiKey,
            'amount' => $amount,
        ]);

        if (!isset($response['transferCode'])) {
            throw new RuntimeException('Invalid response from Centralbank: missing transferCode');
        }

        return $response['transferCode'];
    }
}