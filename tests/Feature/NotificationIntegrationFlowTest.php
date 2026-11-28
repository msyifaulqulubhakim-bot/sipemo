<?php

namespace Tests\Feature;

use App\Models\PenyusunApplication;
use App\Models\PeriodePenyusunan;
use App\Models\User;
use App\Notifications\PenyusunApprovedNotification;
use App\Notifications\PenyusunRejectedNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationIntegrationFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    /**
     * Skenario 14 & 16: Tidak ada email ketika status tidak berubah atau pengajuan sudah diproses (anti-duplicate).
     */
    public function test_no_notification_sent_if_application_status_is_not_pending(): void
    {
        $admin = User::where('is_admin', true)->first() ?? User::factory()->create(['is_admin' => true]);

        // Mock pendaftaran yang statusnya sudah 'approved'
        $application = PenyusunApplication::create([
            'nama_penyusun' => 'Dosen Uji Coba',
            'email' => 'dosentest@example.com',
            'mata_kuliah' => 'Ushul al-Tarbiyah',
            'jurusan' => 'S1 PJJ PAI',
            'semester' => '1',
            'nidn' => '12345678',
            'judul_bahan_ajar' => 'Buku Ushul',
            'status' => 'approved',
            'setuju_informasi' => 1,
            'setuju_pelaksanaan' => 1,
            'setuju_jml_modul' => 1,
            'setuju_pembiayaan' => 1,
        ]);

        // Coba panggil approve lagi pada status yang sudah approved
        $response = $this->actingAs($admin)->post(route('admin.penyusun.approve', $application));

        $response->assertSessionHas('error');
        // Pastikan tidak ada notifikasi ganda / email baru yang dikirim
        Notification::assertNothingSent();
    }

    /**
     * Skenario 15: Verifikasi bahwa jika transaksi dibatalkan (rollback), email tidak dikirim.
     */
    public function test_notification_is_not_sent_if_transaction_rolls_back(): void
    {
        try {
            DB::transaction(function () {
                $app = new PenyusunApplication([
                    'nama_penyusun' => 'Test Rollback',
                    'email' => 'rollback@example.com',
                ]);
                $app->id = 9999;

                $notification = new PenyusunApprovedNotification($app, 'Periode Test');
                $this->assertTrue($notification->afterCommit, 'Notification must have afterCommit enabled');

                // Simulasi error di tengah transaksi
                throw new \Exception('Rollback simulation error');
            });
        } catch (\Exception $e) {
            // Tangkap exception simulasi
        }

        Notification::assertNothingSent();
    }

    /**
     * Skenario 19: Memastikan notifikasi internal navbar tetap berfungsi normal tanpa regresi.
     */
    public function test_internal_navbar_notifications_remain_intact_and_functional(): void
    {
        $admin = User::where('is_admin', true)->first();
        if (!$admin) {
            $admin = User::factory()->create(['is_admin' => true]);
        }

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertStatus(302); // Redirect ke admin.dashboard

        $adminDashboard = $this->actingAs($admin)->get(route('admin.dashboard'));
        $adminDashboard->assertStatus(200);

        // Verifikasi bahwa variabel navbarNotifications dan navbarNotificationItems tetap di-share oleh view composer
        $this->assertNotNull($adminDashboard->viewData('navbarNotifications'));
        $this->assertNotNull($adminDashboard->viewData('navbarNotificationItems'));
    }
}
