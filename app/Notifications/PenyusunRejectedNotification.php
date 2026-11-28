<?php

namespace App\Notifications;

use App\Models\PenyusunApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PenyusunRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public PenyusunApplication $application;
    public ?string $rejectionReason;

    public function __construct(PenyusunApplication $application, ?string $rejectionReason = null)
    {
        $this->afterCommit();
        $this->application = $application;
        $this->rejectionReason = $rejectionReason ?? $application->rejection_reason;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = '[SIPeMo] Pemberitahuan Status Pendaftaran Penyusun Modul';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.penyusun_rejected', [
                'namaPenyusun' => $this->application->nama_penyusun,
                'mataKuliah' => $this->application->mata_kuliah,
                'jurusan' => $this->application->jurusan,
                'rejectionReason' => $this->rejectionReason,
            ]);
    }
}
