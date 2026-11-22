@extends('layouts.app')

@section('title', 'Beranda Admin')

@section('content')
    <p>Selamat datang, {{ auth()->user()->name }}. Pilih menu di atas untuk mengelola garasi dan penyewa.</p>
@endsection
