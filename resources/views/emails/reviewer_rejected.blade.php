@extends('emails.layouts.sipemo', ['subject' => 'Pemberitahuan Status Pendaftaran Reviewer'])

@section('content')
    <div class="greeting">Yth. {{ $namaReviewer }}</div>

    <p>Terima kasih atas partisipasi dan kesediaan Anda mendaftar sebagai Reviewer Modul Pembelajaran di SIPeMo UPT PJJ UIN Siber Syekh Nurjati Cirebon.</p>

    <div style="text-align: center;">
        <span class="badge badge-danger">Pendaftaran Belum Dapat Diterima</span>
    </div>

    <p>Setelah melalui verifikasi dan evaluasi administratif oleh Administrator UPT PJJ, pengajuan pendaftaran reviewer Anda belum dapat kami setujui saat ini.</p>

    @if(!empty($rejectionReason))
    <div class="notes-box-danger">
        <div class="notes-title">Alasan Penolakan:</div>
        <div>{{ $rejectionReason }}</div>
    </div>
    @endif

    <p>Kami mengucapkan terima kasih yang sebesar-besarnya atas minat dan dedikasi Anda. Informasi lebih lanjut dapat dikonfirmasikan ke UPT PJJ.</p>

    <div class="button-wrapper">
        <a href="{{ url('/') }}" class="btn-primary">Kunjungi SIPeMo</a>
    </div>
@endsection
