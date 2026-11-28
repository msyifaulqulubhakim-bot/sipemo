<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PeriodePenyusunan;
use App\Models\PeriodeHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ArchiveExpiredPeriods extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'periode:archive-expired {--dry-run : Menjalankan simulasi pengarsipan tanpa mengubah database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengarsipkan periode penyusunan aktif yang telah melewati tanggal selesai secara otomatis';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timezone = 'Asia/Jakarta';
        $now = Carbon::now($timezone);
        $today = $now->toDateString();
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("=== Pengarsipan Periode Kedaluwarsa SIPeMo ===");
        $this->line("Waktu Eksekusi: " . $now->format('d/m/Y H:i:s') . " ({$timezone})");

        if ($isDryRun) {
            $this->warn("[DRY-RUN MODE] Tidak ada perubahan yang akan disimpan ke database.");
        }

        // Ambil periode aktif yang memiliki tanggal_selesai dan tanggal_selesai < hari ini
        $expiredPeriods = PeriodePenyusunan::query()
            ->where('status', 'active')
            ->whereNotNull('tanggal_selesai')
            ->whereDate('tanggal_selesai', '<', $today)
            ->get();

        if ($expiredPeriods->isEmpty()) {
            $this->info("Tidak ditemukan periode aktif yang melewati batas tanggal selesai ({$today}).");
            return Command::SUCCESS;
        }

        $this->line("Ditemukan " . $expiredPeriods->count() . " periode yang memenuhi kriteria pengarsipan.");

        $tableData = [];
        $archivedCount = 0;
        $failedCount = 0;

        foreach ($expiredPeriods as $periode) {
            $tanggalSelesaiFormatted = $periode->tanggal_selesai
                ? $periode->tanggal_selesai->format('d/m/Y')
                : '-';

            if ($isDryRun) {
                $tableData[] = [
                    $periode->id,
                    $periode->nama_periode,
                    $periode->status,
                    $tanggalSelesaiFormatted,
                    '[DRY-RUN] Akan diarsipkan',
                ];
                $archivedCount++;
                continue;
            }

            try {
                DB::transaction(function () use ($periode, $now, &$archivedCount) {
                    // Lock record baris untuk mencegah race condition / proses bersamaan
                    $lockedPeriode = PeriodePenyusunan::where('id', $periode->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$lockedPeriode || $lockedPeriode->status !== 'active') {
                        return;
                    }

                    // Ubah status dan catat timestamp pengarsipan
                    $lockedPeriode->status = 'archived';
                    $lockedPeriode->archived_at = $now;
                    $lockedPeriode->save();

                    // Cek apakah riwayat pengarsipan sudah pernah dicatat sebelumnya (mencegah duplikasi riwayat)
                    $historyExists = PeriodeHistory::where('periode_id', $lockedPeriode->id)
                        ->where('aksi', 'archive')
                        ->exists();

                    if (!$historyExists) {
                        PeriodeHistory::create([
                            'periode_id' => $lockedPeriode->id,
                            'user_id' => null, // null menandakan aksi otomatis sistem
                            'aksi' => 'archive',
                            'alasan' => "Pengarsipan otomatis oleh sistem: periode telah melewati batas tanggal selesai ({$lockedPeriode->tanggal_selesai->format('d/m/Y')}).",
                            'created_at' => $now,
                        ]);
                    }

                    $archivedCount++;
                });

                $tableData[] = [
                    $periode->id,
                    $periode->nama_periode,
                    'archived',
                    $tanggalSelesaiFormatted,
                    'Berhasil Diarsipkan',
                ];

                Log::info("SIPeMo Auto-Archive: Periode '{$periode->nama_periode}' (ID: {$periode->id}) berhasil diarsipkan secara otomatis.");
            } catch (\Throwable $e) {
                $failedCount++;
                $tableData[] = [
                    $periode->id,
                    $periode->nama_periode,
                    $periode->status,
                    $tanggalSelesaiFormatted,
                    'Gagal: ' . $e->getMessage(),
                ];

                $this->error("Gagal mengarsipkan periode ID {$periode->id}: " . $e->getMessage());
                Log::error("SIPeMo Auto-Archive Error pada periode ID {$periode->id}: " . $e->getMessage());
            }
        }

        $this->table(
            ['ID', 'Nama Periode', 'Status Akhir', 'Tanggal Selesai', 'Keterangan'],
            $tableData
        );

        if ($isDryRun) {
            $this->info("Simulasi selesai. {$archivedCount} periode siap diarsipkan.");
        } else {
            $this->info("Pengarsipan selesai. Berhasil: {$archivedCount}, Gagal: {$failedCount}.");
        }

        return $failedCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}

