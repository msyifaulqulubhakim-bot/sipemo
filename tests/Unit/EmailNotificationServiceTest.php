<?php

namespace Tests\Unit;

use App\Models\FinalDraft;
use App\Models\Modul;
use App\Models\PenyusunApplication;
use App\Models\PublicationModul;
use App\Models\ReviewerApplication;
use App\Models\User;
use App\Notifications\FinalDraftLpmValidatedNotification;
use App\Notifications\FinalDraftReadyForLpmNotification;
use App\Notifications\FinalDraftReviewerValidatedNotification;
use App\Notifications\ModulValidatedNotification;
use App\Notifications\PenyusunApprovedNotification;
use App\Notifications\PenyusunRejectedNotification;
use App\Notifications\PublicationValidatedNotification;
use App\Notifications\ReviewerApprovedNotification;
use App\Notifications\ReviewerRejectedNotification;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailNotificationServiceTest extends TestCase
{
    protected NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationService();
        Notification::fake();
    }

    /**
     * Skenario 1: Email Penyusun disetujui.
     */
    public function test_penyusun_approved_notification_dispatches_with_correct_data(): void
    {
        $app = new PenyusunApplication([
            'nama_penyusun' => 'Dosen Satu, M.Pd.',
            'email' => 'penyusun1@example.com',
            'mata_kuliah' => 'Ushul al-Tarbiyah',
            'jurusan' => 'S1 PJJ PAI',
            'nidn' => '1234567890',
        ]);
        $app->id = 1;

        $this->service->sendPenyusunApproved($app, 'Periode Gasal 2026/2027');

        Notification::assertSentOnDemand(
            PenyusunApprovedNotification::class,
            function ($notification, $channels, $notifiable) {
                $mail = $notification->toMail($notifiable);
                return $notifiable->routes['mail'] === 'penyusun1@example.com'
                    && str_contains($mail->subject, 'Disetujui')
                    && $notification->afterCommit === true;
            }
        );
    }

    /**
     * Skenario 2: Email Penyusun ditolak.
     */
    public function test_penyusun_rejected_notification_dispatches_with_rejection_reason(): void
    {
        $app = new PenyusunApplication([
            'nama_penyusun' => 'Dosen Dua, M.Pd.',
            'email' => 'penyusun2@example.com',
            'mata_kuliah' => 'Filsafat Pendidikan',
            'jurusan' => 'S1 PJJ PAI',
        ]);
        $app->id = 2;

        $this->service->sendPenyusunRejected($app, 'Berkas pendaftaran tidak lengkap');

        Notification::assertSentOnDemand(
            PenyusunRejectedNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === 'penyusun2@example.com'
                    && $notification->rejectionReason === 'Berkas pendaftaran tidak lengkap'
                    && $notification->afterCommit === true;
            }
        );
    }

    /**
     * Skenario 3: Email penolakan otomatis duplikat Penyusun.
     */
    public function test_penyusun_duplicate_rejected_notification_dispatches_properly(): void
    {
        $app = new PenyusunApplication([
            'nama_penyusun' => 'Dosen Tiga, M.Pd.',
            'email' => 'penyusun3@example.com',
            'mata_kuliah' => 'Ushul al-Tarbiyah',
            'jurusan' => 'S1 PJJ PAI',
        ]);
        $app->id = 3;

        $this->service->sendPenyusunRejected($app, 'Mata kuliah ini sudah diambil oleh penyusun lain');

        Notification::assertSentOnDemand(
            PenyusunRejectedNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === 'penyusun3@example.com'
                    && str_contains($notification->rejectionReason, 'sudah diambil oleh penyusun lain');
            }
        );
    }

    /**
     * Skenario 4: Email Reviewer disetujui.
     */
    public function test_reviewer_approved_notification_dispatches_with_assigned_courses(): void
    {
        $app = new ReviewerApplication([
            'nama_reviewer' => 'Prof. Reviewer, Ph.D.',
            'email' => 'reviewer1@example.com',
            'nidn' => '9876543210',
        ]);
        $app->id = 1;

        $courses = ['Ushul al-Tarbiyah', 'Filsafat Pendidikan Islam'];
        $this->service->sendReviewerApproved($app, $courses);

        Notification::assertSentOnDemand(
            ReviewerApprovedNotification::class,
            function ($notification, $channels, $notifiable) use ($courses) {
                $mail = $notification->toMail($notifiable);
                return $notifiable->routes['mail'] === 'reviewer1@example.com'
                    && $notification->mataKuliahList === $courses
                    && str_contains($mail->subject, 'Pendaftaran Reviewer Disetujui')
                    && $notification->afterCommit === true;
            }
        );
    }

    /**
     * Skenario 5: Email Reviewer ditolak.
     */
    public function test_reviewer_rejected_notification_dispatches_with_reason(): void
    {
        $app = new ReviewerApplication([
            'nama_reviewer' => 'Calon Reviewer, M.A.',
            'email' => 'reviewer2@example.com',
        ]);
        $app->id = 2;

        $this->service->sendReviewerRejected($app, 'Bidang keilmuan belum sesuai dengan kuota yang dibutuhkan');

        Notification::assertSentOnDemand(
            ReviewerRejectedNotification::class,
            function ($notification, $channels, $notifiable) {
                $mail = $notification->toMail($notifiable);
                return $notifiable->routes['mail'] === 'reviewer2@example.com'
                    && $notification->rejectionReason === 'Bidang keilmuan belum sesuai dengan kuota yang dibutuhkan'
                    && str_contains($mail->subject, 'Pemberitahuan Status Pendaftaran Reviewer');
            }
        );
    }

    /**
     * Skenario 6: Email modul disetujui.
     */
    public function test_modul_approved_notification_dispatches_properly(): void
    {
        $penyusun = new PenyusunApplication([
            'nama_penyusun' => 'Dosen Satu, M.Pd.',
            'email' => 'penyusun1@example.com',
            'mata_kuliah' => 'Ushul al-Tarbiyah',
        ]);
        $penyusun->id = 1;

        $modul = new Modul([
            'penyusun_application_id' => 1,
            'tahap_id' => 2,
            'status' => 'approved',
            'catatan_admin' => 'Modul sangat baik dan lengkap',
        ]);
        $modul->id = 10;
        $modul->setRelation('penyusunApplication', $penyusun);

        $this->service->sendModulValidated($modul, 'approved', 'Modul sangat baik dan lengkap');

        Notification::assertSentOnDemand(
            ModulValidatedNotification::class,
            function ($notification, $channels, $notifiable) {
                $mail = $notification->toMail($notifiable);
                return $notifiable->routes['mail'] === 'penyusun1@example.com'
                    && $notification->status === 'approved'
                    && str_contains($mail->subject, 'Disetujui');
            }
        );
    }

    /**
     * Skenario 7: Email modul ditolak.
     */
    public function test_modul_rejected_notification_dispatches_with_admin_notes(): void
    {
        $penyusun = new PenyusunApplication([
            'nama_penyusun' => 'Dosen Satu, M.Pd.',
            'email' => 'penyusun1@example.com',
            'mata_kuliah' => 'Ushul al-Tarbiyah',
        ]);
        $penyusun->id = 1;

        $modul = new Modul([
            'penyusun_application_id' => 1,
            'tahap_id' => 1,
            'status' => 'rejected',
            'catatan_admin' => 'Harap lengkapi tujuan pembelajaran khusus',
        ]);
        $modul->id = 11;
        $modul->setRelation('penyusunApplication', $penyusun);

        $this->service->sendModulValidated($modul, 'rejected', 'Harap lengkapi tujuan pembelajaran khusus');

        Notification::assertSentOnDemand(
            ModulValidatedNotification::class,
            function ($notification, $channels, $notifiable) {
                $mail = $notification->toMail($notifiable);
                return $notifiable->routes['mail'] === 'penyusun1@example.com'
                    && $notification->status === 'rejected'
                    && str_contains($mail->subject, 'Revisi');
            }
        );
    }

    /**
     * Skenario 8: Email final draft disetujui Reviewer (ke Penyusun dan Tim LPM).
     */
    public function test_final_draft_approved_by_reviewer_dispatches_to_penyusun_and_lpm(): void
    {
        $penyusun = new PenyusunApplication([
            'nama_penyusun' => 'Dosen Satu, M.Pd.',
            'email' => 'penyusun1@example.com',
            'mata_kuliah' => 'Ushul al-Tarbiyah',
        ]);

        $finalDraft = new FinalDraft([
            'penyusun_application_id' => 1,
            'judul_modul' => 'Naskah Lengkap Ushul al-Tarbiyah',
            'status' => 'approved_by_reviewer',
            'catatan_reviewer' => 'Naskah siap dan layak uji mutu LPM',
        ]);
        $finalDraft->id = 5;
        $finalDraft->setRelation('penyusunApplication', $penyusun);

        $this->service->sendFinalDraftReviewerValidated(
            $finalDraft,
            'approved_by_reviewer',
            'Naskah siap dan layak uji mutu LPM',
            'Dr. Reviewer Utama'
        );

        // 1. Cek notifikasi ke penyusun
        Notification::assertSentOnDemand(
            FinalDraftReviewerValidatedNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === 'penyusun1@example.com'
                    && $notification->status === 'approved_by_reviewer';
            }
        );
    }

    /**
     * Skenario 9: Email final draft ditolak Reviewer (ke Penyusun saja).
     */
    public function test_final_draft_rejected_by_reviewer_dispatches_to_penyusun_only(): void
    {
        $penyusun = new PenyusunApplication([
            'nama_penyusun' => 'Dosen Satu, M.Pd.',
            'email' => 'penyusun1@example.com',
            'mata_kuliah' => 'Ushul al-Tarbiyah',
        ]);

        $finalDraft = new FinalDraft([
            'penyusun_application_id' => 1,
            'judul_modul' => 'Naskah Draft',
            'status' => 'rejected_by_reviewer',
            'catatan_reviewer' => 'Bab 4 perlu diperbaiki sitasinya',
        ]);
        $finalDraft->id = 6;
        $finalDraft->setRelation('penyusunApplication', $penyusun);

        $this->service->sendFinalDraftReviewerValidated(
            $finalDraft,
            'rejected_by_reviewer',
            'Bab 4 perlu diperbaiki sitasinya',
            'Dr. Reviewer Utama'
        );

        Notification::assertSentOnDemand(
            FinalDraftReviewerValidatedNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === 'penyusun1@example.com'
                    && $notification->status === 'rejected_by_reviewer';
            }
        );

        Notification::assertSentOnDemandTimes(
            FinalDraftReviewerValidatedNotification::class,
            1
        );
    }

    /**
     * Skenario 10: Email final draft disetujui LPM.
     */
    public function test_final_draft_approved_by_lpm_dispatches_to_penyusun(): void
    {
        $penyusun = new PenyusunApplication([
            'nama_penyusun' => 'Dosen Satu, M.Pd.',
            'email' => 'penyusun1@example.com',
            'mata_kuliah' => 'Ushul al-Tarbiyah',
        ]);

        $finalDraft = new FinalDraft([
            'penyusun_application_id' => 1,
            'judul_modul' => 'Naskah Lengkap Final',
            'status' => 'approved',
            'catatan_lpm' => 'Validasi LPM selesai dan memenuhi standar mutu',
        ]);
        $finalDraft->id = 7;
        $finalDraft->setRelation('penyusunApplication', $penyusun);

        $this->service->sendFinalDraftLpmValidated($finalDraft, 'approved', 'Validasi LPM selesai dan memenuhi standar mutu');

        Notification::assertSentOnDemand(
            FinalDraftLpmValidatedNotification::class,
            function ($notification, $channels, $notifiable) {
                $mail = $notification->toMail($notifiable);
                return $notifiable->routes['mail'] === 'penyusun1@example.com'
                    && $notification->status === 'approved'
                    && str_contains($mail->subject, 'Divalidasi Resmi oleh LPM');
            }
        );
    }

    /**
     * Skenario 11: Email final draft ditolak LPM.
     */
    public function test_final_draft_rejected_by_lpm_dispatches_with_notes(): void
    {
        $penyusun = new PenyusunApplication([
            'nama_penyusun' => 'Dosen Satu, M.Pd.',
            'email' => 'penyusun1@example.com',
            'mata_kuliah' => 'Ushul al-Tarbiyah',
        ]);

        $finalDraft = new FinalDraft([
            'penyusun_application_id' => 1,
            'judul_modul' => 'Naskah Lengkap Final',
            'status' => 'rejected',
            'catatan_lpm' => 'Format daftar pustaka tidak sesuai panduan LPM',
        ]);
        $finalDraft->id = 8;
        $finalDraft->setRelation('penyusunApplication', $penyusun);

        $this->service->sendFinalDraftLpmValidated($finalDraft, 'rejected', 'Format daftar pustaka tidak sesuai panduan LPM');

        Notification::assertSentOnDemand(
            FinalDraftLpmValidatedNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === 'penyusun1@example.com'
                    && $notification->status === 'rejected'
                    && $notification->catatanLpm === 'Format daftar pustaka tidak sesuai panduan LPM';
            }
        );
    }

    /**
     * Skenario 12: Email publikasi disetujui Admin.
     */
    public function test_publication_approved_dispatches_to_penyusun(): void
    {
        $penyusun = new PenyusunApplication([
            'nama_penyusun' => 'Dosen Satu, M.Pd.',
            'email' => 'penyusun1@example.com',
            'mata_kuliah' => 'Ushul al-Tarbiyah',
        ]);

        $pub = new PublicationModul([
            'penyusun_application_id' => 1,
            'judul_modul' => 'Buku Ajar Ushul al-Tarbiyah',
            'status' => 'approved',
            'catatan_admin' => 'Berkas HKI dan publikasi lengkap',
        ]);
        $pub->id = 20;
        $pub->setRelation('penyusunApplication', $penyusun);

        $this->service->sendPublicationValidated($pub, 'approved', 'Berkas HKI dan publikasi lengkap');

        Notification::assertSentOnDemand(
            PublicationValidatedNotification::class,
            function ($notification, $channels, $notifiable) {
                $mail = $notification->toMail($notifiable);
                return $notifiable->routes['mail'] === 'penyusun1@example.com'
                    && $notification->status === 'approved'
                    && str_contains($mail->subject, 'Disetujui');
            }
        );
    }

    /**
     * Skenario 13: Email publikasi ditolak Admin.
     */
    public function test_publication_rejected_dispatches_with_notes(): void
    {
        $penyusun = new PenyusunApplication([
            'nama_penyusun' => 'Dosen Satu, M.Pd.',
            'email' => 'penyusun1@example.com',
            'mata_kuliah' => 'Ushul al-Tarbiyah',
        ]);

        $pub = new PublicationModul([
            'penyusun_application_id' => 1,
            'judul_modul' => 'Buku Ajar Ushul al-Tarbiyah',
            'status' => 'rejected',
            'catatan_admin' => 'Sertifikat HKI buram, mohon scan ulang',
        ]);
        $pub->id = 21;
        $pub->setRelation('penyusunApplication', $penyusun);

        $this->service->sendPublicationValidated($pub, 'rejected', 'Sertifikat HKI buram, mohon scan ulang');

        Notification::assertSentOnDemand(
            PublicationValidatedNotification::class,
            function ($notification, $channels, $notifiable) {
                return $notifiable->routes['mail'] === 'penyusun1@example.com'
                    && $notification->status === 'rejected'
                    && $notification->catatanAdmin === 'Sertifikat HKI buram, mohon scan ulang';
            }
        );
    }

    /**
     * Skenario 17: Penerima kosong ditangani dengan aman tanpa exception.
     */
    public function test_empty_recipient_is_handled_gracefully_without_exception(): void
    {
        $app = new PenyusunApplication([
            'nama_penyusun' => 'Dosen Tanpa Email',
            'email' => '',
        ]);

        // Tidak boleh melempar exception apapun
        $this->service->sendPenyusunApproved($app);
        $this->service->sendPenyusunRejected($app);

        Notification::assertNothingSent();
    }

    /**
     * Verifikasi bahwa seluruh Notification class mengimplementasikan ShouldQueue dan afterCommit = true.
     */
    public function test_all_notifications_implement_should_queue_and_after_commit(): void
    {
        $instances = [
            new PenyusunApprovedNotification(new PenyusunApplication()),
            new PenyusunRejectedNotification(new PenyusunApplication()),
            new ReviewerApprovedNotification(new ReviewerApplication()),
            new ReviewerRejectedNotification(new ReviewerApplication()),
            new ModulValidatedNotification(new Modul(), 'approved'),
            new FinalDraftReviewerValidatedNotification(new FinalDraft(), 'approved_by_reviewer'),
            new FinalDraftReadyForLpmNotification(new FinalDraft()),
            new FinalDraftLpmValidatedNotification(new FinalDraft(), 'approved'),
            new PublicationValidatedNotification(new PublicationModul(), 'approved'),
        ];

        foreach ($instances as $instance) {
            $class = get_class($instance);
            $interfaces = class_implements($class);
            $this->assertArrayHasKey(
                ShouldQueue::class,
                $interfaces,
                "Class {$class} harus mengimplementasikan ShouldQueue"
            );

            $this->assertTrue(
                $instance->afterCommit === true,
                "Class {$class} harus memiliki afterCommit === true"
            );
        }
    }
}
