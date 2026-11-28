<?php

namespace App\Notifications;

use App\Models\FinalDraft;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FinalDraftReviewerValidatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public FinalDraft $finalDraft;
    public string $status;
    public ?string $catatanReviewer;
    public ?string $namaReviewer;

    public function __construct(FinalDraft $finalDraft, string $status, ?string $catatanReviewer = null, ?string $namaReviewer = null)
    {
        $this->afterCommit();
        $this->finalDraft = $finalDraft;
        $this->status = $status;
        $this->catatanReviewer = $catatanReviewer ?? $finalDraft->catatan_reviewer;
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

        $subject = $this->status === 'approved_by_reviewer'
            ? '[SIPeMo] Final Draft Modul Disetujui oleh Reviewer'
            : '[SIPeMo] Catatan Telaah Reviewer untuk Final Draft Modul';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.final_draft_reviewer_validated', [
                'namaPenyusun' => $namaPenyusun,
                'mataKuliah' => $mataKuliah,
                'judulModul' => $this->finalDraft->judul_modul,
                'namaReviewer' => $this->namaReviewer,
                'status' => $this->status,
                'catatanReviewer' => $this->catatanReviewer,
                'finalDraftUrl' => url('/penyusun/final-draft'),
            ]);
    }
}
