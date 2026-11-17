<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CmsClient
{
    /**
     * Dapatkan Base URL API CMS dari konfigurasi atau .env.
     */
    public static function apiUrl(): string
    {
        return rtrim(env('CMS_API_URL', 'http://127.0.0.1:8000/api'), '/');
    }

    /**
     * Dapatkan Base URL Aset Gambar dari CMS.
     */
    public static function assetUrl(?string $path): string
    {
        if (empty($path)) {
            return asset('images/logo.png');
        }

        // Jika URL eksternal (Unsplash, Cloudinary, dll)
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $baseAsset = rtrim(env('CMS_ASSET_URL', 'http://127.0.0.1:8000/storage'), '/');
        $cleanPath = ltrim($path, '/');

        // Jika diawali "storage/", buang agar tidak double
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        }

        // Cek apakah file fisik ada di public lokal frontend
        if (file_exists(public_path('storage/' . $cleanPath))) {
            return asset('storage/' . $cleanPath);
        }

        return $baseAsset . '/' . $cleanPath;
    }

    /**
     * Panggil endpoint GET dengan caching dan fallback otomatis.
     */
    public static function get(string $endpoint, string $cacheKey, callable $fallback, int $ttl = 120): mixed
    {
        // Jika CMS_API_URL dikosongkan secara eksplisit, langsung pakai fallback lokal
        if (! env('CMS_API_URL') && env('CMS_API_URL') !== null && env('CMS_API_URL') === '') {
            return $fallback();
        }

        // Di mode development lokal, gunakan cache sangat singkat (5 detik) agar setiap input baru di CMS langsung tampil
        $effectiveTtl = config('app.env') === 'local' ? 5 : $ttl;

        try {
            return Cache::remember("cms_{$cacheKey}", $effectiveTtl, function () use ($endpoint, $fallback) {
                $url = self::apiUrl() . '/' . ltrim($endpoint, '/');
                $response = Http::timeout(2.5)->acceptJson()->get($url);

                if ($response->successful()) {
                    $json = $response->json();
                    if (array_key_exists('data', $json)) {
                        return $json['data'];
                    }
                }

                return $fallback();
            });
        } catch (Throwable $e) {
            Log::info("CMS API [{$endpoint}] unreachable: " . $e->getMessage() . " — menggunakan fallback lokal.");
            return $fallback();
        }
    }

    /**
     * Kirim data donasi ke CMS API.
     */
    public static function submitDonation(array $payload): array
    {
        try {
            $url = self::apiUrl() . '/donations';
            $response = Http::timeout(5)->acceptJson()->post($url, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'success' => false,
                'message' => $response->json('message') ?? 'Gagal memproses donasi ke CMS.',
                'errors' => $response->json('errors') ?? [],
            ];
        } catch (Throwable $e) {
            Log::warning('Submit donasi ke CMS error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Layanan CMS sedang tidak dapat dihubungi. Donasi disimpan secara lokal.',
                'fallback' => true,
            ];
        }
    }

    /**
     * Cek status donasi berdasarkan reference code.
     */
    public static function checkDonationStatus(string $referenceCode): ?array
    {
        try {
            $url = self::apiUrl() . "/donations/{$referenceCode}/status";
            $response = Http::timeout(3)->acceptJson()->get($url);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (Throwable $e) {
            Log::warning("Cek status donasi {$referenceCode} gagal: " . $e->getMessage());
        }

        return null;
    }
}

