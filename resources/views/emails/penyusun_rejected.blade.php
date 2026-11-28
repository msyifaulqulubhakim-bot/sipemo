@extends('emails.layouts.sipemo', ['subject' => 'Pemberitahuan Status Pendaftaran Penyusun Modul'])

@section('content')
    <div class="greeting">Yth. {{ $namaPenyusun }}</div>

    <p>Terima kasih telah mendaftar sebagai calon Penyusun Modul Pembelajaran di SIPeMo UPT PJJ UIN Siber Syekh Nurjati Cirebon.</p>

    <div style="text-align: center;">
        <span class="badge badge-danger">Pendaftaran Belum Dapat Disetujui</span>
    </div>

    <p>Setelah melalui proses verifikasi oleh Administrator, dengan ini kami sampaikan bahwa pengajuan pendaftaran Anda untuk mata kuliah berikut belum dapat disetujui:</p>

    <div class="card-details">
        <table>
            <tr>
                <td class="label">Mata Kuliah:</td>
                <td class="value">{{ $mataKuliah }}</td>
            </tr>
            @if(!empty($jurusan))
            <tr>
                <td class="label">Program Studi / Jurusan:</td>
                <td class="value">{{ $jurusan }}</td>
            </tr>
            @endif
        </table>
    </div>

    @if(!empty($rejectionReason))
    <div class="notes-box-danger">
        <div class="notes-title">Alasan / Catatan:</div>
        <div>{{ $rejectionReason }}</div>
    </div>
    @endif

    <p>Jika Anda memiliki pertanyaan lebih lanjut atau memerlukan informasi tambahan, silakan hubungi tim administrasi UPT Pembelajaran Jarak Jauh (PJJ).</p>

    <div class="button-wrapper">
        <a href="{{ url('/') }}" class="btn-primary">Kunjungi SIPeMo</a>
    </div>
@endsection
