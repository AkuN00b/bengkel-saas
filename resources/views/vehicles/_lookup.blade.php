<form method="GET" action="{{ route('vehicles.lookup') }}" class="space-y-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
    <label for="lookup-plate" class="block font-semibold text-emerald-950">Cari kendaraan dari pelat</label>
    <p class="text-sm text-emerald-900">Sudah pernah datang? Masukkan nomor pelat lengkap untuk membuka kendaraan.</p>
    <div class="flex flex-col gap-3 sm:flex-row">
        <input id="lookup-plate" name="plate_number" type="text" value="{{ is_string(old('plate_number')) ? old('plate_number') : ($plate ?? '') }}" maxlength="20" placeholder="Contoh: B 1234 ABC" required autocapitalize="characters" class="auth-input min-w-0 flex-1" @error('plate_number') aria-invalid="true" aria-describedby="lookup-error" @enderror>
        <button type="submit" class="auth-button w-auto">Cari pelat</button>
    </div>
    @error('plate_number') <p id="lookup-error" role="alert" class="text-sm text-red-700">{{ $message }}</p> @enderror
</form>
