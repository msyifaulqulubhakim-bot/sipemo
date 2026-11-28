@extends('emails.layouts.sipemo', ['subject' => 'Pendaftaran Reviewer Disetujui & Penugasan Mata Kuliah'])

@section('content')
    <div class="greeting">Halo, {{ $namaReviewer }}</div>

    <p>Selamat! Pendaftaran Anda sebagai <strong>Reviewer Modul Pembelajaran</strong> pada sistem SIPeMo telah <strong>disetujui</strong> oleh Administrator UPT PJJ.</p>

    <div style="text-align: center;">
        <span class="badge badge-success">Reviewer Disetujui</span>
    </div>

    @if(!empty($mataKuliahList) && count($mataKuliahList) > 0)
    <p>Anda telah ditugaskan untuk menelaah (mereview) modul pada mata kuliah berikut:</p>
    <div class="card-details">
        <ul style="margin: 0; padding-left: 20px; color: #1e293b; font-weight: 600;">
            @foreach($mataKuliahList as $mk)
                <li style="padding: 4px 0;">{{ $mk }}</li>
            @endforeach
        </ul>
    </div>
    @else
    <p>Penugasan mata kuliah spesifik untuk Anda akan segera diatur oleh Administrator UPT PJJ.</p>
    @endif

    <p>Akun reviewer Anda telah aktif. Silakan masuk ke aplikasi SIPeMo untuk memantau dan meninjau final draft modul yang ditugaskan kepada Anda.</p>

    <div class="button-wrapper">
        <a href="{{ $loginUrl ?? url('/login') }}" class="btn-primary">Buka Dashboard Reviewer</a>
    </div>
@endsection
