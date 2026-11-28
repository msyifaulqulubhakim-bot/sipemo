<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\PeriodePenyusunan;
use App\Models\PeriodeHistory;
use App\Models\TahapPenyusunan;
use App\Models\PenyusunApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class PeriodeDatabaseFoundationTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Uji 1: Verifikasi keberadaan tabel periode_penyusunans dan kolom-kolomnya
     */
    public function test_database_schema_has_periode_penyusunans_table_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('periode_penyusunans'), 'Tabel periode_penyusunans tidak ditemukan di database.');

        $expectedColumns = [
            'id',
            'nama_periode',
            'tanggal_mulai',
            'tanggal_selesai',
            'deskripsi',
            'status',
            'archived_at',
            'active_guard',
            'created_at',
            'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('periode_penyusunans', $column),
                "Kolom '{$column}' tidak ditemukan pada tabel periode_penyusunans."
            );
        }
    }

    /**
     * Uji 2: Verifikasi keberadaan tabel periode_histories dan kolom-kolomnya
     */
    public function test_database_schema_has_periode_histories_table_with_expected_columns(): void
    {
        $this->assertTrue(Schema::hasTable('periode_histories'), 'Tabel periode_histories tidak ditemukan di database.');

        $expectedColumns = [
            'id',
            'periode_id',
            'user_id',
            'aksi',
            'alasan',
            'created_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertTrue(
                Schema::hasColumn('periode_histories', $column),
                "Kolom '{$column}' tidak ditemukan pada tabel periode_histories."
            );
        }
    }

    /**
     * Uji 3: Verifikasi penambahan foreign key periode_id pada tahap_penyusunans
     */
    public function test_tahap_penyusunans_table_has_periode_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('tahap_penyusunans', 'periode_id'),
            "Kolom 'periode_id' belum ditambahkan ke tabel tahap_penyusunans."
        );
    }

    /**
     * Uji 4: Verifikasi penambahan foreign key periode_id pada penyusun_applications
     */
    public function test_penyusun_applications_table_has_periode_id_column(): void
    {
        $this->assertTrue(
            Schema::hasColumn('penyusun_applications', 'periode_id'),
            "Kolom 'periode_id' belum ditambahkan ke tabel penyusun_applications."
        );
    }

    /**
     * Isolasi kondisi awal di dalam transaksi DatabaseTransactions agar periode aktif
     * yang sudah ada di database utama tidak memblokir fixture test, dan tetap aman
     * karena seluruh perubahan otomatis di-rollback setelah tiap test selesai.
     */
    private function resetActivePeriodeForTestTransaction(): void
    {
        $this->assertGreaterThan(
            0,
            DB::transactionLevel(),
            'Isolasi test harus berjalan di dalam transaksi DatabaseTransactions.'
        );

        DB::table('periode_penyusunans')
            ->where('status', 'active')
            ->update(['status' => 'draft']);
    }

    /**
     * Uji 5: Verifikasi pembuatan model PeriodePenyusunan dan method status
     */
    public function test_can_create_periode_penyusunan_model_and_query_attributes(): void
    {
        $this->resetActivePeriodeForTestTransaction();

        $periode = PeriodePenyusunan::create([
            'nama_periode' => 'Periode Semester Ganjil 2026/2027',
            'tanggal_mulai' => '2026-09-01',
            'tanggal_selesai' => '2027-01-31',
            'deskripsi' => 'Periode penyusunan modul tahun akademik 2026/2027',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('periode_penyusunans', [
            'id' => $periode->id,
            'nama_periode' => 'Periode Semester Ganjil 2026/2027',
            'status' => 'active',
        ]);

        $this->assertTrue($periode->isActive());
        $this->assertFalse($periode->isArchived());

        // Uji singleton-like accessor getActive()
        $activePeriode = PeriodePenyusunan::getActive();
        $this->assertNotNull($activePeriode);
        $this->assertEquals($periode->id, $activePeriode->id);
    }

    /**
     * Uji 6: Penegakan aturan HANYA SATU periode aktif (Model-level dan Database-level)
     */
    public function test_database_and_model_strictly_enforce_at_most_one_active_periode(): void
    {
        $this->resetActivePeriodeForTestTransaction();

        // Buat periode aktif pertama
        $periode1 = PeriodePenyusunan::create([
            'nama_periode' => 'Periode Aktif Pertama',
            'status' => 'active',
        ]);

        // Coba buat periode aktif kedua via Eloquent -> harus ditolak oleh model guard
        $modelBlocked = false;
        try {
            PeriodePenyusunan::create([
                'nama_periode' => 'Periode Aktif Kedua',
                'status' => 'active',
            ]);
        } catch (\InvalidArgumentException $e) {
            $modelBlocked = true;
        }
        $this->assertTrue($modelBlocked, 'Model PeriodePenyusunan gagal memblokir pembuatan dua periode aktif.');

        // Coba bypass via direct DB insert -> harus ditolak oleh MySQL unique virtual column constraint
        $dbBlocked = false;
        try {
            DB::table('periode_penyusunans')->insert([
                'nama_periode' => 'Periode Aktif Bypass DB',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $dbBlocked = true;
            $this->assertStringContainsString('unique_active_periode', $e->getMessage());
        }
        $this->assertTrue($dbBlocked, 'Database MySQL gagal menegakkan unique constraint pada active_guard.');
    }

    /**
     * Uji 7: Multiple periode berstatus archived diperbolehkan
     */
    public function test_multiple_archived_periodes_are_allowed(): void
    {
        $archived1 = PeriodePenyusunan::create([
            'nama_periode' => 'Periode Arsip 2024/2025',
            'status' => 'archived',
            'archived_at' => now()->subYear(),
        ]);

        $archived2 = PeriodePenyusunan::create([
            'nama_periode' => 'Periode Arsip 2025/2026',
            'status' => 'archived',
            'archived_at' => now()->subMonths(6),
        ]);

        $this->assertTrue($archived1->isArchived());
        $this->assertTrue($archived2->isArchived());

        $this->assertDatabaseHas('periode_penyusunans', ['id' => $archived1->id, 'status' => 'archived']);
        $this->assertDatabaseHas('periode_penyusunans', ['id' => $archived2->id, 'status' => 'archived']);
    }

    /**
     * Uji 8: Verifikasi relasi PeriodePenyusunan dengan 4 Tahap, Aplikasi Penyusun, dan History
     */
    public function test_periode_penyusunan_relationships(): void
    {
        $this->resetActivePeriodeForTestTransaction();

        $admin = User::where('is_admin', true)->first() ?? User::factory()->create(['is_admin' => true]);

        $periode = PeriodePenyusunan::create([
            'nama_periode' => 'Periode Relasi Test 2026',
            'status' => 'active',
        ]);

        // Buat tepat 4 tahap untuk periode ini
        for ($i = 1; $i <= 4; $i++) {
            TahapPenyusunan::create([
                'periode_id' => $periode->id,
                'tahap' => $i,
                'nama_tahap' => "Tahap {$i}",
                'nama_periode' => $periode->nama_periode,
                'tanggal_mulai' => now()->addDays(($i - 1) * 7)->toDateString(),
                'tanggal_selesai' => now()->addDays($i * 7)->toDateString(),
                'deskripsi' => "Deskripsi Tahap {$i}",
            ]);
        }

        // Verifikasi relasi tahapPenyusunans
        $this->assertCount(4, $periode->tahapPenyusunans);
        $this->assertEquals([1, 2, 3, 4], $periode->tahapPenyusunans->pluck('tahap')->toArray());
        $this->assertEquals($periode->id, $periode->tahapPenyusunans->first()->periode->id);

        // Buat riwayat aksi (PeriodeHistory)
        $history = PeriodeHistory::create([
            'periode_id' => $periode->id,
            'user_id' => $admin->id,
            'aksi' => 'create',
            'alasan' => 'Inisialisasi periode baru',
        ]);

        $this->assertCount(1, $periode->histories);
        $this->assertEquals('create', $periode->histories->first()->aksi);
        $this->assertEquals($periode->id, $history->periode->id);
        $this->assertEquals($admin->id, $history->user->id);

        // Hubungkan aplikasi penyusun ke periode ini
        $application = PenyusunApplication::first();
        if ($application) {
            $application->update(['periode_id' => $periode->id]);

            $this->assertCount(1, $periode->penyusunApplications);
            $this->assertEquals($periode->id, $application->fresh()->periode->id);
            $this->assertTrue(PenyusunApplication::inActivePeriod()->where('id', $application->id)->exists());
        }
    }

    /**
     * Uji 9: Proteksi integritas penghapusan (cegah delete periode jika memiliki data)
     */
    public function test_deleting_periode_with_related_data_is_prevented(): void
    {
        $this->resetActivePeriodeForTestTransaction();

        $periode = PeriodePenyusunan::create([
            'nama_periode' => 'Periode Terproteksi',
            'status' => 'active',
        ]);

        TahapPenyusunan::create([
            'periode_id' => $periode->id,
            'tahap' => 1,
            'nama_tahap' => 'Tahap 1',
            'nama_periode' => $periode->nama_periode,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addWeek()->toDateString(),
        ]);

        $deleteBlocked = false;
        try {
            $periode->delete();
        } catch (\DomainException $e) {
            $deleteBlocked = true;
        }

        $this->assertTrue($deleteBlocked, 'Periode berhasil dihapus padahal masih memiliki tahap penyusunan terkait.');
        $this->assertDatabaseHas('periode_penyusunans', ['id' => $periode->id]);
    }

    /**
     * Uji 10: Verifikasi bahwa aplikasi penyusun lama (tanpa periode_id) tetap tidak terdampak
     *
     * Catatan: Uji ini berjalan pada database pengujian sipemo_test yang terpisah dan tidak
     * berisi data produksi. Oleh karena itu, integritas diuji dengan membuat fixture sendiri
     * yang mensimulasikan kondisi data lama (periode_id = NULL).
     */
    public function test_existing_penyusun_application_data_remains_intact(): void
    {
        // Buat user agar relasi user() (by email) dapat ditemukan
        $user = User::factory()->create(['email' => 'lama-legacy@example.com']);

        // Buat aplikasi lama tanpa periode_id menggunakan DB::table
        // agar kita bebas dari validasi model dan mencerminkan kondisi data pra-fitur-periode.
        // Field wajib non-nullable: nama_penyusun, email, judul_bahan_ajar, jurusan, semester, mata_kuliah
        $id = DB::table('penyusun_applications')->insertGetId([
            'nama_penyusun'      => 'Penyusun Lama (Fixture)',
            'email'              => 'lama-legacy@example.com',
            'judul_bahan_ajar'   => 'Bahan Ajar Lama',
            'jurusan'            => 'Teknik Informatika',
            'semester'           => '5',
            'mata_kuliah'        => 'Pemrograman Web',
            'status'             => 'approved',
            'periode_id'         => null,   // <-- inti: data lama tanpa periode
            'setuju_informasi'   => true,
            'setuju_pelaksanaan' => true,
            'setuju_jml_modul'   => true,
            'setuju_pembiayaan'  => true,
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $application = PenyusunApplication::find($id);
        $this->assertNotNull($application, 'Fixture aplikasi penyusun lama gagal dibuat.');

        // Inti: periode_id harus tetap NULL — tidak dihubungkan ke periode manapun
        $this->assertNull(
            $application->fresh()->periode_id,
            'Aplikasi penyusun lama seharusnya tetap memiliki periode_id = NULL.'
        );

        // Relasi user() menggunakan email — harus bisa ditemukan
        $this->assertNotNull($application->user, 'Relasi user (by email) pada aplikasi lama tidak berfungsi.');
        $this->assertEquals($user->email, $application->user->email);

        // Relasi periode harus NULL karena periode_id = NULL
        $this->assertNull(
            $application->fresh()->periode,
            'Relasi periode pada aplikasi lama seharusnya NULL.'
        );

        // Aplikasi lama tidak boleh muncul dalam scope inActivePeriod
        $this->assertFalse(
            PenyusunApplication::inActivePeriod()->where('id', $application->id)->exists(),
            'Aplikasi penyusun lama (periode_id = NULL) seharusnya tidak muncul dalam scope inActivePeriod.'
        );
    }

    /**
     * Uji 11: Verifikasi halaman GET /admin/periode/{periode} dapat memuat relasi tahap dan moduls (foreign key tahap_id)
     */
    public function test_admin_periode_show_loads_tahap_and_moduls_relation_correctly(): void
    {
        $admin = User::where('is_admin', true)->first() ?? User::factory()->create(['is_admin' => true]);

        $periode = PeriodePenyusunan::getActive() ?? PeriodePenyusunan::create([
            'nama_periode' => 'Periode Show Test',
            'status' => 'active',
        ]);

        // Pastikan relasi tahapPenyusunans.moduls menggunakan tahap_id tanpa SQL error
        $periode->load('tahapPenyusunans.moduls');
        $this->assertNotNull($periode->tahapPenyusunans);

        $response = $this->actingAs($admin)->get(route('admin.periode.show', $periode));
        $response->assertOk();
    }
}

