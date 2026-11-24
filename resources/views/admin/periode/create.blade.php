@extends('layouts.app')

@section('header')
    <div class="flex items-center space-x-2 text-sm text-gray-500 mb-1">
        <a href="{{ route('admin.periode.index') }}" class="hover:text-green-600 transition-colors">Kelola Periode</a>
        <span>/</span>
        <span class="text-gray-700 font-medium">Buat Periode Baru</span>
    </div>
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Buat Periode Penyusunan Baru') }}
    </h2>
@endsection

@section('content')
    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if($hasActivePeriode)
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-semibold text-blue-800">
                                Periode Aktif Saat Ini: {{ $activePeriode->nama_periode }}
                            </p>
                            <p class="text-xs text-blue-700 mt-0.5">
                                Sistem memberlakukan aturan <strong>maksimal satu periode aktif</strong> dalam satu waktu. Jika Anda membuat periode baru sekarang, disarankan menyimpannya dengan status <strong>Draft</strong> terlebih dahulu hingga periode aktif saat ini selesai atau diarsipkan.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.periode.store') }}" class="p-6 space-y-6">
                    @csrf

                    <div>
                        <h3 class="text-base font-semibold text-gray-900 border-b pb-3">Informasi Utama Periode</h3>
                    </div>

                    {{-- Nama Periode --}}
                    <div>
                        <label for="nama_periode" class="block text-sm font-medium text-gray-700 mb-1">
                            Nama Periode <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="nama_periode"
                               name="nama_periode"
                               value="{{ old('nama_periode') }}"
                               placeholder="Contoh: Periode Penyusunan Semester Gasal 2026/2027"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-green-500 focus:border-green-500 @error('nama_periode') border-red-500 @enderror"
                               required>
                        @error('nama_periode')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Status Periode --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Status Awal Periode <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="relative flex p-3 border rounded-lg cursor-pointer hover:bg-gray-50 focus:outline-none">
                                <input type="radio" name="status" value="draft"
                                       class="mt-0.5 h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500"
                                       {{ old('status', $hasActivePeriode ? 'draft' : 'active') === 'draft' ? 'checked' : '' }}>
                                <span class="ml-3 flex flex-col">
                                    <span class="block text-sm font-medium text-gray-900">Draft (Belum Aktif)</span>
                                    <span class="block text-xs text-gray-500">Periode dipersiapkan terlebih dahulu dan dapat diaktifkan nanti oleh Admin.</span>
                                </span>
                            </label>

                            <label class="relative flex p-3 border rounded-lg cursor-pointer hover:bg-gray-50 focus:outline-none">
                                <input type="radio" name="status" value="active"
                                       class="mt-0.5 h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500"
                                       {{ old('status', $hasActivePeriode ? 'draft' : 'active') === 'active' ? 'checked' : '' }}>
                                <span class="ml-3 flex flex-col">
                                    <span class="block text-sm font-medium text-gray-900">Langsung Aktif</span>
                                    <span class="block text-xs text-gray-500">Periode langsung berjalan sebagai periode aktif utama (hanya jika belum ada periode aktif lain).</span>
                                </span>
                            </label>
                        </div>
                        @error('status')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Tanggal Mulai & Tanggal Selesai --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="tanggal_mulai" class="block text-sm font-medium text-gray-700 mb-1">
                                Tanggal Mulai <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   id="tanggal_mulai"
                                   name="tanggal_mulai"
                                   value="{{ old('tanggal_mulai') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-green-500 focus:border-green-500 @error('tanggal_mulai') border-red-500 @enderror"
                                   required>
                            @error('tanggal_mulai')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tanggal_selesai" class="block text-sm font-medium text-gray-700 mb-1">
                                Tanggal Selesai (Batas Akhir Periode) <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   id="tanggal_selesai"
                                   name="tanggal_selesai"
                                   value="{{ old('tanggal_selesai') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-green-500 focus:border-green-500 @error('tanggal_selesai') border-red-500 @enderror"
                                   required>
                            <p class="text-[11px] text-gray-500 mt-1">Saat melewati tanggal ini, sistem scheduler akan mengarsipkan periode secara otomatis pada pukul 00:05 WIB.</p>
                            @error('tanggal_selesai')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Deskripsi --}}
                    <div>
                        <label for="deskripsi" class="block text-sm font-medium text-gray-700 mb-1">
                            Deskripsi / Catatan Periode (Opsional)
                        </label>
                        <textarea id="deskripsi"
                                  name="deskripsi"
                                  rows="3"
                                  placeholder="Tambahkan keterangan atau instruksi terkait periode penyusunan ini..."
                                  class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-green-500 focus:border-green-500 @error('deskripsi') border-red-500 @enderror">{{ old('deskripsi') }}</textarea>
                        @error('deskripsi')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Info Otomatisasi 4 Tahap & 6 Modul --}}
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <h4 class="text-sm font-semibold text-gray-900">Pembentukan Otomatis 4 Tahap & 6 Modul</h4>
                        </div>
                        <p class="text-xs text-gray-600 mb-3">
                            Sistem akan secara otomatis membentuk 4 tahapan penyusunan sesuai rentang tanggal periode:
                        </p>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                            <div class="bg-white p-2.5 rounded border border-gray-200">
                                <span class="font-bold text-green-700 block">Tahap 1</span>
                                <span class="text-gray-600">Modul 1 & Modul 2</span>
                            </div>
                            <div class="bg-white p-2.5 rounded border border-gray-200">
                                <span class="font-bold text-green-700 block">Tahap 2</span>
                                <span class="text-gray-600">Modul 3 & Modul 4</span>
                            </div>
                            <div class="bg-white p-2.5 rounded border border-gray-200">
                                <span class="font-bold text-green-700 block">Tahap 3</span>
                                <span class="text-gray-600">Modul 5</span>
                            </div>
                            <div class="bg-white p-2.5 rounded border border-gray-200">
                                <span class="font-bold text-green-700 block">Tahap 4</span>
                                <span class="text-gray-600">Modul 6</span>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t">
                        <a href="{{ route('admin.periode.index') }}"
                           class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Batal
                        </a>
                        <button type="submit"
                                class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-md shadow-sm transition">
                            Simpan Periode
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endsection
