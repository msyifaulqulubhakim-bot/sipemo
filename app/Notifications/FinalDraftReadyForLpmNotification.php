<?php

namespace App\Notifications;

use App\Models\FinalDraft;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FinalDraftReadyForLpmNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public FinalDraft $finalDraft;
    public ?string $namaReviewer;

    public function __construct(FinalDraft $finalDraft, ?string $namaReviewer = null)
    {
        $this->afterCommit();
        $this->finalDraft = $finalDraft;
        $this->namaReviewer = $namaReviewer;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mataKuliah = $this->finalDraft->mataKuliah?->nama_mata_kuliah 
            ?? $this->finalDraft->penyusunApplication?->mata_kuliah 
            ?? 'Mata Kuliah Modul';
        $namaPenyusun = $this->finalDraft->penyusunApplication?->nama_penyusun ?? 'Penyusun';

        $subject = '[SIPeMo] Final Draft Modul Siap Divalidasi LPM';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.final_draft_ready_for_lpm', [
                'namaPenyusun' => $namaPenyusun,
                'mataKuliah' => $mataKuliah,
                'judulModul' => $this->finalDraft->judul_modul,
                'namaReviewer' => $this->namaReviewer,
                'catatanReviewer' => $this->finalDraft->catatan_reviewer,
                'lpmUrl' => url('/lpm/final-draft'),
            ]);
    }
}
