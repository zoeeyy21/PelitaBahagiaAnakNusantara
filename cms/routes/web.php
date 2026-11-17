<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\ContactSettingController as AdminContactSettingController;
use App\Http\Controllers\Admin\DonationController as AdminDonationController;
use App\Http\Controllers\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Admin\StatisticController as AdminStatisticController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\ProgramController;
use App\Models\Activity;
use App\Models\Donation;
use App\Models\GalleryItem;
use App\Models\Program;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'))->name('home');
Route::view('tentang-kami', 'public.about')->name('about');
Route::get('program', [ProgramController::class, 'index'])->name('programs.index');
Route::get('program/{program}', [ProgramController::class, 'show'])->name('programs.show');
Route::get('kegiatan', [ActivityController::class, 'index'])->name('activities.index');
Route::get('kegiatan/{activity}', [ActivityController::class, 'show'])->name('activities.show');
Route::get('galeri', function () {
    return view('public.gallery', [
        'galleryItems' => GalleryItem::query()
            ->with(['program', 'activity'])
            ->published()
            ->latest()
            ->paginate(12),
    ]);
})->name('gallery');
Route::view('kontak', 'public.contact')->name('contact');
Route::get('donasi', [DonationController::class, 'create'])->name('donations.create');
Route::post('donasi', [DonationController::class, 'store'])->name('donations.store');
Route::get('donasi/{donation}', [DonationController::class, 'show'])->name('donations.show');
Route::get('donasi/{donation}/check-status', [DonationController::class, 'checkStatus'])->name('donations.check-status');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', function () {
        return view('dashboard', [
            'programCount' => Program::count(),
            'activityCount' => Activity::count(),
            'donationCount' => Donation::count(),
        ]);
    })->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::resource('programs', AdminProgramController::class);
        Route::resource('activities', AdminActivityController::class);
        Route::get('statistics', [AdminStatisticController::class, 'index'])->name('statistics.index');
        Route::get('contact', [AdminContactSettingController::class, 'edit'])->name('contact.edit');
        Route::put('contact', [AdminContactSettingController::class, 'update'])->name('contact.update');
        Route::get('donations/export', [AdminDonationController::class, 'export'])->name('donations.export');
        Route::post('donations/{donation}/sync-midtrans', [AdminDonationController::class, 'syncMidtrans'])->name('donations.sync-midtrans');
        Route::resource('donations', AdminDonationController::class)->only(['index', 'show', 'update', 'destroy']);
    });
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
