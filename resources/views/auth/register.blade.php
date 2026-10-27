<x-auth-layout title="Buat akun" description="Daftarkan akun Anda untuk masuk ke BengkelOS.">
    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
        @csrf
        <x-auth-field name="name" label="Nama" autocomplete="name" maxlength="255" required autofocus />
        <x-auth-field name="email" label="Email" type="email" autocomplete="username" maxlength="254" required />
        <p id="password-help" class="text-sm text-slate-600">Gunakan minimal 12 karakter. Hindari kata sandi yang mudah ditebak.</p>
        <x-auth-field name="password" label="Kata sandi" type="password" autocomplete="new-password" minlength="12" aria-describedby="password-help" required />
        <x-auth-field name="password_confirmation" label="Ulangi kata sandi" type="password" autocomplete="new-password" required />
        <button type="submit" class="auth-button">Buat akun</button>
    </form>
    <p class="mt-6 text-center text-sm text-slate-600">Sudah punya akun? <a class="auth-link" href="{{ route('login') }}">Masuk</a></p>
</x-auth-layout>
