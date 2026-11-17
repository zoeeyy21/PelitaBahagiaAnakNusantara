<?php

use App\Http\Controllers\Api\PublicApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API Routes for Public Frontend
|--------------------------------------------------------------------------
|
| Rute-rute ini dikonsumsi oleh aplikasi frontend (cci-clean) untuk
| mengambil data profil yayasan, program, kegiatan, kampanye galang dana,
| dan mengirim formulir donasi.
|
*/

Route::prefix('v1')->group(function (): void {
    // 1. Profil & Pengaturan Yayasan
    Route::get('/site', [PublicApiController::class, 'site']);
    Route::get('/faqs', [PublicApiController::class, 'faqs']);

    // 2. Program Kerja
    Route::get('/programs', [PublicApiController::class, 'programs']);
    Route::get('/programs/{slug}', [PublicApiController::class, 'program']);

    // 3. Kegiatan Terlaksana / Berita
    Route::get('/activities', [PublicApiController::class, 'activities']);
    Route::get('/activities/{slug}', [PublicApiController::class, 'activity']);

    // 4. Kampanye Galang Dana (Donasi)
    Route::get('/campaigns', [PublicApiController::class, 'campaigns']);
    Route::get('/campaigns/{slug}', [PublicApiController::class, 'campaign']);

    // 5. Submit Donasi & Cek Status Midtrans
    Route::post('/donations', [PublicApiController::class, 'donate']);
    Route::get('/donations/{referenceCode}/status', [PublicApiController::class, 'donationStatus']);
});

// Alias tanpa prefix v1 untuk kemudahan integrasi default
Route::get('/site', [PublicApiController::class, 'site']);
Route::get('/faqs', [PublicApiController::class, 'faqs']);
Route::get('/programs', [PublicApiController::class, 'programs']);
Route::get('/programs/{slug}', [PublicApiController::class, 'program']);
Route::get('/activities', [PublicApiController::class, 'activities']);
Route::get('/activities/{slug}', [PublicApiController::class, 'activity']);
Route::get('/campaigns', [PublicApiController::class, 'campaigns']);
Route::get('/campaigns/{slug}', [PublicApiController::class, 'campaign']);
Route::post('/donations', [PublicApiController::class, 'donate']);
Route::get('/donations/{referenceCode}/status', [PublicApiController::class, 'donationStatus']);

