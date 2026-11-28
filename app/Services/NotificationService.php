<?php

namespace App\Services;

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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Kirim notifikasi pendaftaran penyusun disetujui.
     */
    public function sendPenyusunApproved(PenyusunApplication $application, ?string $periodeNama = null): void
    {
        if (empty($application->email)) {
            Log::warning("PenyusunApprovedNotification dilewati: email kosong untuk aplikasi ID {$application->id}");
            return;
        }

        try {
            Notification::route('mail', $application->email)
                ->notify(new PenyusunApprovedNotification($application, $periodeNama));

            Log::info("PenyusunApprovedNotification diantrekan untuk {$application->email}");
        } catch (\Throwable $e) {
            Log::error("Gagal mengirim PenyusunApprovedNotification: " . $e->getMessage());
        }
    }

    /**
     * Kirim notifikasi pendaftaran penyusun ditolak (manual atau auto-reject).
     */
    public function sendPenyusunRejected(PenyusunApplication $application, ?string $rejectionReason = null): void
    {
        if (empty($application->email)) {
            Log::warning("PenyusunRejectedNotification dilewati: email kosong untuk aplikasi ID {$application->id}");
            return;
        }

        try {
            Notification::route('mail', $application->email)
                ->notify(new PenyusunRejectedNotification($application, $rejectionReason));

            Log::info("PenyusunRejectedNotification diantrekan untuk {$application->email} (Alasan: " . ($rejectionReason ?? 'Tanpa alasan') . ")");
        } catch (\Throwable $e) {
            Log::error("Gagal mengirim PenyusunRejectedNotification: " . $e->getMessage());
        }
    }

    /**
     * Kirim notifikasi pendaftaran reviewer disetujui & penugasan mata kuliah.
     */
    public function sendReviewerApproved(ReviewerApplication $application, array $mataKuliahList = []): void
    {
        if (empty($application->email)) {
            Log::warning("ReviewerApprovedNotification dilewati: email kosong untuk reviewer ID {$application->id}");
            return;
        }

        try {
            Notification::route('mail', $application->email)
                ->notify(new ReviewerApprovedNotification($application, $mataKuliahList));

            Log::info("ReviewerApprovedNotification diantrekan untuk {$application->email}");
        } catch (\Throwable $e) {
            Log::error("Gagal mengirim ReviewerApprovedNotification: " . $e->getMessage());
        }
    }

    /**
     * Kirim notifikasi pendaftaran reviewer ditolak.
     */
    public function sendReviewerRejected(ReviewerApplication $application, ?string $rejectionReason = null): void
    {
        if (empty($application->email)) {
            Log::warning("ReviewerRejectedNotification dilewati: email kosong untuk reviewer ID {$application->id}");
            return;
        }

        try {
            Notification::route('mail', $application->email)
                ->notify(new ReviewerRejectedNotification($application, $rejectionReason));

            Log::info("ReviewerRejectedNotification diantrekan untuk {$application->email}");
        } catch (\Throwable $e) {
            Log::error("Gagal mengirim ReviewerRejectedNotification: " . $e->getMessage());
        }
    }

    /**
     * Kirim notifikasi validasi modul tahap oleh Admin.
     */
    public function sendModulValidated(Modul $modul, string $status, ?string $catatanAdmin = null): void
    {
        $email = $modul->penyusunApplication?->email;
        if (empty($email)) {
            Log::warning("ModulValidatedNotification dilewati: email penyusun tidak ditemukan untuk modul ID {$modul->id}");
            return;
        }

        try {
            Notification::route('mail', $email)
                ->notify(new ModulValidatedNotification($modul, $status, $catatanAdmin));

            Log::info("ModulValidatedNotification ({$status}) diantrekan untuk {$email}");
        } catch (\Throwable $e) {
            Log::error("Gagal mengirim ModulValidatedNotification: " . $e->getMessage());
        }
    }

    /**
     * Kirim notifikasi validasi final draft oleh Reviewer.
     * Jika disetujui reviewer:
     * 1. Kirim notifikasi ke Penyusun
     * 2. Kirim pemberitahuan ke seluruh akun LPM yang relevan
     * Jika ditolak reviewer:
     * 1. Kirim notifikasi ke Penyusun beserta catatan perbaikan
     */
    public function sendFinalDraftReviewerValidated(
        FinalDraft $finalDraft,
        string $status,
        ?string $catatanReviewer = null,
        ?string $namaReviewer = null
    ): void {
        $email = $finalDraft->penyusunApplication?->email;

        // 1. Notifikasi ke Penyusun
        if (!empty($email)) {
            try {
                Notification::route('mail', $email)
                    ->notify(new FinalDraftReviewerValidatedNotification(
                        $finalDraft,
                        $status,
                        $catatanReviewer,
                        $namaReviewer
                    ));

                Log::info("FinalDraftReviewerValidatedNotification ({$status}) diantrekan untuk penyusun {$email}");
            } catch (\Throwable $e) {
                Log::error("Gagal mengirim FinalDraftReviewerValidatedNotification: " . $e->getMessage());
            }
        }

        // 2. Jika disetujui Reviewer, teruskan notifikasi ke Tim LPM
        if ($status === 'approved_by_reviewer') {
            $this->sendFinalDraftReadyForLpm($finalDraft, $namaReviewer);
        }
    }

    /**
     * Kirim pemberitahuan kepada seluruh akun LPM bahwa Final Draft siap divalidasi.
     */
    public function sendFinalDraftReadyForLpm(FinalDraft $finalDraft, ?string $namaReviewer = null): void
    {
        try {
            $lpmUsers = User::where('is_lpm', true)
                ->whereNotNull('email')
                ->where('email', '!=', '')
                ->get();

            if ($lpmUsers->isEmpty()) {
                Log::warning("FinalDraftReadyForLpmNotification: Tidak ditemukan user dengan role LPM aktif.");
                return;
            }

            Notification::send($lpmUsers, new FinalDraftReadyForLpmNotification($finalDraft, $namaReviewer));
            Log::info("FinalDraftReadyForLpmNotification diantrekan untuk {$lpmUsers->count()} akun LPM.");
        } catch (\Throwable $e) {
            Log::error("Gagal mengirim FinalDraftReadyForLpmNotification ke LPM: " . $e->getMessage());
        }
    }

    /**
     * Kirim notifikasi validasi final draft oleh LPM (disetujui/ditolak).
     */
    public function sendFinalDraftLpmValidated(FinalDraft $finalDraft, string $status, ?string $catatanLpm = null): void
    {
        $email = $finalDraft->penyusunApplication?->email;
        if (empty($email)) {
            Log::warning("FinalDraftLpmValidatedNotification dilewati: email penyusun tidak ditemukan untuk final draft ID {$finalDraft->id}");
            return;
        }

        try {
            Notification::route('mail', $email)
                ->notify(new FinalDraftLpmValidatedNotification($finalDraft, $status, $catatanLpm));

            Log::info("FinalDraftLpmValidatedNotification ({$status}) diantrekan untuk penyusun {$email}");
        } catch (\Throwable $e) {
            Log::error("Gagal mengirim FinalDraftLpmValidatedNotification: " . $e->getMessage());
        }
    }

    /**
     * Kirim notifikasi validasi berkas publikasi oleh Admin.
     */
    public function sendPublicationValidated(PublicationModul $publicationModul, string $status, ?string $catatanAdmin = null): void
    {
        $email = $publicationModul->penyusunApplication?->email;
        if (empty($email)) {
            Log::warning("PublicationValidatedNotification dilewati: email penyusun tidak ditemukan untuk publikasi ID {$publicationModul->id}");
            return;
        }

        try {
            Notification::route('mail', $email)
                ->notify(new PublicationValidatedNotification($publicationModul, $status, $catatanAdmin));

            Log::info("PublicationValidatedNotification ({$status}) diantrekan untuk penyusun {$email}");
        } catch (\Throwable $e) {
            Log::error("Gagal mengirim PublicationValidatedNotification: " . $e->getMessage());
        }
    }
}
