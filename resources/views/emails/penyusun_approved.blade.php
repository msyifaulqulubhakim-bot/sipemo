@extends('emails.layouts.sipemo', ['subject' => 'Pendaftaran Penyusun Modul Disetujui'])

@section('content')
    <div class="greeting">Halo, {{ $namaPenyusun }}</div>

    <p>Selamat! Pengajuan pendaftaran Anda sebagai <strong>Penyusun Modul Pembelajaran</strong> pada sistem SIPeMo telah <strong>disetujui</strong> oleh Administrator UPT PJJ.</p>

    <div style="text-align: center;">
        <span class="badge badge-success">Pendaftaran Disetujui</span>
    </div>

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
            @if(!empty($periode))
            <tr>
                <td class="label">Periode Penyusunan:</td>
                <td class="value">{{ $periode }}</td>
            </tr>
            @endif
            @if(!empty($nidn))
            <tr>
                <td class="label">NIDN:</td>
                <td class="value">{{ $nidn }}</td>
            </tr>
            @endif
        </table>
    </div>

    <p>Akun penyusun Anda kini telah aktif. Anda dapat masuk ke aplikasi SIPeMo menggunakan akun portal kampus atau kredensial yang telah didaftarkan untuk mulai menyusun dan mengunggah modul pembelajaran sesuai tahap yang tersedia.</p>

    <div class="button-wrapper">
        <a href="{{ $loginUrl ?? url('/login') }}" class="btn-primary">Masuk ke SIPeMo</a>
    </div>
@endsection
