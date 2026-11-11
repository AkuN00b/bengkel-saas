@csrf
<input name="workshop_context" type="hidden" value="{{ $activeWorkshop->id }}">
<input name="version" type="hidden" value="{{ $invoice->version }}">
<input name="operation_key" type="hidden" value="{{ \Illuminate\Support\Str::uuid() }}">
