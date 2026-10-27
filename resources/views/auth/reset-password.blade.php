<x-auth-layout title="Atur ulang kata sandi" description="Masukkan email akun dan kata sandi baru Anda.">
    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        @error('token')
            <p role="alert" class="text-sm text-red-700">{{ $message }}</p>
        @enderror
        <x-auth-field name="email" label="Email" type="email" :value="is_string($email) ? $email : ''" autocomplete="username" maxlength="254" required />
        <p id="password-help" class="text-sm text-slate-600">Gunakan minimal 12 karakter. Hindari kata sandi yang mudah ditebak.</p>
        <x-auth-field name="password" label="Kata sandi baru" type="password" autocomplete="new-password" minlength="12" aria-describedby="password-help" required autofocus />
        <x-auth-field name="password_confirmation" label="Ulangi kata sandi baru" type="password" autocomplete="new-password" required />
        <button type="submit" class="auth-button">Simpan kata sandi baru</button>
    </form>
    <p class="mt-6 text-center text-sm"><a class="auth-link" href="{{ route('password.request') }}">Minta tautan reset baru</a></p>
</x-auth-layout>
