<?php

namespace App\Notifications;

use App\Models\PenyusunApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PenyusunApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public PenyusunApplication $application;
    public ?string $periodeNama;

    public function __construct(PenyusunApplication $application, ?string $periodeNama = null)
    {
        $this->afterCommit();
        $this->application = $application;
        $this->periodeNama = $periodeNama;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = '[SIPeMo] Pendaftaran Penyusun Modul Disetujui';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.penyusun_approved', [
                'namaPenyusun' => $this->application->nama_penyusun,
                'mataKuliah' => $this->application->mata_kuliah,
                'jurusan' => $this->application->jurusan,
                'periode' => $this->periodeNama,
                'nidn' => $this->application->nidn,
                'loginUrl' => url('/login'),
            ]);
    }
}
