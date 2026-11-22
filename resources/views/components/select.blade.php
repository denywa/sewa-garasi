{{-- Satu pilihan dropdown: label, select, keterangan (opsional), dan pesan error.
     Contoh: <x-select name="rental_type" label="Tipe sewa" :options="['daily' => 'Harian', 'monthly' => 'Bulanan']" /> --}}
@props(['name', 'label', 'options', 'hint' => null])

<div>
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->class(['form-control', 'border-red-500' => $errors->has($name)]) }}>
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected(old($name) === (string) $value)>{{ $text }}</option>
        @endforeach
    </select>
    @if ($hint)
        <p id="{{ $name }}-hint" class="form-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="form-error">{{ $message }}</p>
    @enderror
</div>
