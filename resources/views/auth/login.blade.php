<x-auth-layout title="Masuk" description="Masuk menggunakan akun BengkelOS Anda.">
    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
        @csrf
        <x-auth-field name="email" label="Email" type="email" autocomplete="username" maxlength="254" required autofocus />
        <x-auth-field name="password" label="Kata sandi" type="password" autocomplete="current-password" required />
        <label class="flex min-h-11 items-center gap-3 text-sm">
            <input type="checkbox" name="remember" value="1" @checked(old('remember')) class="size-4 accent-emerald-700">
            Ingat saya
        </label>
        <button type="submit" class="auth-button">Masuk</button>
    </form>
    <div class="mt-6 flex flex-wrap justify-between gap-4 text-sm">
        <a class="auth-link" href="{{ route('password.request') }}">Lupa kata sandi?</a>
        <a class="auth-link" href="{{ route('register') }}">Buat akun</a>
    </div>
</x-auth-layout>
