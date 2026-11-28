<?php

namespace App\Notifications;

use App\Models\PublicationModul;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PublicationValidatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public PublicationModul $publicationModul;
    public string $status;
    public ?string $catatanAdmin;

    public function __construct(PublicationModul $publicationModul, string $status, ?string $catatanAdmin = null)
    {
        $this->afterCommit();
        $this->publicationModul = $publicationModul;
        $this->status = $status;
        $this->catatanAdmin = $catatanAdmin ?? $publicationModul->catatan_admin;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $namaPenyusun = $this->publicationModul->penyusunApplication?->nama_penyusun 
            ?? $this->publicationModul->penyusunApplication?->user?->name 
            ?? 'Penyusun';
        $mataKuliah = $this->publicationModul->penyusunApplication?->mata_kuliah ?? '';

        $subject = $this->status === 'approved'
            ? '[SIPeMo] Berkas Publikasi & HKI Modul Disetujui'
            : '[SIPeMo] Permintaan Perbaikan Berkas Publikasi Modul';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.publication_validated', [
                'namaPenyusun' => $namaPenyusun,
                'mataKuliah' => $mataKuliah,
                'judulModul' => $this->publicationModul->judul_modul,
                'status' => $this->status,
                'catatanAdmin' => $this->catatanAdmin,
                'publicationUrl' => url('/penyusun/publication'),
            ]);
    }
}
