@extends('layouts.app')

@section('content')
<section class="min-h-[72vh] flex items-center justify-center py-16 sm:py-24 px-4 sm:px-6 relative overflow-hidden bg-gradient-to-b from-paper-warm via-paper to-paper-warm text-center">
  {{-- Glow Background Accents --}}
  <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[350px] sm:w-[600px] h-[350px] sm:h-[600px] bg-gradient-to-tr from-primary-400/20 via-sun-400/20 to-forest-400/15 blur-3xl rounded-full pointer-events-none -z-10"></div>

  <div class="max-w-xl mx-auto relative z-10 flex flex-col items-center">
    
    {{-- Badge Kode Status --}}
    <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-tint-primary text-accent-primary border border-tint-primary-line text-xs font-bold font-mono shadow-warm-sm mb-4">
      <span class="w-2 h-2 rounded-full bg-primary-500 animate-pulse"></span>
      <span>Status: @yield('code', '404')</span>
    </div>

    {{-- Big Visual Accent --}}
    <div class="relative mb-5 select-none">
      <span class="font-serif text-7xl sm:text-9xl font-extrabold tracking-tighter text-transparent bg-clip-text bg-gradient-to-b from-primary-600 via-primary-700 to-forest-900 opacity-90 drop-shadow-sm">
        @yield('code', '404')
      </span>
      <div class="absolute -bottom-2 -right-2 sm:-bottom-3 sm:-right-3 w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-white/90 dark:bg-surface/90 border border-line shadow-warm flex items-center justify-center text-accent-primary backdrop-blur-md">
        @yield('icon')
      </div>
    </div>

    {{-- Judul & Deskripsi --}}
    <h1 class="font-serif text-2xl sm:text-4xl font-bold text-ink leading-tight">
      @yield('title', 'Halaman Tidak Ditemukan')
    </h1>
    <p class="mt-3 sm:mt-4 text-xs sm:text-sm md:text-base text-ink-muted leading-relaxed max-w-md mx-auto">
      @yield('message', 'Halaman yang Anda tuju tidak ditemukan atau telah dipindahkan.')
    </p>

    {{-- Tombol Aksi Utama --}}
    <div class="mt-8 flex items-center justify-center gap-3 sm:gap-4 flex-wrap w-full">
      <a href="{{ url('/') }}" class="btn-pill btn-primary !py-3 !px-6 text-xs sm:text-sm font-bold shadow-warm-lg flex items-center justify-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        <span>Kembali ke Beranda</span>
      </a>

      <a href="{{ url('/program') }}" class="btn-pill btn-outline !py-3 !px-6 text-xs sm:text-sm font-bold flex items-center justify-center gap-1.5">
        <span>Jelajahi Program</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </a>
    </div>

    {{-- Bantuan Kontak --}}
    <div class="mt-10 pt-6 border-t border-line-soft w-full text-center">
      <p class="text-xs text-ink-muted">
        Ada kendala saat berdonasi atau mengunjungi situs? 
        <a href="{{ url('/kontak') }}" class="text-accent-primary font-bold hover:underline ml-1">
          Hubungi Kami
        </a>
      </p>
    </div>

  </div>
</section>
@endsection
