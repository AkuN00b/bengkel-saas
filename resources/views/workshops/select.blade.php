<x-app-layout title="Pilih bengkel">
    <h1 class="text-3xl font-bold tracking-tight">Pilih bengkel</h1>
    <p class="mt-3 text-sm text-slate-500">Pilih bengkel yang ingin Anda gunakan.</p>
    <div class="mt-8 grid gap-4 sm:grid-cols-2">
        @forelse ($workshops as $workshop)
            <form method="POST" action="{{ route('workshops.activate') }}" class="min-w-0 rounded-2xl border border-slate-200 bg-white p-6">
                @csrf
                <input type="hidden" name="workshop_id" value="{{ $workshop->id }}">
                <p class="mb-5 break-words text-lg font-semibold">{{ $workshop->name }}</p>
                <button type="submit" class="auth-button break-words">Gunakan {{ $workshop->name }}</button>
            </form>
        @empty
            <p class="rounded-2xl border border-slate-200 bg-white p-6 text-sm leading-relaxed text-slate-600 sm:col-span-2">Anda belum tergabung dalam bengkel. Hubungi pemilik bengkel atau pengelola akun untuk mendapatkan akses.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $workshops->links() }}</div>
    @error('workshop_id')
        <p role="alert" class="mt-4 text-sm text-red-700">{{ $message }}</p>
    @enderror
    <form method="POST" action="{{ route('logout') }}" class="mt-6 max-w-xs">
        @csrf
        <button type="submit" class="auth-button">Keluar</button>
    </form>
</x-app-layout>
