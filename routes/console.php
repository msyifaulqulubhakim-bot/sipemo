<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal otomatis: arsipkan periode penyusunan yang telah melewati tanggal selesai setiap hari pukul 00:05 WIB
Schedule::command('periode:archive-expired')
    ->dailyAt('00:05')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->description('Mengarsipkan periode penyusunan aktif yang telah melewati batas tanggal selesai secara otomatis');
