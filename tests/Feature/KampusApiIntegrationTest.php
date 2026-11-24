<?php

namespace Tests\Feature;

use App\Models\Fakultas;
use App\Models\Jurusan;
use App\Models\User;
use App\Services\Kampus\KampusSyncService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test integrasi API Kampus SIPeMo.
 *
 * Menggunakan DatabaseTransactions agar tidak ada data pengujian yang tersisa.
 * Menggunakan Http::fake() agar tidak memanggil server nyata kampus.
 */
class KampusApiIntegrationTest extends TestCase
{
    use DatabaseTransactions;

    private function fakeLoginSuccess(string $token = 'test-sanctum-token'): void
    {
        Http::fake([
            '*/api/v2/portal/login' => Http::response([
                'status' => true,
                'message' => 'Login berhasil',
                'data' => ['token' => $token],
            ], 200),
            '*/api/v2/profil' => Http::response([
                'status' => true,
                'data' => [
                    'username' => '198501012010011001',
                    'nama' => 'Dr. Dosen Penguji, M.Pd.',
                    'nip' => '198501012010011001',
                    'nidn' => '2001018501',
                    'email' => 'dosen.penguji@uinssc.ac.id',
                    'kode_prodi' => '113',
                ],
            ], 200),
            '*/api/v2/logout' => Http::response([
                'status' => true,
                'message' => 'Logout berhasil',
            ], 200),
        ]);
    }

    private function fakeLoginFailed(): void
    {
        Http::fake([
            '*/api/v2/portal/login' => Http::response([
                'status' => false,
                'message' => 'Username atau password salah.',
            ], 401),
        ]);
    }

    private function fakeFakultasAndProdi(): void
    {
        Http::fake([
            '*/api/fakultas' => Http::response([
                'status' => true,
                'count' => 2,
                'data' => [
                    ['kode_fakultas' => 91, 'nama_fakultas' => 'FAKULTAS TEST 1', 'nama_fakultas_singkat' => 'FT1'],
                    ['kode_fakultas' => 92, 'nama_fakultas' => 'FAKULTAS TEST 2', 'nama_fakultas_singkat' => 'FT2'],
                ],
            ], 200),
            '*/api/program_studi' => Http::response([
                'status' => true,
                'count' => 2,
                'data' => [
                    [
                        'id_prodi' => 991,
                        'kode_prodi' => 'TEST991',
                        'nama_prodi' => 'Prodi Uji Coba 1',
                        'jenjang' => 'S1',
                        'sks_tempuh' => 144,
                        'gelar_akademik' => 'S.Kom.',
                        'gelar_akademik_full' => 'Sarjana Komputer',
                        'fakultas' => 'FAKULTAS TEST 1',
                        'fakultas_kode' => 91,
                    ],
                    [
                        'id_prodi' => 992,
                        'kode_prodi' => 'TEST992',
                        'nama_prodi' => 'Prodi Uji Coba 2',
                        'jenjang' => 'S1',
                        'sks_tempuh' => 144,
                        'gelar_akademik' => 'S.Pd.',
                        'gelar_akademik_full' => 'Sarjana Pendidikan',
                        'fakultas' => 'FAKULTAS TEST 2',
                        'fakultas_kode' => 92,
                    ],
                ],
            ], 200),
        ]);
    }

    public function test_halaman_login_dapat_ditampilkan(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Masuk ke SiPeMo');
    }

    public function test_form_login_kampus_tersedia_di_halaman_login(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('login/kampus');
    }

    public function test_login_kampus_berhasil_dan_membuat_sesi_pengguna(): void
    {
        $this->fakeLoginSuccess();

        $response = $this->post(route('login.kampus'), [
            'kampus_username' => '198501012010011001',
            'kampus_password' => 'secret123',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'dosen.penguji@uinssc.ac.id',
            'nip' => '198501012010011001',
            'nidn' => '2001018501',
            'auth_source' => 'kampus_api',
        ]);
    }

