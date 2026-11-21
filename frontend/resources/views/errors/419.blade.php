@extends('errors.layout')

@section('title', 'Sesi Halaman Berakhir')
@section('code', '419')
@section('icon')
  <svg class="w-6 h-6 sm:w-7 sm:h-7 text-sun-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
  </svg>
@endsection
@section('message', 'Halaman telah dibuka terlalu lama sehingga sesi telah kedaluwarsa demi keamanan. Silakan muat ulang atau kembali ke beranda.')
