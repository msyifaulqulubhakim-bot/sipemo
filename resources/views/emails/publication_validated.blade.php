@extends('emails.layouts.sipemo', ['subject' => $status === 'approved' ? 'Berkas Publikasi & HKI Modul Disetujui' : 'Permintaan Perbaikan Berkas Publikasi Modul'])

@section('content')
    <div class="greeting">Halo, {{ $namaPenyusun }}</div>

    @if($status === 'approved')
        <p>Selamat! Berkas <strong>Publikasi Modul Pembelajaran dan Sertifikat HKI</strong> Anda telah diperiksa dan <strong>disetujui secara resmi</strong> oleh Administrator UPT Pembelajaran Jarak Jauh (PJJ).</p>
        <div style="text-align: center;">
            <span class="badge badge-success">Publikasi Disetujui &bull; Siklus Selesai</span>
        </div>
        <p>Seluruh tahapan penyusunan modul mulai dari pendaftaran, penyusunan berkala, telaah reviewer, penjaminan mutu LPM, hingga administrasi publikasi & HKI telah rampung 100%.</p>
    @else
        <p>Pengajuan berkas <strong>Publikasi Modul & HKI</strong> Anda telah diperiksa oleh Administrator dan <strong>memerlukan perbaikan atau kelengkapan tambahan</strong>.</p>
        <div style="text-align: center;">
            <span class="badge badge-danger">Perlu Perbaikan Berkas</span>
        </div>
    @endif

    <div class="card-details">
        <table>
            @if(!empty($judulModul))
            <tr>
                <td class="label">Judul Modul:</td>
                <td class="value">{{ $judulModul }}</td>
            </tr>
            @endif
            @if(!empty($mataKuliah))
            <tr>
                <td class="label">Mata Kuliah:</td>
                <td class="value">{{ $mataKuliah }}</td>
            </tr>
            @endif
        </table>
    </div>

    @if(!empty($catatanAdmin))
    <div class="{{ $status === 'approved' ? 'notes-box' : 'notes-box-danger' }}">
        <div class="notes-title">Catatan Administrator:</div>
        <div>{{ $catatanAdmin }}</div>
    </div>
    @endif

    @if($status !== 'approved')
        <p>Silakan lengkapi berkas atau perbaiki data yang diminta, kemudian ajukan kembali melalui halaman Publikasi di dashboard SIPeMo Anda.</p>
    @endif

    <div class="button-wrapper">
        <a href="{{ $publicationUrl ?? url('/penyusun/publication') }}" class="btn-primary">Buka Halaman Publikasi</a>
    </div>
@endsection
