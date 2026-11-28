<?php

namespace App\Services\Kampus;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class KampusAuthService
{
    protected KampusClient $client;

    public function __construct(KampusClient $client)
    {
        $this->client = $client;
    }

    /**
     * Autentikasi ke API portal kampus dan mengembalikan Bearer token.
     */
    public function loginPortal(string $username, string $password): string
    {
        $endpoint = config('kampus.endpoints.login', '/api/v2/portal/login');

        $response = $this->client->postPortalLogin($endpoint, [
            'username' => $username,
            'password' => $password,
        ]);

        $token = $response['data']['token'] ?? $response['token'] ?? null;

        if (! $token) {
            throw new RuntimeException($response['message'] ?? 'Token tidak ditemukan dalam response login kampus.');
        }

        return (string) $token;
    }

    /**
     * Mengambil profil pengguna dari API bridge dengan Bearer token pengguna.
     */
    public function getProfile(string $token): array
    {
        $endpoint = config('kampus.endpoints.profil', '/api/v2/profil');

        $response = $this->client->getWithUserToken($endpoint, $token);

        return $response['data'] ?? $response;
    }

    /**
     * Memutus sesi token di server bridge agar token tidak menggantung.
     */
    public function logoutPortal(string $token): bool
    {
        $endpoint = config('kampus.endpoints.logout', '/api/v2/logout');

        try {
            $this->client->postWithUserToken($endpoint, $token);
            return true;
        } catch (\Throwable $e) {
            Log::warning("Gagal logout portal token bridge: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Alur lengkap autentikasi akun kampus:
     * 1. Validasi login ke bridge API
     * 2. Ambil data profil
     * 3. Tutup sesi token bridge
     * 4. Sinkronisasikan / buat user lokal SIPeMo dengan aman
     */
    public function authenticateAndSyncUser(string $username, string $password): User
    {
        $token = $this->loginPortal($username, $password);

        try {
            $profile = $this->getProfile($token);
        } finally {
            // Selalu panggil logout agar token di server bridge tidak menggantung
            $this->logoutPortal($token);
        }

        return $this->syncUserFromProfile($username, $profile);
    }

    /**
     * Mencocokkan atau membuat user lokal di SIPeMo tanpa mengubah role eksisting.
     */
    public function syncUserFromProfile(string $username, array $profile): User
    {
        $nip = $profile['nip'] ?? $profile['username'] ?? $username;
        $nidn = $profile['nidn'] ?? null;
        $name = $profile['nama'] ?? $profile['name'] ?? $username;
        $email = $profile['email'] ?? null;

        // Jika email kosong dari API, gunakan format fallback standar kampus
        if (empty($email)) {
            $email = Str::lower($username) . '@uinssc.ac.id';
        }

        // Cari pengguna eksisting berdasarkan NIP atau Email
        $user = User::where(function ($query) use ($nip, $email) {
            if (! empty($nip)) {
                $query->where('nip', $nip);
            }
            if (! empty($email)) {
                $query->orWhere('email', $email);
            }
        })->first();

        if ($user) {
            // Update identitas profil jika sebelumnya kosong
            $updated = false;

            if (empty($user->nip) && ! empty($nip)) {
                $user->nip = $nip;
                $updated = true;
            }

            if (empty($user->nidn) && ! empty($nidn)) {
                $user->nidn = $nidn;
                $updated = true;
            }

            if (! empty($name) && $user->name !== $name) {
                $user->name = $name;
                $updated = true;
            }

            // Tandai auth source kampus jika sebelumnya default
            if ($user->auth_source === 'local' && ! $user->is_admin && ! $user->is_lpm) {
                $user->auth_source = 'kampus_api';
                $updated = true;
            }

            if ($updated) {
                $user->save();
            }

            return $user;
        }

        // Buat akun baru pengguna kampus dengan status default (bukan penyusun/reviewer/admin/lpm)
        return User::create([
            'name' => $name,
            'email' => $email,
            'nip' => $nip,
            'nidn' => $nidn,
            'auth_source' => 'kampus_api',
            'password' => Hash::make(Str::random(32)),
            'is_admin' => false,
            'is_penyusun' => false,
            'is_lpm' => false,
            'is_reviewer' => false,
        ]);
    }
}
