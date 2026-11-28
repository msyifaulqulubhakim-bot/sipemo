<?php

namespace App\Services\Kampus;

use App\Models\Fakultas;
use App\Models\Jurusan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class KampusSyncService
{
    protected KampusClient $client;

    public function __construct(KampusClient $client)
    {
        $this->client = $client;
    }

    /**
     * Sinkronisasi data master fakultas dari API kampus ke tabel `fakultas`.
     */
    public function syncFakultas(): array
    {
        $endpoint = config('kampus.endpoints.fakultas', '/api/fakultas');
        $response = $this->client->getWithStaticToken($endpoint);
        $data = isset($response['data']) && is_array($response['data']) ? $response['data'] : $response;

        $inserted = 0;
        $updated = 0;

        DB::transaction(function () use ($data, &$inserted, &$updated) {
            foreach ($data as $item) {
                if (! isset($item['kode_fakultas'])) {
                    continue;
                }

                $record = Fakultas::updateOrCreate(
                    ['kode_fakultas' => (int) $item['kode_fakultas']],
                    [
                        'nama_fakultas' => $item['nama_fakultas'] ?? 'Fakultas ' . $item['kode_fakultas'],
                        'nama_singkat' => $item['nama_fakultas_singkat'] ?? null,
                    ]
                );

                if ($record->wasRecentlyCreated) {
                    $inserted++;
                } else {
                    $updated++;
                }
            }
        });

        Log::info("Sinkronisasi Fakultas selesai: {$inserted} baru, {$updated} diperbarui.");

        return [
            'total' => count($data),
            'inserted' => $inserted,
            'updated' => $updated,
        ];
    }

    /**
     * Sinkronisasi data master program studi dari API kampus ke tabel `jurusans`.
     * Menjaga relasi foreign key eksisting ke `mata_kuliahs`.
     */
    public function syncProgramStudi(): array
    {
        $endpoint = config('kampus.endpoints.program_studi', '/api/program_studi');
        $response = $this->client->getWithStaticToken($endpoint);
        $data = isset($response['data']) && is_array($response['data']) ? $response['data'] : $response;

        // Ambil peta mapping kode_fakultas => id tabel fakultas
        $fakultasMap = Fakultas::pluck('id', 'kode_fakultas')->toArray();

        $inserted = 0;
        $updated = 0;

        DB::transaction(function () use ($data, $fakultasMap, &$inserted, &$updated) {
            foreach ($data as $item) {
                $kodeProdi = isset($item['kode_prodi']) ? (string) $item['kode_prodi'] : null;
                $idProdi = isset($item['id_prodi']) ? (int) $item['id_prodi'] : null;

                if (! $kodeProdi && ! $idProdi) {
                    continue;
                }

                $fakultasKode = $item['fakultas_kode'] ?? null;
                $fakultasId = $fakultasKode ? ($fakultasMap[$fakultasKode] ?? null) : null;

                // Cari berdasarkan kode_jurusan terlebih dahulu agar record eksisting tetap utuh
                $jurusan = Jurusan::where('kode_jurusan', $kodeProdi)
                    ->orWhere(function ($query) use ($idProdi) {
                        if ($idProdi) {
                            $query->where('id_prodi_kampus', $idProdi);
                        }
                    })->first();

                if ($jurusan) {
                    $jurusan->update([
                        'id_prodi_kampus' => $idProdi,
                        'nama_jurusan' => $item['nama_prodi'] ?? $jurusan->nama_jurusan,
                        'fakultas_id' => $fakultasId ?? $jurusan->fakultas_id,
                        'jenjang' => $item['jenjang'] ?? $jurusan->jenjang,
                        'deskripsi' => $item['gelar_akademik_full'] ?? $jurusan->deskripsi,
                    ]);
                    $updated++;
                } else {
                    Jurusan::create([
                        'id_prodi_kampus' => $idProdi,
                        'kode_jurusan' => $kodeProdi ?? (string) $idProdi,
                        'nama_jurusan' => $item['nama_prodi'] ?? 'Prodi ' . $kodeProdi,
                        'fakultas_id' => $fakultasId,
                        'jenjang' => $item['jenjang'] ?? null,
                        'deskripsi' => $item['gelar_akademik_full'] ?? null,
                    ]);
                    $inserted++;
                }
            }
        });

        Log::info("Sinkronisasi Program Studi selesai: {$inserted} baru, {$updated} diperbarui.");

        return [
            'total' => count($data),
            'inserted' => $inserted,
            'updated' => $updated,
        ];
    }

    /**
     * Jalankan sinkronisasi lengkap Fakultas dan Program Studi.
     */
    public function syncAll(): array
    {
        $fakultasResult = $this->syncFakultas();
        $prodiResult = $this->syncProgramStudi();

        return [
            'fakultas' => $fakultasResult,
            'prodi' => $prodiResult,
        ];
    }
}
