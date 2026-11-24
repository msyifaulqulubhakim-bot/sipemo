@extends('layouts.app')

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-sm text-gray-500 mb-1">
                <a href="{{ route('admin.periode.index') }}" class="hover:text-green-600 transition-colors">
                    Kelola Periode
                </a>
                <span>/</span>
                <span class="text-gray-700 font-medium">Arsip</span>
            </div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Arsip Periode Penyusunan') }}
            </h2>
        </div>
        <div>
            <a href="{{ route('admin.periode.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md transition-colors duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Kembali ke Kelola Periode
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Banner Info Read-Only --}}
            <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                  clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-amber-800">
                            Arsip Periode bersifat <strong>Read-Only</strong>.
                        </p>
                        <p class="text-xs text-amber-700 mt-0.5">
                            Periode yang telah diarsipkan tidak dapat diaktifkan kembali. Data penyusun, modul, final draft, dan publikasi pada periode arsip dipertahankan untuk kebutuhan pelaporan, audit, dan penelusuran riwayat.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Stats Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-gray-100 text-gray-600 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Total Periode Diarsipkan</p>
                            <p class="text-2xl font-bold text-gray-800">{{ $stats['total_arsip'] }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-blue-50 text-blue-600 mr-4">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Total Pengajuan Penyusun Terarsip</p>
                            <p class="text-2xl font-bold text-blue-700">{{ $stats['total_penyusun_terarsip'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Filter Pencarian --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.periode.arsip') }}" class="flex flex-wrap gap-3 items-end">
                        <div class="flex-1 min-w-48">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cari Periode Arsip</label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="Ketik nama periode..."
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition-colors duration-150">
                                Cari
                            </button>
                            @if(request('search'))
                                <a href="{{ route('admin.periode.arsip') }}"
                                   class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md transition-colors duration-150">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            {{-- Tabel Daftar Arsip --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($periodes->isEmpty())
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">Belum ada periode yang diarsipkan</h3>
                            <p class="mt-1 text-xs text-gray-500">
                                Periode aktif yang melewati tanggal selesai akan diarsipkan secara otomatis oleh sistem setiap hari pukul 00:05 WIB.
                            </p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Periode</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Masa Berjalan</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Waktu Diarsipkan</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Penyusun</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($periodes as $periode)
                                        <tr class="hover:bg-gray-50 transition-colors duration-100">
                                            <td class="px-4 py-3">
                                                <div class="text-sm font-semibold text-gray-900">{{ $periode->nama_periode }}</div>
                                                @if($periode->deskripsi)
                                                    <div class="text-xs text-gray-500 mt-0.5 truncate max-w-sm">{{ $periode->deskripsi }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                                <div>
                                                    <span class="text-xs text-gray-500">Mulai:</span>
                                                    {{ $periode->tanggal_mulai ? formatTanggalIndonesia($periode->tanggal_mulai) : '—' }}
                                                </div>
                                                <div class="mt-0.5">
                                                    <span class="text-xs text-gray-500">Selesai:</span>
                                                    {{ $periode->tanggal_selesai ? formatTanggalIndonesia($periode->tanggal_selesai) : '—' }}
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                                @if($periode->archived_at)
                                                    <div class="font-medium text-gray-900">{{ formatTanggalIndonesia($periode->archived_at) }}</div>
                                                    <div class="text-xs text-gray-500">{{ $periode->archived_at->format('H:i') }} WIB</div>
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-center text-sm font-medium text-gray-800">
                                                {{ $periode->penyusun_applications_count ?? 0 }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                                    Diarsipkan
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                                <a href="{{ route('admin.periode.show', $periode) }}"
                                                   class="inline-flex items-center px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-md transition-colors duration-150">
                                                    <svg class="w-3.5 h-3.5 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                    Detail Arsip
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($periodes->hasPages())
                            <div class="mt-4">
                                {{ $periodes->links() }}
                            </div>
                        @endif
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection

