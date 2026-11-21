<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

View::share('site', cciSite());

/*
|--------------------------------------------------------------------------
| FRONTEND ROUTES — Yayasan Pelita Bahagia Anak Nusantara
|--------------------------------------------------------------------------
| Scope: tampilan (resources) + rute saja. Bagian backend (controllers,
| models, migrations, seeders, integrasi payment) dikerjakan terpisah.
|
| ══════════════════════════════════════════════════════════════════════════
|  ISI DATA DI SINI (TIDAK PERLU UBAH KODE VIEW):
|    resources/data/site.php        -> identitas, alamat, kontak, statistik,
|                                     kutipan beranda, kisah yayasan, medsos
|    resources/data/programs.php    -> program
|    resources/data/activities.php  -> kegiatan / berita
|    resources/data/galleries.php   -> galeri foto
|    resources/data/campaigns.php   -> kampanye galang dana
|    resources/data/faqs.php        -> FAQ halaman kontak
|  Setiap file berisi petunjuk lengkap struktur datanya di bagian atas.
|  Selama file masih mengembalikan [ ] (kosong), halaman menampilkan
|  status kosong yang rapi — bukan error.
| ══════════════════════════════════════════════════════════════════════════
|
| Halaman donasi:
|   GET  /donasi           -> jelajah kampanye
|   GET  /donasi/form      -> formulir donasi mandiri
|   GET  /donasi/{slug}    -> detail kampanye + form donasi inline
|   POST /donasi           -> submit donasi (handler sementara)
|
| Gambar: file fisik diletakkan di storage/app/public/<folder>/
|         lalu ditulis path-nya (mis. 'programs/foto.jpg') pada file data.
|         Jalankan `php artisan storage:link` sekali agar dapat diakses.
*/

/** Muat file data; jika belum ada atau isinya kosong, kembalikan array kosong. */
function cciData(string $name): array
{
    $path = resource_path("data/{$name}.php");

    if (! is_file($path)) {
        return [];
    }

    $data = require $path;

    return is_array($data) ? $data : [];
}

/** Profil & identitas yayasan (alamat, kontak, statistik, medsos). */
function cciSite(): array
{
    $defaults = [
        'name' => 'Yayasan Pelita Bahagia Anak Nusantara',
        'short_name' => 'Pelita Bahagia',
        'tagline' => '',
        'description' => '',
        'legal_number' => '',
        'address' => '',
        'email' => '',
        'email_partner' => '',
        'email_volunteer' => '',
        'phone' => '',
        'whatsapp' => '',
        'maps_url' => '',
        'stats' => [
            'children' => 0, 'children_note' => '',
            'programs' => 0, 'programs_note' => '',
            'programs_completed' => 0, 'packages_distributed' => '',
            'villages' => 0, 'founded_year' => 0, 'volunteers' => 0,
        ],
        'social' => ['instagram' => '', 'facebook' => '', 'youtube' => '', 'tiktok' => ''],
        'social_stats' => ['instagram_handle' => '', 'instagram_followers' => '', 'instagram_posts' => '', 'youtube_handle' => ''],
        'quote' => ['text' => '', 'author' => '', 'role' => ''],
        'story' => ['heading' => '', 'paragraph_1' => '', 'paragraph_2' => ''],
        'mission' => ['badge' => '', 'quote' => '', 'body' => ''],
        'qris_image' => null,
    ];

    $data = \App\Services\CmsClient::get('site', 'site', fn () => cciData('site'), 300);
    $site = array_replace_recursive($defaults, is_array($data) ? $data : []);

    // Bersihkan tautan WhatsApp menjadi digit berawalan 62 untuk wa.me
    $waDigits = preg_replace('/\D/', '', $site['whatsapp'] ?? '');
    if (str_starts_with($waDigits, '0')) {
        $waDigits = '62' . substr($waDigits, 1);
    }
    $site['whatsapp_link'] = $waDigits ? 'https://wa.me/' . $waDigits : '';

    return $site;
}

/** Kategori kampanye beserta labelnya. */
function cciCampaignCategories(): array
{
    return [
        'pendidikan'    => 'Pendidikan',
        'kesehatan'     => 'Kesehatan & Gizi',
        'pemberdayaan'  => 'Pemberdayaan',
        'bencana'       => 'Tanggap Bencana',
    ];
}

