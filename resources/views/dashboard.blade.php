<x-auth-layout title="Akun Anda">
    <p class="mt-5 leading-relaxed text-slate-600">Selamat datang, {{ auth()->user()->name }}. Anda sudah masuk ke BengkelOS.</p>
    <form method="POST" action="{{ route('logout') }}" class="mt-6">
        @csrf
        <button type="submit" class="auth-button">Keluar</button>
    </form>
</x-auth-layout>
