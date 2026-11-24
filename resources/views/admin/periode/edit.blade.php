@extends('layouts.app')

@section('header')
    <div class="flex items-center space-x-2 text-sm text-gray-500 mb-1">
        <a href="{{ route('admin.periode.index') }}" class="hover:text-green-600 transition-colors">Kelola Periode</a>
        <span>/</span>
        <a href="{{ route('admin.periode.show', $periode) }}" class="hover:text-green-600 transition-colors">{{ $periode->nama_periode }}</a>
        <span>/</span>
        <span class="text-gray-700 font-medium">Edit Periode</span>
    </div>
    <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Periode Penyusunan') }}
        </h2>
        <div>
            @if($periode->status === 'active')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-1.5 animate-pulse"></span>
                    Aktif
                </span>
            @elseif($periode->status === 'draft')
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                    Draft
                </span>
            @endif
        </div>
    </div>
@endsection

@section('content')
    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <form method="POST" action="{{ route('admin.periode.update', $periode) }}" class="p-6 space-y-6">
                    @csrf
                    @method('PUT')

                    <div>
                        <h3 class="text-base font-semibold text-gray-900 border-b pb-3">Perbarui Informasi Periode</h3>
                    </div>

                    {{-- Nama Periode --}}
                    <div>
                        <label for="nama_periode" class="block text-sm font-medium text-gray-700 mb-1">
                            Nama Periode <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="nama_periode"
                               name="nama_periode"
                               value="{{ old('nama_periode', $periode->nama_periode) }}"
                               class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-green-500 focus:border-green-500 @error('nama_periode') border-red-500 @enderror"
                               required>
                        @error('nama_periode')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Status Periode --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Status Periode <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="relative flex p-3 border rounded-lg cursor-pointer hover:bg-gray-50 focus:outline-none">
                                <input type="radio" name="status" value="draft"
                                       class="mt-0.5 h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500"
                                       {{ old('status', $periode->status) === 'draft' ? 'checked' : '' }}>
                                <span class="ml-3 flex flex-col">
                                    <span class="block text-sm font-medium text-gray-900">Draft</span>
                                    <span class="block text-xs text-gray-500">Periode belum dibuka untuk pengunggahan modul.</span>
                                </span>
                            </label>

                            <label class="relative flex p-3 border rounded-lg cursor-pointer hover:bg-gray-50 focus:outline-none">
                                <input type="radio" name="status" value="active"
                                       class="mt-0.5 h-4 w-4 text-green-600 border-gray-300 focus:ring-green-500"
                                       {{ old('status', $periode->status) === 'active' ? 'checked' : '' }}>
                                <span class="ml-3 flex flex-col">
                                    <span class="block text-sm font-medium text-gray-900">Aktif</span>
                                    <span class="block text-xs text-gray-500">Periode berjalan (hanya 1 periode boleh aktif).</span>
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
                                   value="{{ old('tanggal_mulai', $periode->tanggal_mulai ? $periode->tanggal_mulai->format('Y-m-d') : '') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-green-500 focus:border-green-500 @error('tanggal_mulai') border-red-500 @enderror"
                                   required>
                            @error('tanggal_mulai')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tanggal_selesai" class="block text-sm font-medium text-gray-700 mb-1">
                                Tanggal Selesai <span class="text-red-500">*</span>
                            </label>
                            <input type="date"
                                   id="tanggal_selesai"
                                   name="tanggal_selesai"
                                   value="{{ old('tanggal_selesai', $periode->tanggal_selesai ? $periode->tanggal_selesai->format('Y-m-d') : '') }}"
                                   class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-green-500 focus:border-green-500 @error('tanggal_selesai') border-red-500 @enderror"
                                   required>
                            @error('tanggal_selesai')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Deskripsi --}}
                    <div>
                        <label for="deskripsi" class="block text-sm font-medium text-gray-700 mb-1">
                            Deskripsi
                        </label>
                        <textarea id="deskripsi"
                                  name="deskripsi"
                                  rows="3"
                                  class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:ring-green-500 focus:border-green-500 @error('deskripsi') border-red-500 @enderror">{{ old('deskripsi', $periode->deskripsi) }}</textarea>
                        @error('deskripsi')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t">
                        <a href="{{ route('admin.periode.show', $periode) }}"
                           class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            Batal
                        </a>
                        <button type="submit"
                                class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-md shadow-sm transition">
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
@endsection
