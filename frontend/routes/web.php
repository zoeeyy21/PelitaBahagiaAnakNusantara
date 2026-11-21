<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

View::composer('*', function ($view) {
    $view->with('site', cciSite());
});

/*
|--------------------------------------------------------------------------
| FRONTEND ROUTES — Yayasan Pelita Bahagia Anak Nusantara
|--------------------------------------------------------------------------
| Scope: tampilan (resources) + rute saja.
*/

Route::get('/', fn () => view('home', [
    'programs'   => cciPrograms(),
    'activities' => cciActivities(),
    'campaigns'  => cciCampaigns(),
]))->name('home');

Route::get('/tentang', fn () => view('tentang'))->name('tentang');

Route::get('/program', fn () => view('program.index', [
    'programs' => cciPrograms(),
]))->name('program');

Route::get('/program/{slug}', function (string $slug) {
    $program = collect(cciPrograms())->firstWhere('slug', $slug);

    abort_unless($program, 404);

    // Ambil semua kegiatan terkait yang terafiliasi dengan program ini
    $relatedActivities = collect(cciActivities())
        ->filter(function ($activity) use ($program) {
            $progName = strtolower(trim($program->name ?? ''));
            $actProgName = strtolower(trim($activity->program_name ?? ''));
            if ($progName === '' || $actProgName === '') {
                return false;
            }
            return $actProgName === $progName ||
                   str_contains($actProgName, $progName) ||
                   str_contains($progName, $actProgName);
        })
        ->values()
        ->all();

    return view('program.show', [
        'program'           => $program,
        'relatedActivities' => $relatedActivities,
    ]);
})->name('program.show');

Route::get('/kegiatan', fn () => view('kegiatan.index', [
    'activities' => cciActivities(),
]))->name('kegiatan');

Route::get('/kegiatan/{slug}', function (string $slug) {
    $activity = collect(cciActivities())->firstWhere('slug', $slug);

    abort_unless($activity, 404);

    return view('kegiatan.show', ['activity' => $activity]);
})->name('kegiatan.show');

// Redirect /galeri ke /kegiatan karena foto galeri sudah disatukan ke detail kegiatan
Route::redirect('/galeri', '/kegiatan');


Route::get('/kontak', fn () => view('kontak', [
    'faqs' => \App\Services\CmsClient::get('faqs', 'faqs', fn () => cciData('faqs'), 300),
]))->name('kontak');

/*
 * Donasi — jelajah kampanye.
 * Catatan: '/donasi/form' WAJIB didaftarkan sebelum '/donasi/{slug}',
 * agar tidak tertangkap sebagai slug kampanye.
 */
Route::get('/donasi', fn () => view('donasi.index', [
    'campaigns'  => cciCampaigns(),
    'categories' => cciCampaignCategories(),
]))->name('donasi');

Route::get('/donasi/form', fn () => view('donasi.form', [
    'campaigns' => cciCampaigns(),
]))->name('donasi.form');

Route::post('/donasi', function (\Illuminate\Http\Request $request) {
    $payload = array_merge($request->all(), [
        'payment_method' => $request->input('payment_method', 'qris'),
        'status'         => 'paid',
    ]);
    $result = \App\Services\CmsClient::submitDonation($payload);

    if ($request->wantsJson() || $request->ajax()) {
        return response()->json($result, ($result['success'] ?? true) ? 200 : 422);
    }

    if (! empty($result['snap_token'])) {
        session()->flash('snap_token', $result['snap_token']);
    }

    if (! empty($result['reference_code'])) {
        session()->flash('reference_code', $result['reference_code']);
    }

    return back()->with('success', $result['message'] ?? 'Terima kasih! Donasi Anda telah kami terima.');
})->name('donasi.store');

Route::get('/donasi/{slug}', function (string $slug) {
    $campaigns = cciCampaigns();
    $campaign  = collect($campaigns)->firstWhere('slug', $slug);

    if (! $campaign) {
        $single = \App\Services\CmsClient::get("campaigns/{$slug}", "campaign_{$slug}", fn () => null, 120);
        if ($single && is_array($single)) {
            $campaign = cciCampaign($single);
        }
    }

    if (! $campaign) {
        $program = collect(cciPrograms())->firstWhere('slug', $slug);
        if ($program) {
            if (strtolower($program->status ?? 'aktif') !== 'aktif' || empty($program->accepts_donation)) {
                return redirect()->route('program.show', $slug);
            }

            $catKey = strtolower($program->category ?? 'pendidikan');
            if (! array_key_exists($catKey, cciCampaignCategories())) {
                $catKey = 'pendidikan';
            }

            $campaign = cciCampaign([
                'slug'         => $program->slug,
                'title'        => 'Dukung Program ' . $program->name,
                'summary'      => $program->summary,
                'category'     => $catKey,
                'organization' => 'Yayasan Pelita Bahagia',
                'location'     => 'Pelosok Nusantara',
                'raised'       => (int) ($program->collected_amount ?? 0),
                'target'       => (int) ($program->donation_target ?? 0),
                'donors'       => 0,
                'days_left'    => $program->days_left ?? null,
                'urgent'       => false,
                'verified'     => true,
                'image'        => $program->image ?? null,
                'story'        => array_values(array_unique(array_filter([
                    strip_tags($program->description ?? ''),
                    'Setiap donasi yang Anda salurkan akan dialokasikan langsung untuk operasional dan pemenuhan kebutuhan anak-anak penerima manfaat pada program ini.',
                ]))),
            ]);
        }
    }

    abort_unless($campaign, 404);

    $others = collect($campaigns)
        ->where('slug', '!=', $slug)
        ->sortByDesc(fn ($c) => $c['urgent'])
        ->take(3)
        ->values()
        ->all();

    return view('donasi.show', [
        'campaign'   => $campaign,
        'others'     => $others,
        'categories' => cciCampaignCategories(),
    ]);
})->name('donasi.show');

if (app()->environment('local')) {
    Route::get('/preview-error/{code}', function (int $code) {
        abort_unless(in_array($code, [403, 404, 419, 500]), 404);
        return response()->view("errors.{$code}", [], $code);
    });
}


