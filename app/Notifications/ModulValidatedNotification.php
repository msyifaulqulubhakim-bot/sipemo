<?php

namespace App\Notifications;

use App\Models\Modul;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ModulValidatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Modul $modul;
    public string $status;
    public ?string $catatanAdmin;

    public function __construct(Modul $modul, string $status, ?string $catatanAdmin = null)
    {
        $this->afterCommit();
        $this->modul = $modul;
        $this->status = $status;
        $this->catatanAdmin = $catatanAdmin ?? $modul->catatan_admin;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tahapNomor = $this->modul->tahapPenyusunan?->tahap ?? $this->modul->tahap_id ?? 1;
        $namaTahap = $this->modul->tahapPenyusunan?->nama_tahap ?? '';
        $mataKuliah = $this->modul->penyusunApplication?->mata_kuliah ?? 'Mata Kuliah Modul';
        $namaPenyusun = $this->modul->penyusunApplication?->nama_penyusun ?? 'Penyusun';

        $subject = $this->status === 'approved'
            ? "[SIPeMo] Modul Pembelajaran Tahap {$tahapNomor} Disetujui"
            : "[SIPeMo] Catatan Revisi Modul Tahap {$tahapNomor}";

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.modul_validated', [
                'namaPenyusun' => $namaPenyusun,
                'mataKuliah' => $mataKuliah,
                'tahap' => $tahapNomor,
                'namaTahap' => $namaTahap,
                'status' => $this->status,
                'catatanAdmin' => $this->catatanAdmin,
                'modulUrl' => url('/penyusun/modul'),
            ]);
    }
}