    public function test_login_kampus_gagal_karena_kredensial_salah_memberikan_error_validasi(): void
    {
        $this->fakeLoginFailed();

        $response = $this->post(route('login.kampus'), [
            'kampus_username' => '123456789',
            'kampus_password' => 'salah',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('kampus_username');
    }

    public function test_login_kampus_tanpa_username_ditolak_validasi_lokal(): void
    {
        $response = $this->post(route('login.kampus'), [
            'kampus_username' => '',
            'kampus_password' => 'secret123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('kampus_username');
    }

    public function test_login_kampus_tidak_mengubah_role_admin_yang_sudah_ada(): void
    {
        $this->fakeLoginSuccess();

        // Buat user admin lokal dengan email yang sama dengan data profil kampus
        $adminUser = User::factory()->create([
            'email' => 'dosen.penguji@uinssc.ac.id',
            'is_admin' => true,
            'is_penyusun' => false,
            'is_lpm' => false,
            'is_reviewer' => false,
        ]);

        $this->post(route('login.kampus'), [
            'kampus_username' => '198501012010011001',
            'kampus_password' => 'secret123',
        ]);

        $adminUser->refresh();
        $this->assertTrue((bool) $adminUser->is_admin, 'Role admin tidak boleh berubah setelah sinkronisasi login kampus');
        $this->assertFalse((bool) $adminUser->is_penyusun);
    }

    public function test_user_baru_dari_kampus_tidak_otomatis_mendapat_role_penyusun(): void
    {
        $this->fakeLoginSuccess();

        $this->post(route('login.kampus'), [
            'kampus_username' => '198501012010011001',
            'kampus_password' => 'secret123',
        ]);

        $user = User::where('email', 'dosen.penguji@uinssc.ac.id')->first();
        $this->assertNotNull($user);
        $this->assertFalse((bool) $user->is_penyusun, 'User baru kampus tidak boleh otomatis jadi penyusun');
        $this->assertFalse((bool) $user->is_admin, 'User baru kampus tidak boleh otomatis jadi admin');
        $this->assertFalse((bool) $user->is_lpm, 'User baru kampus tidak boleh otomatis jadi lpm');
        $this->assertFalse((bool) $user->is_reviewer, 'User baru kampus tidak boleh otomatis jadi reviewer');
    }

    public function test_login_kampus_kedua_kali_memperbarui_profil_tanpa_duplikat_user(): void
    {
        $this->fakeLoginSuccess();

        // Login pertama
        $this->post(route('login.kampus'), ['kampus_username' => '198501012010011001', 'kampus_password' => 'secret123']);
        $this->assertAuthenticated();
        auth()->logout();

        $countBefore = User::where('email', 'dosen.penguji@uinssc.ac.id')->count();

        // Login kedua
        $this->fakeLoginSuccess();
        $this->post(route('login.kampus'), ['kampus_username' => '198501012010011001', 'kampus_password' => 'secret123']);
        $this->assertAuthenticated();

        $countAfter = User::where('email', 'dosen.penguji@uinssc.ac.id')->count();
        $this->assertEquals($countBefore, $countAfter, 'Login kampus kedua kali tidak boleh membuat duplikat user');
    }

    public function test_sinkronisasi_fakultas_menyimpan_data_dengan_benar(): void
    {
        $this->fakeFakultasAndProdi();

        $syncService = app(KampusSyncService::class);
        $result = $syncService->syncFakultas();

        $this->assertGreaterThan(0, $result['total']);
        $this->assertDatabaseHas('fakultas', [
            'kode_fakultas' => 91,
            'nama_singkat' => 'FT1',
        ]);
        $this->assertDatabaseHas('fakultas', [
            'kode_fakultas' => 92,
            'nama_singkat' => 'FT2',
        ]);
    }

    public function test_sinkronisasi_prodi_menyimpan_data_dengan_benar(): void
    {
        $this->fakeFakultasAndProdi();

        $syncService = app(KampusSyncService::class);
        $syncService->syncFakultas();
        $result = $syncService->syncProgramStudi();

        $this->assertGreaterThan(0, $result['total']);
        $this->assertDatabaseHas('jurusans', [
            'id_prodi_kampus' => 991,
            'kode_jurusan' => 'TEST991',
        ]);
    }

    public function test_sinkronisasi_bersifat_idempoten_tidak_duplikat(): void
    {
        $this->fakeFakultasAndProdi();

        $syncService = app(KampusSyncService::class);
        $syncService->syncFakultas();

        $countBefore = Fakultas::count();

        $this->fakeFakultasAndProdi();
        $syncService->syncFakultas();

        $countAfter = Fakultas::count();
        $this->assertEquals($countBefore, $countAfter, 'Sinkronisasi ulang tidak boleh membuat duplikat baris fakultas');
    }
}
