@extends('layouts.app')

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.periode.index') }}" class="hover:text-green-600 transition-colors">Kelola Periode</a>
                <span>/</span>
                @if($periode->status === 'archived')
                    <a href="{{ route('admin.periode.arsip') }}" class="hover:text-green-600 transition-colors">Arsip</a>
                    <span>/</span>
                @endif
                <span class="text-gray-700 font-medium truncate max-w-xs sm:max-w-md">{{ $periode->nama_periode }}</span>
            </div>
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $periode->nama_periode }}
                </h2>
                @if($periode->status === 'active')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5 animate-pulse"></span>
                        Aktif
                    </span>
                @elseif($periode->status === 'draft')
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                        Draft
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                        <svg class="w-3 h-3 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Diarsipkan (Read-Only)
                    </span>
                @endif
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($periode->status === 'draft')
                <form method="POST" action="{{ route('admin.periode.activate', $periode) }}" class="inline"
                      onsubmit="return confirm('Apakah Anda yakin ingin mengaktifkan periode ini? Periode aktif lain (jika ada) harus diarsipkan terlebih dahulu.');">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center px-3.5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-md shadow-sm transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Aktifkan Periode
                    </button>
                </form>
                <a href="{{ route('admin.periode.edit', $periode) }}"
                   class="inline-flex items-center px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-semibold rounded-md transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
            @elseif($periode->status === 'active')
                <a href="{{ route('admin.periode.edit', $periode) }}"
                   class="inline-flex items-center px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-700 text-sm font-semibold rounded-md transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit Periode
                </a>
            @endif

            @if($periode->status === 'archived')
                <a href="{{ route('admin.periode.arsip') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md transition-colors duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Arsip
                </a>
            @else
                <a href="{{ route('admin.periode.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md transition-colors duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Daftar Periode
                </a>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-md">
                    <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                </div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-md">
                    <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                </div>
            @endif
            @if(session('info'))
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-md">
                    <p class="text-sm font-medium text-blue-800">{{ session('info') }}</p>
                </div>
            @endif

            {{-- Banner Read-Only untuk Periode Arsip --}}
            @if($periode->status === 'archived')
                <div class="bg-gray-50 border-l-4 border-gray-400 p-4 rounded-r-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-800">
                                Periode ini telah diarsipkan{{ $periode->archived_at ? ' pada ' . formatTanggalIndonesia($periode->archived_at) . ' pukul ' . $periode->archived_at->format('H:i') . ' WIB' : '' }}.
                            </p>
                            <p class="text-xs text-gray-600 mt-0.5">
                                Seluruh data pada periode arsip berstatus <strong>read-only</strong> dan dilindungi dari perubahan status kembali untuk menjamin kepatuhan audit.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Informasi Periode --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Informasi Periode</h3>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Periode</dt>
                            <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $periode->nama_periode }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Mulai</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $periode->tanggal_mulai ? formatTanggalIndonesia($periode->tanggal_mulai) : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Selesai</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $periode->tanggal_selesai ? formatTanggalIndonesia($periode->tanggal_selesai) : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ $periode->status === 'archived' ? 'Waktu Diarsipkan' : 'Status Berjalan' }}
                            </dt>
                            <dd class="mt-1 text-sm">
                                @if($periode->status === 'archived')
                                    <span class="font-medium text-gray-800">
                                        {{ $periode->archived_at ? formatTanggalIndonesia($periode->archived_at) : '—' }}
                                    </span>
                                @elseif($periode->status === 'active')
                                    <span class="text-green-600 font-semibold">Sedang Berjalan</span>
                                @else
                                    <span class="text-yellow-600 font-semibold">Draft (Belum Aktif)</span>
                                @endif
                            </dd>
                        </div>
                        @if($periode->deskripsi)
                            <div class="sm:col-span-2 lg:col-span-4">
                                <dt class="text-xs font-medium text-gray-500 uppercase tracking-wider">Deskripsi</dt>
                                <dd class="mt-1 text-sm text-gray-700 whitespace-pre-line">{{ $periode->deskripsi }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Stats Cards Progres Periode --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Penyusun Terdaftar</p>
                    <p class="mt-1 text-2xl font-bold text-gray-800">{{ $stats['total_penyusun'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $stats['approved_penyusun'] }} pengajuan disetujui</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <p class="text-xs text-green-600 uppercase tracking-wider font-semibold">Modul Disetujui</p>
                    <p class="mt-1 text-2xl font-bold text-green-600">{{ $stats['total_moduls'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Total modul lolos review</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <p class="text-xs text-blue-600 uppercase tracking-wider font-semibold">Final Draft Disetujui</p>
                    <p class="mt-1 text-2xl font-bold text-blue-600">{{ $stats['total_final_drafts'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Total naskah final approved</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <p class="text-xs text-purple-600 uppercase tracking-wider font-semibold">Publikasi Modul</p>
                    <p class="mt-1 text-2xl font-bold text-purple-600">{{ $stats['total_publications'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Total modul tervalidasi publikasi</p>
                </div>
            </div>

            {{-- 4 Tahap Penyusunan --}}
            @php
                $tahapList = $periode->tahapPenyusunans->keyBy('tahap');
                $activeTahap = $periode->tahapPenyusunans->first(fn($t) => $t->isActiveStatus());
                $completedTahapCount = $periode->tahapPenyusunans->filter(fn($t) => $t->isCompletedStatus())->count();
                $defaultTahap = [
                    1 => ['nama' => 'Tahap 1', 'modul' => 'Modul 1 dan Modul 2'],
                    2 => ['nama' => 'Tahap 2', 'modul' => 'Modul 3 dan Modul 4'],
                    3 => ['nama' => 'Tahap 3', 'modul' => 'Modul 5'],
                    4 => ['nama' => 'Tahap 4', 'modul' => 'Modul 6'],
                ];
            @endphp

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg"
                 x-data="{
                     confirmModal: false,
                     modalTitle: '',
                     modalBody: '',
                     confirmLabel: '',
                     confirmClass: 'bg-green-600 hover:bg-green-700',
                     actionUrl: '',
                     openConfirm(title, body, label, btnClass, url) {
                         this.modalTitle = title;
                         this.modalBody = body;
                         this.confirmLabel = label;
                         this.confirmClass = btnClass;
                         this.actionUrl = url;
                         this.confirmModal = true;
                     }
                 }">
                <div class="px-6 py-4 border-b border-gray-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Struktur Tahap Penyusunan (4 Tahap &amp; 6 Modul)</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Setiap periode terdiri dari tepat 4 tahap dengan maksimal 1 tahap Aktif dalam satu waktu.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 font-medium">
                            {{ $periode->nama_periode }} ({{ $periode->status === 'active' ? 'Aktif' : ($periode->status === 'archived' ? 'Arsip' : 'Draft') }})
                        </span>
                        <span class="px-2.5 py-1 rounded-md bg-gray-100 text-gray-700 font-medium">
                            4 Tahap
                        </span>
                        @if($activeTahap)
                            <span class="px-2.5 py-1 rounded-md bg-green-100 text-green-800 font-semibold">
                                Tahap Aktif: {{ $activeTahap->nama_tahap }}
                            </span>
                        @else
                            <span class="px-2.5 py-1 rounded-md bg-amber-50 text-amber-800 font-medium">
                                Tidak ada tahap yang sedang aktif.
                            </span>
                        @endif
                        <span class="px-2.5 py-1 rounded-md bg-blue-50 text-blue-800 font-semibold">
                            Tahap Selesai: {{ $completedTahapCount }} dari 4
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach($defaultTahap as $num => $info)
                            @php
                                $tahapData = $tahapList->get($num);
                                $isAktif = $tahapData && $tahapData->isActiveStatus();
                                $isSelesai = $tahapData && $tahapData->isCompletedStatus();
                                $isNonaktif = !$tahapData || $tahapData->isInactiveStatus();
                            @endphp
                            <div class="border rounded-lg p-4 flex flex-col justify-between {{ $isAktif ? 'border-green-300 bg-green-50/40' : ($isSelesai ? 'border-indigo-200 bg-indigo-50/30' : 'border-gray-200 bg-gray-50') }}">
                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-2">
                                        <span class="text-xs font-semibold uppercase tracking-wider text-green-700 bg-green-100 px-2 py-0.5 rounded">
                                            {{ $info['nama'] }}
                                        </span>
                                        @if($isAktif)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1 animate-pulse"></span>
                                                Aktif
                                            </span>
                                        @elseif($isSelesai)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                                                Selesai
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-200 text-gray-700">
                                                Nonaktif Sementara
                                            </span>
                                        @endif
                                    </div>
                                    <h4 class="text-sm font-semibold text-gray-900 mb-1">
                                        {{ $tahapData ? $tahapData->nama_tahap : $info['nama'] }}
                                    </h4>
                                    <p class="text-xs text-gray-600 mb-2">
                                        Cakupan: <strong>{{ $info['modul'] }}</strong>
                                    </p>
                                </div>

                                <div class="text-xs text-gray-500 border-t border-gray-200 pt-2 mt-2 space-y-1">
                                    @if($tahapData && $tahapData->activated_at)
                                        <div>Diaktifkan: {{ formatTanggalIndonesia($tahapData->activated_at) }} {{ $tahapData->activated_at->format('H:i') }}</div>
                                    @endif
                                    @if($tahapData && $tahapData->completed_at)
                                        <div class="text-indigo-700 font-medium">Diselesaikan: {{ formatTanggalIndonesia($tahapData->completed_at) }} {{ $tahapData->completed_at->format('H:i') }}</div>
                                    @elseif($tahapData && $tahapData->deactivated_at && $isNonaktif)
                                        <div>Dinonaktifkan: {{ formatTanggalIndonesia($tahapData->deactivated_at) }} {{ $tahapData->deactivated_at->format('H:i') }}</div>
                                    @endif
                                    @if(!$tahapData || (!$tahapData->activated_at && !$tahapData->completed_at))
                                        <span class="italic text-gray-400">Belum pernah diaktifkan</span>
                                    @endif
                                </div>

                                @if($tahapData && $periode->status === 'active')
                                    <div class="mt-3 pt-3 border-t border-gray-200 flex flex-col gap-2">
                                        @if($isAktif)
                                            <button type="button"
                                                    @click="openConfirm(
                                                        'Nonaktifkan {{ $tahapData->nama_tahap }} sementara?',
                                                        'Penyusun tidak dapat melanjutkan pengerjaan atau mengunggah modul baru pada tahap ini selama dinonaktifkan. Seluruh progres dan dokumen yang sudah tersimpan tetap dipertahankan.',
                                                        'Nonaktifkan Tahap',
                                                        'bg-amber-600 hover:bg-amber-700',
                                                        '{{ route('admin.periode.tahap.deactivate', [$periode, $tahapData]) }}'
                                                    )"
                                                    class="w-full inline-flex justify-center items-center px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 text-xs font-semibold rounded-md transition">
                                                Nonaktifkan Sementara
                                            </button>
                                            <button type="button"
                                                    @click="openConfirm(
                                                        'Tandai {{ $tahapData->nama_tahap }} Selesai?',
                                                        'Tahap ini akan dikunci dan tidak dapat diaktifkan kembali melalui tindakan biasa. Pastikan seluruh pekerjaan dan validasi tahap sudah selesai.',
                                                        'Ya, Tandai Selesai',
                                                        'bg-indigo-600 hover:bg-indigo-700',
                                                        '{{ route('admin.periode.tahap.complete', [$periode, $tahapData]) }}'
                                                    )"
                                                    class="w-full inline-flex justify-center items-center px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-md transition">
                                                Tandai Selesai
                                            </button>
                                        @elseif($isNonaktif)
                                            @if(!$activeTahap)
                                                <button type="button"
                                                        @click="openConfirm(
                                                            'Aktifkan {{ $tahapData->nama_tahap }}?',
                                                            '{{ $tahapData->nama_tahap }} akan dibuka untuk Penyusun. Pastikan tidak ada tahap lain yang sedang aktif.',
                                                            'Ya, Aktifkan',
                                                            'bg-green-600 hover:bg-green-700',
                                                            '{{ route('admin.periode.tahap.activate', [$periode, $tahapData]) }}'
                                                        )"
                                                        class="w-full inline-flex justify-center items-center px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white text-xs font-semibold rounded-md transition">
                                                    Aktifkan Tahap
                                                </button>
                                            @else
                                                <p class="text-[11px] text-gray-500 italic text-center">
                                                    {{ $activeTahap->nama_tahap }} sedang aktif.
                                                </p>
                                            @endif
                                            <button type="button"
                                                    @click="openConfirm(
                                                        'Tandai {{ $tahapData->nama_tahap }} Selesai?',
                                                        'Tahap ini akan dikunci dan tidak dapat diaktifkan kembali melalui tindakan biasa. Pastikan seluruh pekerjaan dan validasi tahap sudah selesai.',
                                                        'Ya, Tandai Selesai',
                                                        'bg-indigo-600 hover:bg-indigo-700',
                                                        '{{ route('admin.periode.tahap.complete', [$periode, $tahapData]) }}'
                                                    )"
                                                    class="w-full inline-flex justify-center items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-md transition">
                                                Tandai Selesai
                                            </button>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Modal Dialog Konfirmasi Perubahan Status Tahap --}}
                <div x-show="confirmModal"
                     x-cloak
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                     @keydown.escape.window="confirmModal = false">
                    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6" @click.away="confirmModal = false">
                        <h4 class="text-base font-semibold text-gray-900" x-text="modalTitle"></h4>
                        <p class="mt-2 text-sm text-gray-600" x-text="modalBody"></p>
                        <form method="POST" :action="actionUrl" class="mt-5 flex justify-end gap-2">
                            @csrf
                            <button type="button"
                                    @click="confirmModal = false"
                                    class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md transition">
                                Batal
                            </button>
                            <button type="submit"
                                    :class="confirmClass"
                                    class="px-4 py-2 text-white text-sm font-semibold rounded-md transition"
                                    x-text="confirmLabel">
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Daftar Pengajuan Penyusun --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Daftar Pengajuan Penyusun</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Pengajuan yang terhubung dengan periode ini.</p>
                    </div>
                    <span class="text-xs font-semibold text-gray-500">
                        Total: {{ $periode->penyusunApplications->count() }}
                    </span>
                </div>
                <div class="p-6">
                    @if($periode->penyusunApplications->isEmpty())
                        <div class="text-center py-8">
                            <svg class="mx-auto h-10 w-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <p class="mt-2 text-sm text-gray-500">Belum ada pengajuan penyusun yang terhubung dengan periode ini.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Penyusun</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mata Kuliah / Jurusan</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Modul Approved</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($periode->penyusunApplications as $app)
                                        <tr class="hover:bg-gray-50 transition-colors duration-100">
                                            <td class="px-4 py-3">
                                                <div class="text-sm font-semibold text-gray-900">{{ $app->nama_penyusun }}</div>
                                                <div class="text-xs text-gray-500">{{ $app->email }}</div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="text-sm text-gray-900 font-medium">
                                                    {{ $app->mataKuliah?->nama_mata_kuliah ?? ($app->mata_kuliah ?: '—') }}
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    {{ $app->mataKuliah?->jurusan?->nama_jurusan ?? ($app->jurusan ?: '—') }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-center text-sm font-medium text-gray-800">
                                                {{ $app->moduls->where('status', 'approved')->count() }} / 6
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @if($app->status === 'approved')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Approved
                                                    </span>
                                                @elseif($app->status === 'rejected')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Rejected
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        Pending
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                                <a href="{{ route('admin.penyusun.show', $app) }}"
                                                   class="inline-flex items-center px-3 py-1 bg-green-50 hover:bg-green-100 text-green-700 text-xs font-medium rounded-md transition-colors duration-150">
                                                    Detail Pengajuan
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Riwayat Aktivitas & Audit Periode --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Riwayat Aktivitas & Audit Periode</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Catatan riwayat status dan tindakan pengarsipan periode ini.</p>
                </div>
                <div class="p-6">
                    @if($periode->histories->isEmpty())
                        <div class="text-center py-6">
                            <p class="text-sm text-gray-500">Belum ada riwayat aktivitas yang tercatat untuk periode ini.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Waktu</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Eksekutor</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Keterangan / Alasan</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($periode->histories->sortByDesc('created_at') as $history)
                                        <tr class="hover:bg-gray-50 transition-colors duration-100">
                                            <td class="px-4 py-3 text-xs text-gray-700 whitespace-nowrap">
                                                <div>{{ formatTanggalIndonesia($history->created_at) }}</div>
                                                <div class="text-gray-400">{{ $history->created_at ? $history->created_at->format('H:i:s') : '—' }} WIB</div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap">
                                                @if($history->aksi === 'archive')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-800">
                                                        Diarsipkan
                                                    </span>
                                                @elseif($history->aksi === 'activate')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">
                                                        Diaktifkan
                                                    </span>
                                                @elseif($history->aksi === 'create')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">
                                                        Dibuat
                                                    </span>
                                                @elseif($history->aksi === 'tahap_activate')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-100 text-green-800">
                                                        Tahap Diaktifkan
                                                    </span>
                                                @elseif($history->aksi === 'tahap_deactivate')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">
                                                        Tahap Dinonaktifkan
                                                    </span>
                                                @elseif($history->aksi === 'tahap_complete')
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-100 text-indigo-800">
                                                        Tahap Selesai
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-gray-100 text-gray-800">
                                                        {{ ucfirst($history->aksi) }}
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-xs text-gray-700 whitespace-nowrap">
                                                @if($history->user)
                                                    <div class="font-medium text-gray-900">{{ $history->user->name }}</div>
                                                    <div class="text-gray-400">{{ $history->user->email }}</div>
                                                @else
                                                    <span class="inline-flex items-center text-gray-600 font-medium">
                                                        <svg class="w-3.5 h-3.5 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        Sistem Otomatis (Scheduler)
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-xs text-gray-600">
                                                {{ $history->alasan ?: '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection

