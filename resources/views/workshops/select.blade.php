<x-auth-layout title="Pilih bengkel" description="Pilih bengkel yang ingin Anda gunakan.">
    <div class="mt-6 space-y-4">
        @forelse ($workshops as $workshop)
            <form method="POST" action="{{ route('workshops.activate') }}" class="rounded-lg border border-slate-200 p-4">
                @csrf
                <input type="hidden" name="workshop_id" value="{{ $workshop->id }}">
                <p class="mb-3 font-semibold">{{ $workshop->name }}</p>
                <button type="submit" class="auth-button">Gunakan {{ $workshop->name }}</button>
            </form>
        @empty
            <p class="text-sm leading-relaxed text-slate-600">Anda belum tergabung dalam bengkel. Hubungi pemilik bengkel atau pengelola akun untuk mendapatkan akses.</p>
        @endforelse
        {{ $workshops->links() }}
    </div>
    @error('workshop_id')
        <p role="alert" class="mt-4 text-sm text-red-700">{{ $message }}</p>
    @enderror
    <form method="POST" action="{{ route('logout') }}" class="mt-6">
        @csrf
        <button type="submit" class="auth-button">Keluar</button>
    </form>
</x-auth-layout>
