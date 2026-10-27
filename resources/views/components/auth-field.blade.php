@props(['name', 'label', 'type' => 'text', 'value' => null])
@php
    $fieldValue = old($name, $value);
    $describedBy = trim(($attributes->get('aria-describedby') ?? '').($errors->has($name) ? ' '.$name.'-error' : ''));
@endphp
<div>
    <label for="{{ $name }}" class="block text-sm font-medium">{{ $label }}</label>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ is_string($fieldValue) ? $fieldValue : '' }}" @endif
        @error($name) aria-invalid="true" @enderror
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        {{ $attributes->except('aria-describedby')->class(['auth-input']) }}
    >
    @error($name)
        <p id="{{ $name }}-error" role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p>
    @enderror
</div>
