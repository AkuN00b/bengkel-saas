<x-app-layout title="Mekanik" :active-workshop="$activeWorkshop">
    <h1 class="text-3xl font-bold">Mekanik</h1>
    <p class="mt-2 text-sm text-slate-600">Buat akun baru. Mekanik harus membuat sandi dari email sebelum bisa masuk dan ditugaskan.</p>
    @if ($errors->any()) <div role="alert" class="my-4 rounded-xl bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div> @endif
    <details class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
        <summary class="auth-link min-h-11 cursor-pointer py-2 font-semibold">Tambah mekanik</summary>
        <form method="POST" action="{{ route('mechanics.store') }}" class="mt-4 max-w-xl space-y-4">
            @csrf <input type="hidden" name="workshop_context" value="{{ $activeWorkshop->id }}">
            <x-auth-field name="name" label="Nama mekanik" maxlength="255" required />
            <x-auth-field name="email" label="Email baru mekanik" type="email" maxlength="254" required />
            <button class="auth-button w-auto">Buat akun dan kirim tautan</button>
        </form>
    </details>
    <div class="mt-6 space-y-4">
        @forelse ($mechanics as $mechanic)
            <article class="rounded-2xl border border-slate-200 bg-white p-5">
                <h2 class="break-words font-semibold">{{ $mechanic->user->name }}</h2>
                <p class="mt-1 break-all text-sm text-slate-600">{{ $mechanic->user->email }}</p>
                <p class="mt-2 text-sm">{{ $mechanic->is_active ? 'Akses aktif' : 'Akses nonaktif' }} · {{ $mechanic->user->needs_password_setup ? 'Menunggu pembuatan sandi' : 'Sandi sudah dibuat' }}</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    @if ($mechanic->is_active && $mechanic->user->needs_password_setup)
                        <form method="POST" action="{{ route('mechanics.resend', $mechanic) }}">@csrf
                            <input type="hidden" name="workshop_context" value="{{ $activeWorkshop->id }}"><input type="hidden" name="version" value="{{ $mechanic->version }}">
                            <button class="auth-link min-h-11 text-sm">Kirim ulang tautan sandi</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('mechanics.activation', $mechanic) }}">@csrf @method('PATCH')
                        <input type="hidden" name="workshop_context" value="{{ $activeWorkshop->id }}"><input type="hidden" name="version" value="{{ $mechanic->version }}">
                        <input type="hidden" name="is_active" value="{{ $mechanic->is_active ? '0' : '1' }}">
                        <button class="min-h-11 text-sm font-medium {{ $mechanic->is_active ? 'text-red-700' : 'text-emerald-800' }}">{{ $mechanic->is_active ? 'Nonaktifkan akses' : 'Aktifkan akses' }}</button>
                    </form>
                </div>
            </article>
        @empty <p class="rounded-xl bg-white p-6 text-slate-600">Belum ada akun mekanik di bengkel ini.</p> @endforelse
    </div>
    <div class="mt-6">{{ $mechanics->links() }}</div>
</x-app-layout>
