<x-auth-layout title="Lupa kata sandi" description="Masukkan email akun Anda untuk meminta tautan pengaturan ulang kata sandi.">
    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf
        <x-auth-field name="email" label="Email" type="email" autocomplete="username" maxlength="254" required autofocus />
        <button type="submit" class="auth-button">Kirim tautan reset</button>
    </form>
    <p class="mt-6 text-center text-sm"><a class="auth-link" href="{{ route('login') }}">Kembali ke halaman masuk</a></p>
</x-auth-layout>
