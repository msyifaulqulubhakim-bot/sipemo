@extends('emails.layouts.sipemo', ['subject' => $status === 'approved' ? 'Modul Pembelajaran Tahap ' . $tahap . ' Disetujui' : 'Catatan Revisi Modul Tahap ' . $tahap])

@section('content')
    <div class="greeting">Halo, {{ $namaPenyusun }}</div>

    @if($status === 'approved')
        <p>Kabar baik! Modul pembelajaran yang Anda unggah untuk <strong>Tahap {{ $tahap }}</strong> telah diperiksa dan <strong>disetujui</strong> oleh Administrator UPT PJJ.</p>
        <div style="text-align: center;">
            <span class="badge badge-success">Modul Tahap {{ $tahap }} Disetujui</span>
        </div>
    @else
        <p>Modul pembelajaran yang Anda unggah untuk <strong>Tahap {{ $tahap }}</strong> telah diperiksa oleh Administrator dan <strong>memerlukan revisi/perbaikan</strong>.</p>
        <div style="text-align: center;">
            <span class="badge badge-danger">Perlu Revisi Tahap {{ $tahap }}</span>
        </div>
    @endif

    <div class="card-details">
        <table>
            <tr>
                <td class="label">Mata Kuliah:</td>
                <td class="value">{{ $mataKuliah }}</td>
            </tr>
            <tr>
                <td class="label">Tahap Penyusunan:</td>
                <td class="value">Tahap {{ $tahap }}</td>
            </tr>
            @if(!empty($namaTahap))
            <tr>
                <td class="label">Keterangan Tahap:</td>
                <td class="value">{{ $namaTahap }}</td>
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

    @if($status === 'approved')
        <p>Silakan melanjutkan penyusunan modul ke tahap berikutnya yang tersedia atau mempersiapkan Final Draft jika seluruh tahap telah selesai.</p>
    @else
        <p>Harap periksa catatan di atas, lakukan perbaikan pada dokumen modul Anda, dan unggah kembali berkas modul revisi melalui dashboard SIPeMo.</p>
    @endif

    <div class="button-wrapper">
        <a href="{{ $modulUrl ?? url('/penyusun/modul') }}" class="btn-primary">Buka Halaman Modul</a>
    </div>
@endsection
