<?php

namespace App\Services\Kampus;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class KampusClient
{
    protected string $baseUrl;
    protected string $staticToken;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retrySleep;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('kampus.base_url', 'https://bridge.uinssc.ac.id'), '/');
        $this->staticToken = (string) config('kampus.static_token', 'ptipd-access-token');
        $this->timeout = (int) config('kampus.timeout', 15);
        $this->retryTimes = (int) config('kampus.retry_times', 2);
        $this->retrySleep = (int) config('kampus.retry_sleep', 100);
    }

    /**
     * Membuat request HTTP client dasar dengan timeout & headers standar.
     */
    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->retry($this->retryTimes, $this->retrySleep, throw: false)
            ->acceptJson();
    }

    /**
     * Request GET dengan Static System Bearer Token (untuk Master Data Fakultas & Prodi).
     */
    public function getWithStaticToken(string $endpoint, array $query = []): array
    {
        try {
            $response = $this->client()
                ->withToken($this->staticToken)
                ->get($endpoint, $query);

            return $this->handleResponse($response, $endpoint);
        } catch (\Throwable $e) {
            Log::error("KampusClient GET {$endpoint} failed: " . $e->getMessage());
            throw new RuntimeException("Gagal menghubungi server kampus ({$endpoint}): " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Request POST untuk Login Portal Pengguna (mengirim username & password).
     */
    public function postPortalLogin(string $endpoint, array $credentials): array
    {
        try {
            // Gunakan asForm (application/x-www-form-urlencoded) sesuai spesifikasi bridge API
            $response = $this->client()
                ->asForm()
                ->post($endpoint, $credentials);

            return $this->handleResponse($response, $endpoint);
        } catch (\Throwable $e) {
            // Keamanan: jangan pernah mencatat kredensial password ke log!
            Log::error("KampusClient POST {$endpoint} (login) failed: " . $e->getMessage());
            throw new RuntimeException("Gagal melakukan login ke server kampus: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Request GET dengan Bearer Token Pengguna (untuk API Profil).
     */
    public function getWithUserToken(string $endpoint, string $userToken, array $query = []): array
    {
        try {
            $response = $this->client()
                ->withToken($userToken)
                ->get($endpoint, $query);

            return $this->handleResponse($response, $endpoint);
        } catch (\Throwable $e) {
            Log::error("KampusClient GET {$endpoint} (user) failed: " . $e->getMessage());
            throw new RuntimeException("Gagal mengambil profil dari server kampus: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Request POST dengan Bearer Token Pengguna (untuk API Logout bridge).
     */
    public function postWithUserToken(string $endpoint, string $userToken, array $data = []): array
    {
        try {
            $response = $this->client()
                ->withToken($userToken)
                ->post($endpoint, $data);

            return $this->handleResponse($response, $endpoint);
        } catch (\Throwable $e) {
            Log::warning("KampusClient POST {$endpoint} (logout) warning: " . $e->getMessage());
            return ['status' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Parsing dan validasi response JSON.
     */
    protected function handleResponse(Response $response, string $endpoint): array
    {
        $status = $response->status();
        $json = $response->json();

        if ($response->successful()) {
            return is_array($json) ? $json : [];
        }

        $message = is_array($json) && isset($json['message']) 
            ? (string) $json['message'] 
            : "HTTP error {$status} saat mengakses {$endpoint}";

        throw new RuntimeException($message, $status);
    }
}
