{{-- Satu kolom form: label, input, keterangan (opsional), dan pesan error.
     Contoh: <x-input name="email" label="Email" type="email" hint="Dipakai untuk login." /> --}}
@props(['name', 'label', 'type' => 'text', 'hint' => null])

<div>
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name) }}" @endif
        {{ $attributes->class(['form-control', 'border-red-500' => $errors->has($name)]) }}>
    @if ($hint)
        <p id="{{ $name }}-hint" class="form-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
