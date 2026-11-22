@extends('layouts.app')

@section('title', 'Beranda Penyewa')

@section('content')
    <p>Selamat datang, {{ auth()->user()->name }}. Pilih menu di atas untuk menyewa garasi.</p>
@endsection
