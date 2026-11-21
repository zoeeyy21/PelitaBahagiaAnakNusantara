@extends('errors.layout')

@section('title', 'Terjadi Kendala Sistem')
@section('code', '500')
@section('icon')
  <svg class="w-6 h-6 sm:w-7 sm:h-7 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
  </svg>
@endsection
@section('message', 'Server kami sedang mengalami kendala sesaat saat memproses permintaan Anda. Silakan coba kembali dalam beberapa saat atau kembali ke halaman utama.')
