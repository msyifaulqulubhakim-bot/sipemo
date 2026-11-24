<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Selamat Datang -->
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-2xl p-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Selamat datang, {{ auth()->user()->name }}!</h2>
                        @if (auth()->user()->nip)
                            <p class="text-sm text-gray-600 mt-0.5">NIP: <span class="font-medium">{{ auth()->user()->nip }}</span></p>
                        @endif
                        @if (auth()->user()->nidn)
                            <p class="text-sm text-gray-600">NIDN: <span class="font-medium">{{ auth()->user()->nidn }}</span></p>
                        @endif
                        @if (auth()->user()->auth_source === 'kampus_api')
                            <span class="inline-flex items-center gap-1 mt-1 text-xs text-green-700 bg-green-100 rounded-full px-2.5 py-0.5 font-medium">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Login via Portal Kampus UIN SSC
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Belum memiliki role aktif di SIPeMo -->
            @if (!auth()->user()->is_admin && !auth()->user()->is_penyusun && !auth()->user()->is_lpm && !auth()->user()->is_reviewer)
                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="bg-amber-50 border-b border-amber-100 px-6 py-4 flex items-center gap-3">
                        <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="font-semibold text-amber-800">Akun Anda belum memiliki peran aktif di SIPeMo</h3>
                    </div>
                    <div class="px-6 py-5">
                        <p class="text-gray-600 text-sm mb-5">
                            Sebagai dosen UIN SSC, Anda dapat mengajukan pendaftaran sebagai <strong>Penyusun Modul</strong>.
                            Data NIP, Nama, dan Email Anda akan terisi otomatis dari data kampus.
                        </p>

                        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 mb-5 space-y-2">
                            <p class="text-xs text-gray-500 font-medium uppercase tracking-wider mb-2">Data Identitas Kampus Anda</p>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Nama Lengkap</span>
                                <span class="font-medium text-gray-900">{{ auth()->user()->name }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Email</span>
                                <span class="font-medium text-gray-900">{{ auth()->user()->email }}</span>
                            </div>
                            @if (auth()->user()->nip)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">NIP</span>
                                <span class="font-medium text-gray-900">{{ auth()->user()->nip }}</span>
                            </div>
                            @endif
                            @if (auth()->user()->nidn)
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">NIDN</span>
                                <span class="font-medium text-gray-900">{{ auth()->user()->nidn }}</span>
                            </div>
                            @endif
                        </div>

                        <a href="{{ route('penyusun.apply.create') }}"
                           class="inline-flex items-center gap-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-semibold py-2.5 px-5 rounded-lg hover:from-green-700 hover:to-emerald-700 transition-all duration-200 shadow-sm text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Daftar sebagai Penyusun Modul
                        </a>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
