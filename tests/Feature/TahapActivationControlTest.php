<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\MataKuliah;
use App\Models\Modul;
use App\Models\PenyusunApplication;
use App\Models\PeriodeHistory;
use App\Models\PeriodePenyusunan;
use App\Models\TahapPenyusunan;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TahapActivationControlTest extends TestCase
{
    use DatabaseTransactions;

    private function resetActivePeriodeForTestTransaction(): void
    {
        $this->assertGreaterThan(0, DB::transactionLevel());
        DB::table('periode_penyusunans')->where('status', 'active')->update(['status' => 'draft']);
    }

    private function createActivePeriodeWithFourTahaps(): array
    {
        $this->resetActivePeriodeForTestTransaction();

        $periode = PeriodePenyusunan::create([
            'nama_periode' => 'Periode Kendali Tahap Test',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addMonths(3)->toDateString(),
            'status' => 'active',
        ]);

        $tahaps = [];
        for ($i = 1; $i <= 4; $i++) {
            $tahaps[$i] = TahapPenyusunan::create([
                'periode_id' => $periode->id,
                'tahap' => $i,
                'nama_tahap' => "Tahap {$i}",
                'nama_periode' => $periode->nama_periode,
                'tanggal_mulai' => now()->toDateString(),
                'tanggal_selesai' => now()->addMonth()->toDateString(),
                'deskripsi' => "Tahap {$i}",
                'status' => TahapPenyusunan::STATUS_INACTIVE,
            ]);
        }

        return [$periode, $tahaps];
    }

    public function test_tahap_status_transitions_and_audit_history(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        [$periode, $tahaps] = $this->createActivePeriodeWithFourTahaps();

        // 1. Nonaktif Sementara -> Aktif
        $resActivate = $this->actingAs($admin)
            ->post(route('admin.periode.tahap.activate', [$periode, $tahaps[1]]));
        $resActivate->assertRedirect(route('admin.periode.show', $periode));
        $resActivate->assertSessionHas('success');

        $tahaps[1]->refresh();
        $this->assertEquals(TahapPenyusunan::STATUS_ACTIVE, $tahaps[1]->status);
        $this->assertNotNull($tahaps[1]->activated_at);
        $this->assertEquals($admin->id, $tahaps[1]->activated_by);
        // Tahap 2-4 tetap Nonaktif Sementara
        $this->assertEquals(TahapPenyusunan::STATUS_INACTIVE, $tahaps[2]->fresh()->status);
        $this->assertEquals(TahapPenyusunan::STATUS_INACTIVE, $tahaps[3]->fresh()->status);
        $this->assertEquals(TahapPenyusunan::STATUS_INACTIVE, $tahaps[4]->fresh()->status);

        // 2. Aktif -> Nonaktif Sementara
        $resDeactivate = $this->actingAs($admin)
            ->post(route('admin.periode.tahap.deactivate', [$periode, $tahaps[1]]));
        $resDeactivate->assertSessionHas('success');
        $this->assertEquals(TahapPenyusunan::STATUS_INACTIVE, $tahaps[1]->fresh()->status);
        $this->assertNotNull($tahaps[1]->fresh()->deactivated_at);

        // 3. Nonaktif Sementara -> Selesai (tidak ada penyusun approved belum tuntas)
        $resComplete = $this->actingAs($admin)
            ->post(route('admin.periode.tahap.complete', [$periode, $tahaps[1]]));
        $resComplete->assertSessionHas('success');
        $this->assertEquals(TahapPenyusunan::STATUS_COMPLETED, $tahaps[1]->fresh()->status);
        $this->assertNotNull($tahaps[1]->fresh()->completed_at);
        $this->assertEquals($admin->id, $tahaps[1]->fresh()->completed_by);

        // 4. Tahap Selesai tidak dapat diaktifkan atau dinonaktifkan melalui tindakan biasa
        $this->actingAs($admin)
            ->post(route('admin.periode.tahap.activate', [$periode, $tahaps[1]]))
            ->assertSessionHas('error');
        $this->actingAs($admin)
            ->post(route('admin.periode.tahap.deactivate', [$periode, $tahaps[1]]))
            ->assertSessionHas('error');
        $this->assertEquals(TahapPenyusunan::STATUS_COMPLETED, $tahaps[1]->fresh()->status);

        // Pastikan Tahap 2 tidak otomatis aktif setelah Tahap 1 selesai
        $this->assertEquals(TahapPenyusunan::STATUS_INACTIVE, $tahaps[2]->fresh()->status);

        // Verifikasi audit history tercatat
        $this->assertTrue(PeriodeHistory::where('periode_id', $periode->id)->where('aksi', 'tahap_activate')->exists());
        $this->assertTrue(PeriodeHistory::where('periode_id', $periode->id)->where('aksi', 'tahap_deactivate')->exists());
        $this->assertTrue(PeriodeHistory::where('periode_id', $periode->id)->where('aksi', 'tahap_complete')->exists());
    }

    public function test_only_one_tahap_can_be_active_per_periode_backend_and_database(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        [$periode, $tahaps] = $this->createActivePeriodeWithFourTahaps();

        // Aktifkan Tahap 1
        $this->actingAs($admin)
            ->post(route('admin.periode.tahap.activate', [$periode, $tahaps[1]]))
            ->assertSessionHas('success');

        // Coba aktifkan Tahap 2 -> harus ditolak
        $resReject = $this->actingAs($admin)
            ->post(route('admin.periode.tahap.activate', [$periode, $tahaps[2]]));
        $resReject->assertSessionHas('error');
        $this->assertEquals(TahapPenyusunan::STATUS_INACTIVE, $tahaps[2]->fresh()->status);

        // Bypass langsung via query DB -> harus ditolak oleh unique_active_tahap_per_periode
        $dbBlocked = false;
        try {
            DB::table('tahap_penyusunans')
                ->where('id', $tahaps[2]->id)
                ->update(['status' => TahapPenyusunan::STATUS_ACTIVE]);
        } catch (\Throwable $e) {
            $dbBlocked = true;
            $this->assertStringContainsString('unique_active_tahap_per_periode', $e->getMessage());
        }
        $this->assertTrue($dbBlocked, 'Constraint database gagal memblokir dua tahap aktif dalam satu periode.');

        // Nonaktifkan Tahap 1 -> aktifkan Tahap 2 -> harus berhasil
        $this->actingAs($admin)
            ->post(route('admin.periode.tahap.deactivate', [$periode, $tahaps[1]]))
            ->assertSessionHas('success');
        $this->actingAs($admin)
            ->post(route('admin.periode.tahap.activate', [$periode, $tahaps[2]]))
            ->assertSessionHas('success');

        $this->assertEquals(TahapPenyusunan::STATUS_INACTIVE, $tahaps[1]->fresh()->status);
        $this->assertEquals(TahapPenyusunan::STATUS_ACTIVE, $tahaps[2]->fresh()->status);
    }

    public function test_complete_tahap_requires_all_approved_penyusun_moduls_to_be_approved(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        [$periode, $tahaps] = $this->createActivePeriodeWithFourTahaps();

        $jurusan = Jurusan::first() ?? Jurusan::create(['nama_jurusan' => 'S1 Uji', 'kode_jurusan' => 'UJI']);
        $mk = MataKuliah::first() ?? MataKuliah::create([
            'jurusan_id' => $jurusan->id,
            'kode_mata_kuliah' => 'MKUJI01',
            'nama_mata_kuliah' => 'MK Uji Tahap',
            'sks' => 2,
            'semester' => 1,
        ]);

        $app = PenyusunApplication::create([
            'periode_id' => $periode->id,
            'nama_penyusun' => 'Penyusun Uji Kelayakan',
            'email' => 'kelayakan@example.com',
            'judul_bahan_ajar' => 'Buku Uji',
            'jurusan' => $jurusan->nama_jurusan,
            'semester' => '1',
            'mata_kuliah' => $mk->nama_mata_kuliah,
            'mata_kuliah_id' => $mk->id,
            'status' => 'approved',
            'setuju_informasi' => true,
            'setuju_pelaksanaan' => true,
            'setuju_jml_modul' => true,
            'setuju_pembiayaan' => true,
        ]);

        // 1. Belum ada modul diunggah -> ditolak
        $this->actingAs($admin)
            ->post(route('admin.periode.tahap.complete', [$periode, $tahaps[1]]))
            ->assertSessionHas('error');
        $this->assertEquals(TahapPenyusunan::STATUS_INACTIVE, $tahaps[1]->fresh()->status);

        // 2. Modul masih pending -> ditolak
        $modul = Modul::create([
            'penyusun_application_id' => $app->id,
            'mata_kuliah_id' => $mk->id,
            'tahap_id' => $tahaps[1]->id,
            'nomor_modul' => 1,
            'judul_modul' => 'Modul Uji',
            'file_path' => 'moduls/test.docx',
            'file_name' => 'test.docx',
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);
        $this->actingAs($admin)
            ->post(route('admin.periode.tahap.complete', [$periode, $tahaps[1]]))
            ->assertSessionHas('error');

        // 3. Modul approved -> diizinkan Selesai
        $modul->update(['status' => 'approved']);
        $this->actingAs($admin)
            ->post(route('admin.periode.tahap.complete', [$periode, $tahaps[1]]))
            ->assertSessionHas('success');
        $this->assertEquals(TahapPenyusunan::STATUS_COMPLETED, $tahaps[1]->fresh()->status);
    }

    public function test_penyusun_upload_and_delete_are_blocked_when_tahap_inactive_or_completed(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create(['is_admin' => true]);
        $penyusun = User::factory()->create(['is_penyusun' => true, 'email' => 'penyusun-lock@example.com']);
        [$periode, $tahaps] = $this->createActivePeriodeWithFourTahaps();

        $jurusan = Jurusan::first() ?? Jurusan::create(['nama_jurusan' => 'S1 Lock', 'kode_jurusan' => 'LCK']);
        $mk = MataKuliah::create([
            'jurusan_id' => $jurusan->id,
            'kode_mata_kuliah' => 'MKLOCK01',
            'nama_mata_kuliah' => 'MK Uji Penguncian Tahap',
            'sks' => 2,
            'semester' => 1,
        ]);

        $app = PenyusunApplication::create([
            'periode_id' => $periode->id,
            'nama_penyusun' => 'Penyusun Lock',
            'email' => $penyusun->email,
            'judul_bahan_ajar' => 'Buku Lock',
            'jurusan' => $jurusan->nama_jurusan,
            'semester' => '1',
            'mata_kuliah' => $mk->nama_mata_kuliah,
            'mata_kuliah_id' => $mk->id,
            'status' => 'approved',
            'setuju_informasi' => true,
            'setuju_pelaksanaan' => true,
            'setuju_jml_modul' => true,
            'setuju_pembiayaan' => true,
        ]);

        // 1. Tahap Nonaktif Sementara -> create & store ditolak
        $this->actingAs($penyusun)
            ->get(route('penyusun.modul.create', 1))
            ->assertRedirect(route('penyusun.modul.index'))
            ->assertSessionHas('error');

        $this->actingAs($penyusun)
            ->post(route('penyusun.modul.store', 1), [
                'judul_modul' => 'Modul Terlarang',
                'file_modul' => UploadedFile::fake()->create('modul.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            ])
            ->assertRedirect(route('penyusun.modul.index'))
            ->assertSessionHas('error');
        $this->assertEquals(0, Modul::where('penyusun_application_id', $app->id)->count());

        // 2. Aktifkan Tahap 1 -> upload berhasil
        $this->actingAs($admin)->post(route('admin.periode.tahap.activate', [$periode, $tahaps[1]]));

        $this->actingAs($penyusun)
            ->get(route('penyusun.modul.create', 1))
            ->assertOk();

        $this->actingAs($penyusun)
            ->post(route('penyusun.modul.store', 1), [
                'judul_modul' => 'Modul Sah Tahap 1',
                'file_modul' => UploadedFile::fake()->createWithContent('modul1.doc', "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"),
            ])
            ->assertRedirect(route('penyusun.modul.index'))
            ->assertSessionHas('success');

        $modul = Modul::where('penyusun_application_id', $app->id)->where('tahap_id', $tahaps[1]->id)->first();
        $this->assertNotNull($modul);

        // 3. Nonaktifkan sementara Tahap 1 -> dokumen tetap tersimpan, hapus/upload ditolak
        $this->actingAs($admin)->post(route('admin.periode.tahap.deactivate', [$periode, $tahaps[1]]));

        $this->assertDatabaseHas('moduls', ['id' => $modul->id]);
        $this->actingAs($penyusun)
            ->delete(route('penyusun.modul.destroy', $modul))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('moduls', ['id' => $modul->id]);

        // 4. Setujui modul & tandai Selesai -> upload & hapus tetap ditolak
        $modul->update(['status' => 'approved']);
        $this->actingAs($admin)->post(route('admin.periode.tahap.complete', [$periode, $tahaps[1]]))->assertSessionHas('success');

        $this->actingAs($penyusun)
            ->post(route('penyusun.modul.store', 1), [
                'judul_modul' => 'Modul Setelah Selesai',
                'file_modul' => UploadedFile::fake()->create('modul2.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            ])
            ->assertSessionHas('error');
        $this->assertDatabaseHas('moduls', ['id' => $modul->id, 'judul_modul' => 'Modul Sah Tahap 1']);
    }

    public function test_archived_periode_tahap_status_cannot_be_changed(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $archivedPeriode = PeriodePenyusunan::create([
            'nama_periode' => 'Periode Arsip Test',
            'status' => 'archived',
            'archived_at' => now()->subDay(),
        ]);

        $tahap = TahapPenyusunan::create([
            'periode_id' => $archivedPeriode->id,
            'tahap' => 1,
            'nama_tahap' => 'Tahap 1',
            'nama_periode' => $archivedPeriode->nama_periode,
            'tanggal_mulai' => now()->subMonth()->toDateString(),
            'tanggal_selesai' => now()->subWeek()->toDateString(),
            'status' => TahapPenyusunan::STATUS_INACTIVE,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.periode.tahap.activate', [$archivedPeriode, $tahap]))
            ->assertSessionHas('error');
        $this->actingAs($admin)
            ->post(route('admin.periode.tahap.complete', [$archivedPeriode, $tahap]))
            ->assertSessionHas('error');

        $this->assertEquals(TahapPenyusunan::STATUS_INACTIVE, $tahap->fresh()->status);
    }
}
