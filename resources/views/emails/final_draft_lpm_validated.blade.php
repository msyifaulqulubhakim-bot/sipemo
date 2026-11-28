@extends('emails.layouts.sipemo', ['subject' => $status === 'approved' ? 'Final Draft Modul Divalidasi Resmi oleh LPM' : 'Catatan Validasi LPM untuk Final Draft Modul'])

@section('content')
    <div class="greeting">Halo, {{ $namaPenyusun }}</div>

    @if($status === 'approved')
        <p>Kabar luar biasa! Dokumen <strong>Final Draft Modul Pembelajaran</strong> Anda telah resmi <strong>disetujui dan divalidasi oleh Lembaga Penjaminan Mutu (LPM)</strong>.</p>
        <div style="text-align: center;">
            <span class="badge badge-success">Validasi LPM Selesai (Disetujui)</span>
        </div>
        <p>Tahap evaluasi substansi modul Anda telah selesai dengan sukses. Anda sekarang dapat melanjutkan ke tahap akhir, yaitu <strong>Pengajuan Berkas Publikasi & Sertifikat HKI</strong>.</p>
    @else
        <p>Dokumen <strong>Final Draft Modul Pembelajaran</strong> Anda telah ditinjau oleh Lembaga Penjaminan Mutu (LPM) dan <strong>memerlukan perbaikan</strong>.</p>
        <div style="text-align: center;">
            <span class="badge badge-danger">Perlu Revisi LPM</span>
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
        </table>
    </div>

    @if(!empty($catatanLpm))
    <div class="{{ $status === 'approved' ? 'notes-box' : 'notes-box-danger' }}">
        <div class="notes-title">Catatan / Evaluasi LPM:</div>
        <div>{{ $catatanLpm }}</div>
    </div>
    @endif

    <div class="button-wrapper">
        @if($status === 'approved')
            <a href="{{ $publicationUrl ?? url('/penyusun/publication') }}" class="btn-primary">Lanjutkan ke Pengajuan Publikasi</a>
        @else
            <a href="{{ $finalDraftUrl ?? url('/penyusun/final-draft') }}" class="btn-primary">Lihat Final Draft</a>
        @endif
    </div>
@endsection
