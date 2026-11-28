@extends('emails.layouts.sipemo', ['subject' => $status === 'approved_by_reviewer' ? 'Final Draft Modul Disetujui oleh Reviewer' : 'Catatan Telaah Reviewer untuk Final Draft Modul'])

@section('content')
    <div class="greeting">Halo, {{ $namaPenyusun }}</div>

    @if($status === 'approved_by_reviewer')
        <p>Kabar gembira! Dokumen <strong>Final Draft Modul</strong> yang Anda ajukan telah selesai ditelaah dan <strong>disetujui</strong> oleh Reviewer.</p>
        <div style="text-align: center;">
            <span class="badge badge-success">Disetujui Reviewer &bull; Diteruskan ke LPM</span>
        </div>
        <p>Final Draft Anda saat ini telah diteruskan secara otomatis ke tim Lembaga Penjaminan Mutu (LPM) untuk tahap validasi akhir.</p>
    @else
        <p>Dokumen <strong>Final Draft Modul</strong> yang Anda ajukan telah ditelaah oleh Reviewer dan <strong>memerlukan perbaikan (revisi)</strong> sebelum dapat diteruskan ke LPM.</p>
        <div style="text-align: center;">
            <span class="badge badge-danger">Perlu Revisi Reviewer</span>
        </div>
    @endif

    <div class="card-details">
        <table>
            <tr>
                <td class="label">Mata Kuliah:</td>
                <td class="value">{{ $mataKuliah }}</td>
            </tr>
            @if(!empty($judulModul))
            <tr>
                <td class="label">Judul Modul:</td>
                <td class="value">{{ $judulModul }}</td>
            </tr>
            @endif
            @if(!empty($namaReviewer))
            <tr>
                <td class="label">Reviewer:</td>
                <td class="value">{{ $namaReviewer }}</td>
            </tr>
            @endif
        </table>
    </div>

    @if(!empty($catatanReviewer))
    <div class="{{ $status === 'approved_by_reviewer' ? 'notes-box' : 'notes-box-danger' }}">
        <div class="notes-title">Catatan Reviewer:</div>
        <div>{{ $catatanReviewer }}</div>
    </div>
    @endif

    @if($status !== 'approved_by_reviewer')
        <p>Harap cermati catatan perbaikan di atas, revisi dokumen final draft Anda, dan unggah kembali melalui sistem SIPeMo.</p>
    @endif

    <div class="button-wrapper">
        <a href="{{ $finalDraftUrl ?? url('/penyusun/final-draft') }}" class="btn-primary">Lihat Final Draft</a>
    </div>
@endsection
