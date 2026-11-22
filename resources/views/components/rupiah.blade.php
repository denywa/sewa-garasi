{{-- Format uang Rupiah tanpa desimal. Contoh: <x-rupiah :amount="150000" /> → Rp 150.000 --}}
@props(['amount'])
{{ \Illuminate\Support\Number::currency($amount, in: 'IDR', locale: 'id', precision: 0) }}