/** Normalisasi satu data kampanye agar aman dipakai view walau beberapa field kosong. */
function cciCampaign(array $c): array
{
    $categories = cciCampaignCategories();

    $defaultPackages = [
        [
            'id'          => 'pkg-1',
            'name'        => 'Paket Peduli Anak',
            'price'       => 28000,
            'unit'        => '/ paket',
            'badge'       => 'TERMURAH',
            'description' => 'Paket bantuan kebutuhan dasar anak penerima manfaat'
        ],
        [
            'id'          => 'pkg-2',
            'name'        => 'Paket Berdaya Bersama',
            'price'       => 75000,
            'unit'        => '/ paket',
            'badge'       => 'FAVORIT',
            'description' => 'Dukungan perlengkapan dan nutrisi sehat komplit'
        ],
        [
            'id'          => 'pkg-3',
            'name'        => 'Paket Harapan Juara',
            'price'       => 150000,
            'unit'        => '/ paket',
            'badge'       => 'LENGKAP',
            'description' => 'Paket pendampingan penuh dan fasilitas berkelanjutan'
        ],
    ];

    $packages = is_array($c['packages'] ?? null) && count($c['packages']) > 0
        ? $c['packages']
        : $defaultPackages;

    $donationType = $c['donation_type'] ?? 'both'; // 'package', 'nominal', 'both'
    $minDonation = (int) ($c['min_donation'] ?? 28000);

    return array_merge([
        'slug'          => '',
        'title'         => '',
        'summary'       => '',
        'category'      => 'pendidikan',
        'organization'  => '',
        'location'      => '',
        'raised'        => 0,
        'target'        => 0,
        'donors'        => 0,
        'days_left'     => null,
        'urgent'        => false,
        'verified'      => false,
        'story'         => [],
        'image'         => null,
        'qris_image'    => null,
        'donation_type' => $donationType,
        'min_donation'  => $minDonation,
        'packages'      => $packages,
    ], $c, [
        'raised'        => (int) ($c['raised'] ?? 0),
        'target'        => (int) ($c['target'] ?? 0),
        'donors'        => (int) ($c['donors'] ?? 0),
        'days_left'     => isset($c['days_left']) && $c['days_left'] !== null ? (int) $c['days_left'] : null,
        'urgent'        => (bool) ($c['urgent'] ?? false),
        'verified'      => (bool) ($c['verified'] ?? false),
        'story'         => is_array($c['story'] ?? null) ? $c['story'] : [],
        'category'      => array_key_exists($c['category'] ?? '', $categories) ? $c['category'] : 'pendidikan',
        'donation_type' => $donationType,
        'min_donation'  => $minDonation,
        'packages'      => $packages,
    ]);
}

/** Semua kampanye yang sudah dinormalisasi. */
function cciCampaigns(): array
{
    $items = \App\Services\CmsClient::get('campaigns', 'campaigns', fn () => cciData('campaigns'), 120);
    return array_map('cciCampaign', is_array($items) ? $items : []);
}

/** Normalisasi satu program → object agar view memakai ->field. */
function cciProgram(array $p): object
{
    return (object) array_merge([
        'name'             => '',
        'slug'             => '',
        'summary'          => '',
        'description'      => '',
        'goal'             => '',
        'target'           => '',
        'status'           => 'Aktif',
        'category'         => 'Pendidikan',
        'period'           => 'Periode 2026',
        'image'            => null,
        'gallery'          => [],
        'accepts_donation' => false,
        'donation_target'  => 0,
        'collected_amount' => 0,
        'days_left'        => null,
    ], $p, [
        'accepts_donation' => (bool) ($p['accepts_donation'] ?? false),
    ]);
}

/** Normalisasi satu kegiatan → object. */
function cciActivity(array $a): object
{
    return (object) array_merge([
        'title'        => '',
        'slug'         => '',
        'date'         => null,
        'location'     => 'Nusa Tenggara Timur',
        'program_name' => 'Program Aksi',
        'summary'      => '',
        'content'      => '',
        'cover'        => null,
        'gallery'      => [],
    ], $a);
}

/** Semua program dan kegiatan dalam bentuk object. */
function cciPrograms(): array
{
    $items = \App\Services\CmsClient::get('programs', 'programs', fn () => cciData('programs'), 120);
    return array_map('cciProgram', is_array($items) ? $items : []);
}

function cciActivities(): array
{
    $items = \App\Services\CmsClient::get('activities', 'activities', fn () => cciData('activities'), 120);
    return array_map('cciActivity', is_array($items) ? $items : []);
}

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


