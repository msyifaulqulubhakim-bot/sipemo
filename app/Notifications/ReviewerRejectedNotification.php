<?php

namespace App\Notifications;

use App\Models\ReviewerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewerRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public ReviewerApplication $application;
    public ?string $rejectionReason;

    public function __construct(ReviewerApplication $application, ?string $rejectionReason = null)
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
        $subject = '[SIPeMo] Pemberitahuan Status Pendaftaran Reviewer';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.reviewer_rejected', [
                'namaReviewer' => $this->application->nama_reviewer,
                'rejectionReason' => $this->rejectionReason,
            ]);
    }
}
