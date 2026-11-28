<?php

namespace App\Notifications;

use App\Models\FinalDraft;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FinalDraftLpmValidatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public FinalDraft $finalDraft;
    public string $status;
    public ?string $catatanLpm;

    public function __construct(FinalDraft $finalDraft, string $status, ?string $catatanLpm = null)
    {
        $this->afterCommit();
        $this->finalDraft = $finalDraft;
        $this->status = $status;
        $this->catatanLpm = $catatanLpm ?? $finalDraft->catatan_lpm;
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

        $subject = $this->status === 'approved'
            ? '[SIPeMo] Final Draft Modul Divalidasi Resmi oleh LPM'
            : '[SIPeMo] Catatan Validasi LPM untuk Final Draft Modul';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.final_draft_lpm_validated', [
                'namaPenyusun' => $namaPenyusun,
                'mataKuliah' => $mataKuliah,
                'judulModul' => $this->finalDraft->judul_modul,
                'status' => $this->status,
                'catatanLpm' => $this->catatanLpm,
                'publicationUrl' => url('/penyusun/publication'),
                'finalDraftUrl' => url('/penyusun/final-draft'),
            ]);
    }
}
