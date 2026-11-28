<x-guest-with-header-footer>
    <div class="w-full max-w-md mx-auto">
        <!-- Header Section -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Masuk ke SiPeMo</h1>
            <p class="text-gray-600">Silakan masuk ke akun Anda untuk melanjutkan</p>
        </div>

        <!-- Login Form Card -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden" x-data="{ mode: '{{ $errors->has('kampus_username') ? 'kampus' : 'lokal' }}' }">

            <!-- Form Body -->
            <div class="p-8">
                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <!-- === FORM LOGIN LOKAL === -->
                <div x-show="mode === 'lokal'">
                    <form method="POST" action="{{ route('login') }}" class="space-y-5">
                        @csrf

                        <!-- Email -->
                        <div class="space-y-2">
                            <x-input-label for="email" :value="__('Email')" class="text-sm font-medium text-gray-700" />
                            <div class="relative">
                                <x-text-input id="email" class="w-full pl-10" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>

                        <!-- Password -->
                        <div class="space-y-2">
                            <x-input-label for="password" :value="__('Password')" class="text-sm font-medium text-gray-700" />
                            <div class="relative">
                                <x-text-input id="password" class="w-full pl-10" type="password" name="password" required autocomplete="current-password" />
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-1" />
                        </div>

                        <!-- Remember & Forgot -->
                        <div class="flex items-center justify-between">
                            <label for="remember_me" class="inline-flex items-center">
                                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500" name="remember">
                                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                            </label>
                            @if (Route::has('password.request'))
                                <a class="text-sm text-green-600 hover:text-green-700 underline" href="{{ route('password.request') }}">
                                    {{ __('Forgot your password?') }}
                                </a>
                            @endif
                        </div>

                        <!-- Submit -->
                        <div class="pt-2">
                            <x-primary-button class="w-full bg-gradient-to-r from-green-600 to-emerald-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 shadow-lg">
                                <div class="flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    {{ __('Log in') }}
                                </div>
                            </x-primary-button>
                        </div>

                        <!-- Switch ke Login Kampus -->
                        <div class="text-center pt-1">
                            <button type="button" @click="mode = 'kampus'" class="text-sm text-green-600 hover:text-green-700 underline">
                                Login menggunakan akun portal kampus
                            </button>
                        </div>
                    </form>
                </div>

                <!-- === FORM LOGIN KAMPUS === -->
                <div x-show="mode === 'kampus'" x-cloak>
                    <form method="POST" action="{{ route('login.kampus') }}" class="space-y-5">
                        @csrf

                        <!-- Username Kampus -->
                        <div class="space-y-2">
                            <x-input-label for="kampus_username" value="Username Portal (NIP / NIDN)" class="text-sm font-medium text-gray-700" />
                            <div class="relative">
                                <x-text-input
                                    id="kampus_username"
                                    class="w-full pl-10"
                                    type="text"
                                    name="kampus_username"
                                    :value="old('kampus_username')"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="Contoh: 198501012010011001"
                                />
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                    </svg>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('kampus_username')" class="mt-1" />
                        </div>

                        <!-- Password Kampus -->
                        <div class="space-y-2">
                            <x-input-label for="kampus_password" value="Password Portal" class="text-sm font-medium text-gray-700" />
                            <div class="relative">
                                <x-text-input
                                    id="kampus_password"
                                    class="w-full pl-10"
                                    type="password"
                                    name="kampus_password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Password portal UIN SSC"
                                />
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('kampus_password')" class="mt-1" />
                        </div>

                        <!-- Remember -->
                        <div class="flex items-center">
                            <input id="remember_me_kampus" type="checkbox" class="rounded border-gray-300 text-green-600 shadow-sm focus:ring-green-500" name="remember">
                            <label for="remember_me_kampus" class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</label>
                        </div>

                        <!-- Submit -->
                        <div class="pt-2">
                            <x-primary-button class="w-full bg-gradient-to-r from-green-600 to-emerald-600 text-white font-semibold py-3 px-6 rounded-lg hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 shadow-lg">
                                <div class="flex items-center justify-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    Masuk via Portal Kampus
                                </div>
                            </x-primary-button>
                        </div>

                        <!-- Kembali ke login biasa -->
                        <div class="text-center pt-1">
                            <button type="button" @click="mode = 'lokal'" class="text-sm text-green-600 hover:text-green-700 underline">
                                ← Kembali ke login akun biasa
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 text-center">
                <p class="text-sm text-gray-600">
                    Belum punya akun?
                    <a href="{{ route('penyusun.apply.create') }}" class="text-green-600 hover:text-green-700 font-medium underline">
                        Daftar sebagai Penyusun
                    </a>
                </p>
            </div>
        </div>
    </div>
</x-guest-with-header-footer>
