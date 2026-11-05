<x-auth-layout title="Akun Anda">
    <p class="mt-5 leading-relaxed text-slate-600">Selamat datang, {{ auth()->user()->name }}. Anda sudah masuk ke BengkelOS.</p>
    <p class="mt-4 text-sm text-slate-600">Bengkel aktif</p>
    <p class="mt-1 font-semibold">{{ $activeWorkshop->name }}</p>
    <a class="auth-link mt-4 inline-block" href="{{ route('workshops.select') }}">Pilih bengkel lain</a>
    <form method="POST" action="{{ route('logout') }}" class="mt-6">
        @csrf
        <button type="submit" class="auth-button">Keluar</button>
    </form>
</x-auth-layout>
