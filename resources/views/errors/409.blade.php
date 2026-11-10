<x-app-layout title="Data sudah berubah" :active-workshop="request()->attributes->get('activeWorkshop')">
    <h1 class="text-2xl font-bold">Data sudah berubah</h1>
    <p role="alert" class="mt-4">{{ $exception->getMessage() ?: 'Muat ulang halaman sebelum menyimpan lagi.' }}</p>
    <a href="{{ request()->routeIs('mechanics.*') ? route('mechanics.index') : route('work-orders.index') }}" class="auth-link mt-6 inline-flex min-h-11 items-center">Muat ulang daftar</a>
</x-app-layout>
