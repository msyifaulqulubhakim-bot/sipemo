<?php

namespace App\Notifications;

use App\Models\ReviewerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewerApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public ReviewerApplication $application;
    public array $mataKuliahList;

    public function __construct(ReviewerApplication $application, array $mataKuliahList = [])
    {
        $this->afterCommit();
        $this->application = $application;
        $this->mataKuliahList = $mataKuliahList;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = '[SIPeMo] Pendaftaran Reviewer Disetujui & Penugasan Mata Kuliah';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.reviewer_approved', [
                'namaReviewer' => $this->application->nama_reviewer,
                'mataKuliahList' => $this->mataKuliahList,
                'loginUrl' => url('/login'),
            ]);
    }
}
