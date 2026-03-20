@extends('layouts.app')

@section('title', 'Beranda - CV. Tri Jaya')

@section('content')
    {{-- Hero Section --}}
    <section class="bg-gradient-to-br from-blue-600 to-blue-800 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="max-w-2xl">
                <h1 class="text-4xl font-bold mb-4">Terpal Berkualitas untuk Segala Kebutuhan</h1>
                <p class="text-lg text-blue-100 mb-8">CV. Tri Jaya menyediakan berbagai jenis terpal dengan kualitas terbaik.
                    Pesan sekarang dengan layanan potong sesuai ukuran yang Anda inginkan.</p>
                <a href="#produk"
                    class="inline-block bg-white text-blue-700 px-6 py-3 rounded-lg font-semibold hover:bg-blue-50 transition">
                    Lihat Produk
                </a>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center p-6">
                    <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Kualitas Terjamin</h3>
                    <p class="text-gray-600 text-sm">Terpal berkualitas tinggi dari bahan pilihan untuk ketahanan maksimal.
                    </p>
                </div>
                <div class="text-center p-6">
                    <div class="w-14 h-14 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14.121 14.121L19 19m-7-7l7-7m-7 7l-2.879 2.879M12 12L9.121 9.121m0 5.758a3 3 0 10-4.243 4.243 3 3 0 004.243-4.243z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Potong Sesuai Ukuran</h3>
                    <p class="text-gray-600 text-sm">Layanan pemotongan terpal custom sesuai kebutuhan Anda dengan algoritma
                        optimasi.</p>
                </div>
                <div class="text-center p-6">
                    <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-7 h-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Pengiriman Cepat</h3>
                    <p class="text-gray-600 text-sm">Pengiriman ke seluruh Indonesia dengan pilihan kurir terpercaya.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Products Placeholder --}}
    <section id="produk" class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-8">Produk Kami</h2>
            <div class="text-center text-gray-500 py-12">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <p>Produk akan segera ditampilkan di sini.</p>
            </div>
        </div>
    </section>
@endsection
