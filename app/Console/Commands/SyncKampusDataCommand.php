<?php

namespace App\Console\Commands;

use App\Services\Kampus\KampusSyncService;
use Illuminate\Console\Command;

class SyncKampusDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kampus:sync {--only= : fakultas atau prodi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi data master Fakultas dan Program Studi dari API Kampus ke database SIPeMo';

    /**
     * Execute the console command.
     */
    public function handle(KampusSyncService $syncService): int
    {
        $only = $this->option('only');

        $this->info('Memulai sinkronisasi data master dari Bridge API kampus...');

        try {
            if ($only === 'fakultas') {
                $this->info('Sinkronisasi data Fakultas...');
                $res = $syncService->syncFakultas();
                $this->table(['Total Diterima', 'Baru', 'Diperbarui'], [[$res['total'], $res['inserted'], $res['updated']]]);
                return self::SUCCESS;
            }

            if ($only === 'prodi') {
                $this->info('Sinkronisasi data Program Studi...');
                $res = $syncService->syncProgramStudi();
                $this->table(['Total Diterima', 'Baru', 'Diperbarui'], [[$res['total'], $res['inserted'], $res['updated']]]);
                return self::SUCCESS;
            }

            $this->info('1/2. Sinkronisasi data Fakultas...');
            $resF = $syncService->syncFakultas();
            $this->table(['Fakultas Total', 'Baru', 'Diperbarui'], [[$resF['total'], $resF['inserted'], $resF['updated']]]);

            $this->info('2/2. Sinkronisasi data Program Studi...');
            $resP = $syncService->syncProgramStudi();
            $this->table(['Prodi Total', 'Baru', 'Diperbarui'], [[$resP['total'], $resP['inserted'], $resP['updated']]]);

            $this->info('Sinkronisasi master data akademik kampus berhasil diselesaikan!');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Gagal melakukan sinkronisasi data: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
