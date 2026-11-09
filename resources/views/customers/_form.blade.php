@csrf
@if ($customer->exists) @method('PUT') @endif
<x-auth-field name="name" label="Nama pelanggan" :value="$customer->name" maxlength="255" autocomplete="name" required autofocus />
<x-auth-field name="phone" label="Nomor telepon (opsional)" type="tel" :value="$customer->phone" maxlength="32" autocomplete="tel" placeholder="Contoh: 081234567890 atau +62 812-3456-7890" aria-describedby="phone-help" />
<p id="phone-help" class="text-xs leading-relaxed text-slate-500">Boleh memakai angka, spasi, +, tanda kurung, titik, atau tanda hubung.</p>
<div>
    <label for="notes" class="block text-sm font-medium">Catatan (opsional)</label>
    @php($notes = old('notes', $customer->notes))
    <textarea id="notes" name="notes" rows="5" maxlength="5000" class="auth-input mt-2 resize-y" @error('notes') aria-invalid="true" aria-describedby="notes-error" @enderror>{{ is_string($notes) ? $notes : '' }}</textarea>
    @error('notes') <p id="notes-error" role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
</div>
<div class="flex flex-col gap-3 sm:flex-row">
    <button type="submit" class="auth-button w-auto">{{ $customer->exists ? 'Simpan perubahan' : 'Simpan pelanggan' }}</button>
    <a href="{{ $customer->exists ? route('customers.show', $customer) : route('customers.index') }}" class="auth-link inline-flex min-h-11 items-center justify-center px-4">Batal</a>
</div>
