@extends('layouts.app')

@section('header')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Kelola Periode Penyusunan') }}
            </h2>
            <p class="text-xs text-gray-500 mt-1">
                Atur jadwal periode dan tahapan penyusunan modul pembelajaran
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.periode.create') }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-md shadow-sm transition-colors duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Periode Baru
            </a>
            <a href="{{ route('admin.periode.arsip') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md transition-colors duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                Lihat Arsip
            </a>
        </div>
    </div>
@endsection

@section('content')
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-r-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('info'))
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-800">{{ session('info') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Status Banner: Cek Periode Aktif --}}
            @if($stats['active'] == 0)
                <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded-r-md">
                    <div class="flex items-center justify-between flex-wrap gap-3">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-amber-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <p class="text-sm font-semibold text-amber-800">Saat ini belum ada periode penyusunan yang berstatus aktif.</p>
                                <p class="text-xs text-amber-700 mt-0.5">Pengajuan penyusun baru hanya dapat disetujui saat terdapat periode aktif. Anda dapat membuat periode baru atau mengaktifkan periode draft yang tersedia.</p>
                            </div>
                        </div>
                        <a href="{{ route('admin.periode.create') }}" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold rounded transition">
                            Buat Periode Baru
                        </a>
                    </div>
                </div>
            @else
                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-md">
                    <div class="flex items-center justify-between flex-wrap gap-2">
                        <div class="flex items-center">
                            <span class="w-2.5 h-2.5 rounded-full bg-green-500 mr-2.5 animate-pulse"></span>
                            <p class="text-sm font-medium text-green-900">
                                Periode Aktif Saat Ini: <strong>{{ $activePeriode->nama_periode }}</strong>
                                <span class="text-xs text-green-700 ml-1">
                                    ({{ $activePeriode->tanggal_mulai ? formatTanggalIndonesia($activePeriode->tanggal_mulai) : '—' }} s.d. {{ $activePeriode->tanggal_selesai ? formatTanggalIndonesia($activePeriode->tanggal_selesai) : '—' }})
                                </span>
                            </p>
                        </div>
                        <a href="{{ route('admin.periode.show', $activePeriode) }}" class="text-xs text-green-700 hover:text-green-900 font-semibold underline">
                            Lihat Detail & Tahap &rarr;
                        </a>
                    </div>
                </div>
            @endif

            {{-- Stats Cards --}}
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Total Periode</p>
                    <p class="mt-1 text-3xl font-bold text-gray-800">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <p class="text-xs text-green-600 uppercase tracking-wider font-semibold">Aktif</p>
                    <p class="mt-1 text-3xl font-bold text-green-600">{{ $stats['active'] }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <p class="text-xs text-yellow-600 uppercase tracking-wider font-semibold">Draft</p>
                    <p class="mt-1 text-3xl font-bold text-yellow-600">{{ $stats['draft'] }}</p>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-5">
                    <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">Diarsipkan</p>
                    <p class="mt-1 text-3xl font-bold text-gray-500">{{ $stats['archived'] }}</p>
                </div>
            </div>

            {{-- Filter --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('admin.periode.index') }}" class="flex flex-wrap gap-3 items-end">
                        <div class="flex-1 min-w-48">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Cari Periode</label>
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="Nama periode..."
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                        </div>
                        <div class="min-w-36">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status"
                                    class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-green-500 focus:border-green-500">
                                <option value="">Semua Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Diarsipkan</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit"
                                    class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition-colors duration-150">
                                Filter
                            </button>
                            @if(request('search') || request('status'))
                                <a href="{{ route('admin.periode.index') }}"
                                   class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-md transition-colors duration-150">
                                    Reset
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            {{-- Tabel Periode --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($periodes->isEmpty())
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <h3 class="mt-3 text-base font-semibold text-gray-800">Belum ada data periode penyusunan</h3>
                            <p class="mt-1 text-sm text-gray-500 max-w-sm mx-auto">
                                Buat periode penyusunan baru untuk mengatur jadwal dan 4 tahap modul pembelajaran.
                            </p>
                            <div class="mt-5">
                                <a href="{{ route('admin.periode.create') }}"
                                   class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-md shadow-sm transition">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Buat Periode Baru
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama Periode</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal Mulai</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal Selesai</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Penyusun</th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($periodes as $periode)
                                        <tr class="hover:bg-gray-50 transition-colors duration-100 {{ $periode->status === 'active' ? 'bg-green-50/30' : '' }}">
                                            <td class="px-4 py-3">
                                                <div class="text-sm font-semibold text-gray-900">{{ $periode->nama_periode }}</div>
                                                @if($periode->deskripsi)
                                                    <div class="text-xs text-gray-500 mt-0.5 truncate max-w-xs">{{ $periode->deskripsi }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                                {{ $periode->tanggal_mulai ? formatTanggalIndonesia($periode->tanggal_mulai) : '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-700 whitespace-nowrap">
                                                {{ $periode->tanggal_selesai ? formatTanggalIndonesia($periode->tanggal_selesai) : '—' }}
                                            </td>
                                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                                @if($periode->status === 'active')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1 animate-pulse"></span>
                                                        Aktif
                                                    </span>
                                                @elseif($periode->status === 'draft')
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        Draft
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                                        Diarsipkan
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-center text-sm font-medium text-gray-700">
                                                {{ $periode->penyusun_applications_count ?? 0 }}
                                            </td>
                                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                                <div class="flex items-center justify-center gap-1.5">
                                                    <a href="{{ route('admin.periode.show', $periode) }}"
                                                       class="inline-flex items-center px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded transition">
                                                        Detail
                                                    </a>

                                                    @if($periode->status === 'draft')
                                                        <a href="{{ route('admin.periode.edit', $periode) }}"
                                                           class="inline-flex items-center px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-medium rounded transition">
                                                            Edit
                                                        </a>
                                                        <form method="POST" action="{{ route('admin.periode.activate', $periode) }}" class="inline"
                                                              onsubmit="return confirm('Apakah Anda yakin ingin mengaktifkan periode ini? Periode aktif lain (jika ada) harus diarsipkan terlebih dahulu.');">
                                                            @csrf
                                                            <button type="submit"
                                                                    class="inline-flex items-center px-2.5 py-1 bg-green-600 hover:bg-green-700 text-white text-xs font-medium rounded transition">
                                                                Aktifkan
                                                            </button>
                                                        </form>
                                                    @elseif($periode->status === 'active')
                                                        <a href="{{ route('admin.periode.edit', $periode) }}"
                                                           class="inline-flex items-center px-2.5 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-medium rounded transition">
                                                            Edit
                                                        </a>
                                                    @endif
                                                </div>
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
