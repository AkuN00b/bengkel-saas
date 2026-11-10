<form method="POST" action="{{ route('spare-parts.movements',$record) }}" class="mt-4 space-y-3 rounded-xl border border-slate-200 p-4">
    @csrf <input name="workshop_context" type="hidden" value="{{ $activeWorkshop->id }}"><input name="version" type="hidden" value="{{ $record->version }}">
    <input name="operation_key" type="hidden" value="{{ \Illuminate\Support\Str::uuid() }}"><input name="type" type="hidden" value="{{ $type }}">
    <label class="block text-sm">Jumlah {{ $record->unit }}<input name="quantity" type="number" min="1" max="2147483647" step="1" required class="auth-input mt-2"></label>
    <label class="block text-sm">{{ $type === 'restock' ? 'Catatan (opsional)' : 'Alasan (wajib)' }}<textarea name="reason" maxlength="1000" rows="2" @required($type !== 'restock') class="auth-input mt-2"></textarea></label>
    <button class="auth-button w-auto">{{ $label }}</button>
</form>
