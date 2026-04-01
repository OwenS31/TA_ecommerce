@extends('layouts.admin')

@section('title', 'Pengaturan - CV. Tri Jaya')
@section('page-title', 'Pengaturan')

@section('content')
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Profil Admin</h2>
                <form method="POST" action="{{ route('admin.settings.update-profile') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama</label>
                            <input type="text" id="name" name="name" value="{{ old('name', $admin->name) }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            @error('name')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $admin->email) }}"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            @error('email')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">No. Telepon</label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', $admin->phone) }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @error('phone')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                        Simpan Profil
                    </button>
                </form>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Pengaturan Informasi Toko</h2>
                <form method="POST" action="{{ route('admin.settings.update-store') }}" class="space-y-4">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="store_name" class="block text-sm font-medium text-gray-700 mb-1">Nama Toko</label>
                        <input type="text" id="store_name" name="store_name"
                            value="{{ old('store_name', $storeSetting->store_name) }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @error('store_name')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="store_address" class="block text-sm font-medium text-gray-700 mb-1">Alamat Toko</label>
                        <textarea id="store_address" name="store_address" rows="4"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('store_address', $storeSetting->store_address) }}</textarea>
                        @error('store_address')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="store_whatsapp" class="block text-sm font-medium text-gray-700 mb-1">WhatsApp
                            Kontak</label>
                        <input type="text" id="store_whatsapp" name="store_whatsapp"
                            value="{{ old('store_whatsapp', $storeSetting->store_whatsapp) }}"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        @error('store_whatsapp')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                        Simpan Informasi Toko
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 h-fit">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Ubah Password</h2>
            <form method="POST" action="{{ route('admin.settings.update-password') }}" class="space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Password Saat
                        Ini</label>
                    <input type="password" id="current_password" name="current_password"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @error('current_password')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password Baru</label>
                    <input type="password" id="password" name="password"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @error('password')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi
                        Password Baru</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <button type="submit" class="w-full px-4 py-2 bg-gray-900 text-white rounded-lg text-sm hover:bg-gray-800">
                    Update Password
                </button>
            </form>
        </div>
    </div>
@endsection
