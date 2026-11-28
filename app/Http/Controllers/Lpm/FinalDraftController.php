<?php

namespace App\Http\Controllers\Lpm;

use App\Http\Controllers\Controller;
use App\Models\FinalDraft;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FinalDraftController extends Controller
{
    public function index()
    {
        // Ambil semua penyusun yang approved dengan relasi yang diperlukan
        $penyusuns = \App\Models\PenyusunApplication::where('status', 'approved')
            ->with(['mataKuliah.jurusan', 'moduls.tahapPenyusunan', 'finalDrafts', 'publicationModuls'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Ambil jurusan yang aktif untuk penyusunan modul (S1 dan S2 PJJ PAI)
        $allJurusans = \App\Models\Jurusan::whereHas('mataKuliah')->orderBy('id', 'asc')->get();

        // Kelompokkan penyusun berdasarkan jurusan
        $penyusunsByJurusan = $penyusuns->groupBy(function($penyusun) {
            return $penyusun->mataKuliah->jurusan->nama_jurusan ?? 'Lainnya';
        });

        // Pastikan semua jurusan ada dalam array, meskipun kosong
        $penyusunsByJurusanWithAll = collect();
        foreach($allJurusans as $jurusan) {
            $penyusunsByJurusanWithAll->put($jurusan->nama_jurusan, $penyusunsByJurusan->get($jurusan->nama_jurusan, collect()));
        }

        // Tambahkan jurusan "Lainnya" jika ada penyusun tanpa jurusan
        if($penyusunsByJurusan->has('Lainnya')) {
            $penyusunsByJurusanWithAll->put('Lainnya', $penyusunsByJurusan->get('Lainnya'));
        }

        // Ambil final drafts untuk statistik
        $finalDrafts = FinalDraft::whereIn('status', ['approved_by_reviewer', 'approved', 'rejected'])
            ->with(['penyusunApplication', 'mataKuliah', 'lpmValidator'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return view('lpm.final-draft.index', compact('penyusunsByJurusanWithAll', 'finalDrafts'));
    }

    public function show(FinalDraft $finalDraft)
    {
        $finalDraft->load(['penyusunApplication', 'mataKuliah', 'lpmValidator']);
        
        return view('lpm.final-draft.show', compact('finalDraft'));
    }

    public function download(FinalDraft $finalDraft)
    {
        if (!$finalDraft->file_path || !Storage::disk('public')->exists($finalDraft->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }

        return response()->download(Storage::disk('public')->path($finalDraft->file_path), $finalDraft->file_name);
    }

    public function validate(Request $request, FinalDraft $finalDraft)
    {
        $request->validate([
            'status' => 'required|in:approved,rejected',
            'catatan_lpm' => 'nullable|string|max:1000',
        ]);

        try {
            $oldStatus = $finalDraft->status;

            // LPM langsung menentukan status final (approved/rejected)
            $finalDraft->update([
                'status' => $request->status,
                'catatan_lpm' => $request->catatan_lpm,
                'lpm_validated_at' => now(),
                'lpm_validated_by' => Auth::id(),
            ]);

            Log::info('Final draft validated by LPM', [
                'final_draft_id' => $finalDraft->id,
                'lpm_decision' => $request->status,
                'final_status' => $request->status,
                'lpm_id' => Auth::id(),
                'lpm_name' => Auth::user()->name
            ]);

            // Kirim notifikasi email ke Penyusun hanya jika status berubah
            if ($oldStatus !== $request->status) {
                $finalDraft->loadMissing(['penyusunApplication', 'mataKuliah']);
                app(NotificationService::class)->sendFinalDraftLpmValidated(
                    $finalDraft,
                    $request->status,
                    $request->catatan_lpm
                );
            }

            $message = $request->status === 'approved' 
                ? 'Final draft disetujui LPM.' 
                : 'Final draft ditolak LPM.';

            return redirect()->route('lpm.final-draft.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            Log::error('Error validating final draft by LPM: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat memvalidasi final draft: ' . $e->getMessage());
        }
    }
}
