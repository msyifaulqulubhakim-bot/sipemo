@extends('emails.layouts.sipemo', ['subject' => 'Final Draft Modul Siap Divalidasi LPM'])

@section('content')
    <div class="greeting">Yth. Bapak/Ibu Tim Lembaga Penjaminan Mutu (LPM)</div>

    <p>Pemberitahuan: Terdapat dokumen <strong>Final Draft Modul Pembelajaran</strong> yang telah selesai ditelaah serta <strong>disetujui oleh Reviewer</strong>, dan kini siap untuk divalidasi oleh LPM.</p>

    <div style="text-align: center;">
        <span class="badge badge-info">Menunggu Validasi LPM</span>
    </div>

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
            <tr>
                <td class="label">Penyusun:</td>
                <td class="value">{{ $namaPenyusun }}</td>
            </tr>
            @if(!empty($namaReviewer))
            <tr>
                <td class="label">Reviewer:</td>
                <td class="value">{{ $namaReviewer }}</td>
            </tr>
            @endif
        </table>
    </div>

    @if(!empty($catatanReviewer))
    <div class="notes-box">
        <div class="notes-title">Catatan Reviewer:</div>
        <div>{{ $catatanReviewer }}</div>
    </div>
    @endif

    <p>Silakan masuk ke aplikasi SIPeMo untuk mengunduh naskah final draft dan memberikan keputusan validasi LPM.</p>

    <div class="button-wrapper">
        <a href="{{ $lpmUrl ?? url('/lpm/final-draft') }}" class="btn-primary">Buka Dashboard LPM</a>
    </div>
@endsection
