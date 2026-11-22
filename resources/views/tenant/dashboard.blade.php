@extends('layouts.app')

@section('title', 'Beranda Penyewa')

@section('content')
    {{-- Kartu menu (Sewa Garasi, dst.) ditambahkan bersama fiturnya di task berikutnya. --}}
    <p class="text-gray-600">Selamat datang, {{ auth()->user()->name }}.</p>
@endsection
