<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PeriodePenyusunan;
use App\Models\PeriodeHistory;
use App\Models\TahapPenyusunan;
use App\Models\PenyusunApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PeriodePenyusunanController extends Controller
{
    /**
     * Menampilkan daftar seluruh periode penyusunan (draft, active, archived)
     */
    public function index(Request $request)
    {
        $query = PeriodePenyusunan::query()
            ->withCount(['tahapPenyusunans', 'penyusunApplications', 'histories']);

        // Filter pencarian nama periode
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nama_periode', 'like', "%{$search}%");
        }

        // Filter berdasarkan status
        if ($request->filled('status') && in_array($request->input('status'), ['draft', 'active', 'archived'])) {
            $query->where('status', $request->input('status'));
        }

        // Urutkan: active paling atas, lalu draft, lalu archived (terbaru berdasarkan tanggal)
        $periodes = $query->orderByRaw("CASE 
                WHEN status = 'active' THEN 1 
                WHEN status = 'draft' THEN 2 
                ELSE 3 
            END")
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        // Statistik ringkasan
        $stats = [
            'total' => PeriodePenyusunan::count(),
            'active' => PeriodePenyusunan::active()->count(),
            'draft' => PeriodePenyusunan::draft()->count(),
            'archived' => PeriodePenyusunan::archived()->count(),
        ];

        $activePeriode = PeriodePenyusunan::active()->first();

        return view('admin.periode.index', compact('periodes', 'stats', 'activePeriode'));
    }

    /**
     * Menampilkan form pembuatan periode baru
     */
    public function create()
    {
        $activePeriode = PeriodePenyusunan::active()->first();
        $hasActivePeriode = $activePeriode !== null;

        return view('admin.periode.create', compact('hasActivePeriode', 'activePeriode'));
    }

    /**
     * Menyimpan periode penyusunan baru dan secara otomatis membuat 4 tahap penyusunan
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_periode' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status' => 'required|in:draft,active',
            'deskripsi' => 'nullable|string|max:1000',
        ], [
            'nama_periode.required' => 'Nama periode wajib diisi.',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'status.required' => 'Status periode wajib dipilih.',
            'status.in' => 'Status periode harus berupa draft atau active.',
        ]);

        $today = Carbon::now('Asia/Jakarta')->toDateString();

        // Validasi aturan bisnis jika status dipilih active
        if ($request->status === 'active') {
            // Cegah jika tanggal_selesai sudah lampau
            if (Carbon::parse($request->tanggal_selesai)->toDateString() < $today) {
                return back()->withInput()->withErrors([
                    'tanggal_selesai' => 'Periode dengan tanggal selesai yang sudah terlewati tidak dapat diaktifkan.',
                ]);
            }

            // Cegah jika sudah ada periode aktif lain
            $existingActive = PeriodePenyusunan::active()->first();
            if ($existingActive) {
                return back()->withInput()->withErrors([
                    'status' => "Sudah ada periode yang sedang aktif ({$existingActive->nama_periode}). Hanya satu periode yang boleh aktif dalam satu waktu. Pilih status Draft atau nonaktifkan/arsipkan periode aktif saat ini terlebih dahulu.",
                ]);
            }
        }

        DB::transaction(function () use ($request) {
            // 1. Buat record periode penyusunan
            $periode = PeriodePenyusunan::create([
                'nama_periode' => $request->nama_periode,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_selesai' => $request->tanggal_selesai,
                'deskripsi' => $request->deskripsi,
                'status' => $request->status,
            ]);

            // 2. Buat otomatis tepat 4 tahap penyusunan untuk periode ini
            $descriptions = [
                1 => 'Penyusunan modul 1-2',
                2 => 'Penyusunan modul 3-4',
                3 => 'Penyusunan modul 5',
                4 => 'Penyusunan modul 6',
            ];

            $startDate = Carbon::parse($request->tanggal_mulai);
            $endDate = Carbon::parse($request->tanggal_selesai);
            $totalDays = max(1, $startDate->diffInDays($endDate));
            $daysPerTahap = max(1, floor($totalDays / 4));

            for ($i = 1; $i <= 4; $i++) {
                $tahapMulai = Carbon::parse($request->tanggal_mulai)->addDays(($i - 1) * $daysPerTahap)->toDateString();
                $tahapSelesai = ($i === 4)
                    ? $request->tanggal_selesai
                    : Carbon::parse($request->tanggal_mulai)->addDays($i * $daysPerTahap - 1)->toDateString();

                // Pastikan tahapSelesai tidak melebihi tanggal selesai periode
                if (Carbon::parse($tahapSelesai)->gt($endDate)) {
                    $tahapSelesai = $request->tanggal_selesai;
                }

                TahapPenyusunan::create([
                    'periode_id' => $periode->id,
                    'tahap' => $i,
                    'nama_tahap' => "Tahap {$i}",
                    'nama_periode' => $periode->nama_periode,
                    'tanggal_mulai' => $tahapMulai,
                    'tanggal_selesai' => $tahapSelesai,
                    'deskripsi' => $descriptions[$i],
                    'status' => TahapPenyusunan::STATUS_INACTIVE,
                ]);
            }

            // 3. Catat riwayat audit pembuatan periode
            PeriodeHistory::create([
                'periode_id' => $periode->id,
                'user_id' => Auth::id(),
                'aksi' => 'create',
                'alasan' => 'Pembuatan periode baru oleh Admin dengan status: ' . ucfirst($periode->status),
                'created_at' => now(),
            ]);
        });

        return redirect()->route('admin.periode.index')
            ->with('success', "Periode penyusunan '{$request->nama_periode}' berhasil dibuat dan disimpan.");
    }

    /**
     * Menampilkan form edit periode penyusunan
     */
    public function edit(PeriodePenyusunan $periode)
    {
        if ($periode->isArchived()) {
            return redirect()->route('admin.periode.show', $periode)
                ->with('error', 'Periode yang sudah diarsipkan bersifat read-only dan tidak dapat diedit.');
        }

        $periode->load('tahapPenyusunans');
        $hasOtherActivePeriode = PeriodePenyusunan::active()->where('id', '!=', $periode->id)->exists();

        return view('admin.periode.edit', compact('periode', 'hasOtherActivePeriode'));
    }

    /**
     * Memperbarui data periode penyusunan dan tahapan terkait
     */
    public function update(Request $request, PeriodePenyusunan $periode)
    {
        if ($periode->isArchived()) {
            return redirect()->route('admin.periode.show', $periode)
                ->with('error', 'Periode yang sudah diarsipkan bersifat read-only dan tidak dapat diubah.');
        }

        $request->validate([
            'nama_periode' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'deskripsi' => 'nullable|string|max:1000',
            'status' => 'required|in:draft,active',
        ]);

        $today = Carbon::now('Asia/Jakarta')->toDateString();

        if ($request->status === 'active') {
            if (Carbon::parse($request->tanggal_selesai)->toDateString() < $today) {
                return back()->withInput()->withErrors([
                    'tanggal_selesai' => 'Periode dengan tanggal selesai yang sudah terlewati tidak dapat diaktifkan.',
                ]);
            }

            $hasOtherActive = PeriodePenyusunan::active()->where('id', '!=', $periode->id)->exists();
            if ($hasOtherActive) {
                return back()->withInput()->withErrors([
                    'status' => 'Sudah ada periode lain yang sedang aktif. Hanya satu periode yang boleh aktif dalam satu waktu.',
                ]);
            }
        }

        DB::transaction(function () use ($request, $periode) {
            $oldStatus = $periode->status;

            $periode->update([
                'nama_periode' => $request->nama_periode,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_selesai' => $request->tanggal_selesai,
                'deskripsi' => $request->deskripsi,
                'status' => $request->status,
            ]);

            // Sinkronkan nama_periode pada 4 tahap penyusunan
            TahapPenyusunan::where('periode_id', $periode->id)->update([
                'nama_periode' => $periode->nama_periode,
            ]);

            // Catat riwayat audit
            $alasan = 'Pembaruan data periode oleh Admin';
            if ($oldStatus !== $periode->status) {
                $alasan .= " (Status berubah dari {$oldStatus} menjadi {$periode->status})";
            }

            PeriodeHistory::create([
                'periode_id' => $periode->id,
                'user_id' => Auth::id(),
                'aksi' => ($oldStatus !== 'active' && $periode->status === 'active') ? 'activate' : 'update',
                'alasan' => $alasan,
                'created_at' => now(),
            ]);
        });

        return redirect()->route('admin.periode.show', $periode)
            ->with('success', 'Data periode penyusunan berhasil diperbarui.');
    }

    /**
     * Mengaktifkan periode penyusunan yang berstatus draft
     */
    public function activate(PeriodePenyusunan $periode)
    {
        if ($periode->isArchived()) {
            return back()->with('error', 'Periode yang sudah diarsipkan tidak dapat diaktifkan kembali.');
        }

        if ($periode->isActive()) {
            return back()->with('info', 'Periode ini sudah dalam status aktif.');
        }

        if ($periode->isExpired()) {
            return back()->with('error', 'Periode dengan tanggal selesai yang sudah terlewati tidak dapat diaktifkan.');
        }

        $existingActive = PeriodePenyusunan::active()->first();
        if ($existingActive) {
            return back()->with('error', "Gagal mengaktifkan: Sudah ada periode aktif ({$existingActive->nama_periode}). Hanya satu periode yang boleh aktif dalam satu waktu.");
        }

        DB::transaction(function () use ($periode) {
            $periode->update([
                'status' => 'active',
            ]);

            PeriodeHistory::create([
                'periode_id' => $periode->id,
                'user_id' => Auth::id(),
                'aksi' => 'activate',
                'alasan' => 'Aktivasi periode penyusunan oleh Admin',
                'created_at' => now(),
            ]);
        });

        return back()->with('success', "Periode '{$periode->nama_periode}' berhasil diaktifkan.");
    }

    /**
     * Menampilkan daftar khusus periode yang berstatus archived (Arsip Periode)
     */
    public function arsip(Request $request)
    {
        $query = PeriodePenyusunan::archived()
            ->withCount(['tahapPenyusunans', 'penyusunApplications']);

        // Filter pencarian
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('nama_periode', 'like', "%{$search}%");
        }

        $periodes = $query->orderByDesc('archived_at')
            ->orderByDesc('tanggal_selesai')
            ->paginate(10)
            ->withQueryString();

        // Statistik arsip
        $stats = [
            'total_arsip' => PeriodePenyusunan::archived()->count(),
            'total_penyusun_terarsip' => PenyusunApplication::whereHas('periode', function ($q) {
                $q->where('status', 'archived');
            })->count(),
        ];

        return view('admin.periode.arsip', compact('periodes', 'stats'));
    }

    /**
     * Menampilkan detail lengkap periode penyusunan, data penyusun, progres, dan riwayat aktivitas
     */
    public function show(PeriodePenyusunan $periode)
    {
        // Eager load relasi yang diperlukan untuk detail periode
        $periode->load([
            'tahapPenyusunans.moduls',
            'tahapPenyusunans.activator',
            'tahapPenyusunans.completer',
            'histories.user',
            'penyusunApplications' => function ($q) {
                $q->with([
                    'mataKuliah.jurusan',
                    'moduls.tahapPenyusunan',
                    'finalDrafts',
                    'publicationModuls',
                ])->orderBy('created_at', 'desc');
            },
        ]);

        // Ringkasan progres dokumen dalam periode ini
        $applications = $periode->penyusunApplications;
        $totalPenyusun = $applications->count();
        $totalApprovedPenyusun = $applications->where('status', 'approved')->count();

        // Hitung total modul, final draft, dan publikasi yang terkait periode ini
        $totalModuls = 0;
        $totalFinalDrafts = 0;
        $totalPublications = 0;

        foreach ($applications as $app) {
            $totalModuls += $app->moduls->where('status', 'approved')->count();
            $totalFinalDrafts += $app->finalDrafts->where('status', 'approved')->count();
            $totalPublications += $app->publicationModuls->where('status', 'approved')->count();
        }

        $stats = [
            'total_penyusun' => $totalPenyusun,
            'approved_penyusun' => $totalApprovedPenyusun,
            'total_moduls' => $totalModuls,
            'total_final_drafts' => $totalFinalDrafts,
            'total_publications' => $totalPublications,
        ];

        return view('admin.periode.show', compact('periode', 'stats'));
    }

    /**
     * Mengaktifkan satu tahap pada periode aktif (menjamin maksimal 1 tahap aktif secara atomik).
     */
    public function activateTahap(Request $request, PeriodePenyusunan $periode, TahapPenyusunan $tahap)
    {
        if ((int) $tahap->periode_id !== (int) $periode->id) {
            abort(404);
        }

        $result = DB::transaction(function () use ($request, $periode, $tahap) {
            $lockedPeriode = PeriodePenyusunan::where('id', $periode->id)->lockForUpdate()->firstOrFail();

            if ($lockedPeriode->isArchived()) {
                return ['ok' => false, 'message' => 'Periode arsip bersifat read-only dan status tahapnya tidak dapat diubah.'];
            }

            if (!$lockedPeriode->isActive()) {
                return ['ok' => false, 'message' => 'Tahap hanya dapat diaktifkan pada periode yang berstatus aktif.'];
            }

            $lockedTahap = TahapPenyusunan::where('id', $tahap->id)->lockForUpdate()->firstOrFail();

            if ($lockedTahap->isCompletedStatus()) {
                return ['ok' => false, 'message' => "{$lockedTahap->nama_tahap} sudah berstatus Selesai dan tidak dapat diaktifkan kembali melalui tindakan biasa."];
            }

            if ($lockedTahap->isActiveStatus()) {
                return ['ok' => false, 'message' => "{$lockedTahap->nama_tahap} sudah berstatus Aktif."];
            }

            $otherActive = TahapPenyusunan::where('periode_id', $lockedPeriode->id)
                ->where('status', TahapPenyusunan::STATUS_ACTIVE)
                ->where('id', '!=', $lockedTahap->id)
                ->lockForUpdate()
                ->first();

            if ($otherActive) {
                return [
                    'ok' => false,
                    'message' => "Tidak dapat mengaktifkan {$lockedTahap->nama_tahap} karena {$otherActive->nama_tahap} masih aktif. Nonaktifkan atau selesaikan tahap yang sedang berjalan terlebih dahulu.",
                ];
            }

            $oldLabel = $lockedTahap->status_label;
            $lockedTahap->update([
                'status' => TahapPenyusunan::STATUS_ACTIVE,
                'activated_at' => now(),
                'activated_by' => Auth::id(),
            ]);

            $alasan = "Mengaktifkan {$lockedTahap->nama_tahap} ({$oldLabel} → Aktif)";
            if ($request->filled('alasan')) {
                $alasan .= ': ' . trim((string) $request->input('alasan'));
            }

            PeriodeHistory::create([
                'periode_id' => $lockedPeriode->id,
                'user_id' => Auth::id(),
                'aksi' => 'tahap_activate',
                'alasan' => $alasan,
            ]);

            return ['ok' => true, 'message' => "{$lockedTahap->nama_tahap} berhasil diaktifkan."];
        });

        return redirect()
            ->route('admin.periode.show', $periode)
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Menonaktifkan sementara tahap yang sedang aktif.
     */
    public function deactivateTahap(Request $request, PeriodePenyusunan $periode, TahapPenyusunan $tahap)
    {
        if ((int) $tahap->periode_id !== (int) $periode->id) {
            abort(404);
        }

        $result = DB::transaction(function () use ($request, $periode, $tahap) {
            $lockedPeriode = PeriodePenyusunan::where('id', $periode->id)->lockForUpdate()->firstOrFail();

            if ($lockedPeriode->isArchived()) {
                return ['ok' => false, 'message' => 'Periode arsip bersifat read-only dan status tahapnya tidak dapat diubah.'];
            }

            if (!$lockedPeriode->isActive()) {
                return ['ok' => false, 'message' => 'Perubahan status tahap hanya dapat dilakukan pada periode yang berstatus aktif.'];
            }

            $lockedTahap = TahapPenyusunan::where('id', $tahap->id)->lockForUpdate()->firstOrFail();

            if ($lockedTahap->isCompletedStatus()) {
                return ['ok' => false, 'message' => "{$lockedTahap->nama_tahap} sudah berstatus Selesai dan tidak dapat diubah ke Nonaktif Sementara."];
            }

            if ($lockedTahap->isInactiveStatus()) {
                return ['ok' => false, 'message' => "{$lockedTahap->nama_tahap} sudah berstatus Nonaktif Sementara."];
            }

            $lockedTahap->update([
                'status' => TahapPenyusunan::STATUS_INACTIVE,
                'deactivated_at' => now(),
            ]);

            $alasan = "Menonaktifkan sementara {$lockedTahap->nama_tahap} (Aktif → Nonaktif Sementara)";
            if ($request->filled('alasan')) {
                $alasan .= ': ' . trim((string) $request->input('alasan'));
            }

            PeriodeHistory::create([
                'periode_id' => $lockedPeriode->id,
                'user_id' => Auth::id(),
                'aksi' => 'tahap_deactivate',
                'alasan' => $alasan,
            ]);

            return ['ok' => true, 'message' => "{$lockedTahap->nama_tahap} berhasil dinonaktifkan sementara. Progres dan berkas Penyusun tetap tersimpan."];
        });

        return redirect()
            ->route('admin.periode.show', $periode)
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Menandai tahap Selesai setelah memeriksa kelayakan seluruh modul penyusun pada tahap tersebut.
     */
    public function completeTahap(Request $request, PeriodePenyusunan $periode, TahapPenyusunan $tahap)
    {
        if ((int) $tahap->periode_id !== (int) $periode->id) {
            abort(404);
        }

        $result = DB::transaction(function () use ($request, $periode, $tahap) {
            $lockedPeriode = PeriodePenyusunan::where('id', $periode->id)->lockForUpdate()->firstOrFail();

            if ($lockedPeriode->isArchived()) {
                return ['ok' => false, 'message' => 'Periode arsip bersifat read-only dan status tahapnya tidak dapat diubah.'];
            }

            if (!$lockedPeriode->isActive()) {
                return ['ok' => false, 'message' => 'Tahap hanya dapat diselesaikan pada periode yang berstatus aktif.'];
            }

            $lockedTahap = TahapPenyusunan::where('id', $tahap->id)->lockForUpdate()->firstOrFail();

            if ($lockedTahap->isCompletedStatus()) {
                return ['ok' => false, 'message' => "{$lockedTahap->nama_tahap} sudah berstatus Selesai."];
            }

            // Pemeriksaan kelayakan sebelum tahap dinyatakan Selesai
            $approvedApplications = PenyusunApplication::where('periode_id', $lockedPeriode->id)
                ->where('status', 'approved')
                ->with(['moduls' => fn($q) => $q->where('tahap_id', $lockedTahap->id)])
                ->get();

            $missingCount = 0;
            $pendingCount = 0;
            $rejectedCount = 0;

            foreach ($approvedApplications as $app) {
                $modul = $app->moduls->first();
                if (!$modul) {
                    $missingCount++;
                } elseif ($modul->status === 'pending') {
                    $pendingCount++;
                } elseif ($modul->status === 'rejected') {
                    $rejectedCount++;
                }
            }

            if ($missingCount > 0 || $pendingCount > 0 || $rejectedCount > 0) {
                $details = [];
                if ($missingCount > 0) {
                    $details[] = "{$missingCount} penyusun belum mengunggah modul";
                }
                if ($pendingCount > 0) {
                    $details[] = "{$pendingCount} modul masih menunggu validasi Admin";
                }
                if ($rejectedCount > 0) {
                    $details[] = "{$rejectedCount} modul berstatus ditolak dan belum disetujui";
                }

                return [
                    'ok' => false,
                    'message' => "Tidak dapat menandai {$lockedTahap->nama_tahap} Selesai karena masih terdapat pekerjaan yang belum tuntas: " . implode(', ', $details) . '.',
                ];
            }

            $oldLabel = $lockedTahap->status_label;
            $lockedTahap->update([
                'status' => TahapPenyusunan::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => Auth::id(),
            ]);

            $alasan = "Menandai {$lockedTahap->nama_tahap} Selesai ({$oldLabel} → Selesai)";
            if ($request->filled('alasan')) {
                $alasan .= ': ' . trim((string) $request->input('alasan'));
            }

            PeriodeHistory::create([
                'periode_id' => $lockedPeriode->id,
                'user_id' => Auth::id(),
                'aksi' => 'tahap_complete',
                'alasan' => $alasan,
            ]);

            return ['ok' => true, 'message' => "{$lockedTahap->nama_tahap} berhasil ditandai Selesai dan dikunci."];
        });

        return redirect()
            ->route('admin.periode.show', $periode)
            ->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
