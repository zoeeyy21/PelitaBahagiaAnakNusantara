<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Donation;
use App\Models\GalleryItem;
use App\Models\ImpactMetric;
use App\Models\Program;
use App\Models\SiteSetting;
use App\Services\MidtransService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class PublicApiController extends Controller
{
    /**
     * Data konfigurasi dan profil yayasan.
     */
    public function site(): JsonResponse
    {
        $contact = SiteSetting::contact();
        $metrics = ImpactMetric::query()->where('is_active', true)->orderBy('sort_order')->get();

        $stats = [
            'children' => 0,
            'programs' => Program::published()->count(),
            'programs_completed' => Activity::published()->count(),
            'volunteers' => 35,
            'villages' => 12,
            'founded_year' => 2020,
        ];

        foreach ($metrics as $metric) {
            $label = strtolower($metric->label);
            if (str_contains($label, 'anak')) {
                $stats['children'] = $metric->value;
            } elseif (str_contains($label, 'relawan')) {
                $stats['volunteers'] = $metric->value;
            } elseif (str_contains($label, 'desa') || str_contains($label, 'komunitas')) {
                $stats['villages'] = $metric->value;
            }
        }

        $data = [
            'name' => 'Yayasan Pelita Bahagia Anak Nusantara',
            'short_name' => 'Pelita Bahagia',
            'tagline' => 'Menyalakan Harapan, Membangun Generasi Gemilang',
            'description' => 'Lembaga nirlaba yang berfokus pada pendidikan, kesehatan, dan pemberdayaan anak di pelosok Nusantara.',
            'address' => $contact['address'] ?? 'Jakarta, Indonesia',
            'email' => $contact['email'] ?? 'halo@pelitabahagia.org',
            'phone' => $contact['whatsapp'] ?? '0812-0000-0000',
            'whatsapp' => $contact['whatsapp'] ?? '0812-0000-0000',
            'whatsapp_link' => $contact['whatsapp_url'] ?? '',
            'maps_url' => $contact['map_url'] ?? '',
            'stats' => $stats,
            'social' => [
                'instagram' => $contact['instagram'] ?? 'pelitabahagia',
                'facebook' => 'pelitabahagia',
                'youtube' => 'PelitaBahagiaTV',
                'tiktok' => 'pelitabahagia',
            ],
            'social_stats' => [
                'instagram_handle' => '@' . ($contact['instagram'] ?? 'pelitabahagia'),
                'instagram_followers' => '12.4K',
                'instagram_posts' => '180+',
                'youtube_handle' => '@PelitaBahagiaTV',
            ],
            'quote' => [
                'text' => 'Pendidikan adalah hak setiap anak. Tidak boleh ada pelita mimpi yang padam hanya karena keterbatasan ruang dan jarak.',
                'author' => 'Dewan Pembina',
                'role' => 'Yayasan Pelita Bahagia',
            ],
            'story' => [
                'heading' => 'Perjalanan Menyalakan Pelita di Pelosok Negeri',
                'paragraph_1' => 'Bermula dari kepedulian sekelompok relawan melihat keterbatasan fasilitas belajar di wilayah pedalaman, Yayasan Pelita Bahagia hadir sebagai jembatan kebaikan masyarakat Indonesia.',
                'paragraph_2' => 'Kami percaya kolaborasi adalah kunci transformasi berkelanjutan demi masa depan tunas bangsa yang lebih cerah.',
            ],
            'mission' => [
                'badge' => 'Fokus Utama',
                'quote' => 'Membangun ekosistem belajar yang ramah, sehat, dan berdaya guna.',
                'body' => 'Mengalirkan akses pendidikan layak, perbaikan gizi anak, dan pelatihan keterampilan keluarga prasejahtera.',
            ],
            'structure_image' => SiteSetting::structureImage(),
            'structure_title' => 'Struktur Organisasi',
            'structure_subtitle' => 'Susunan kepengurusan dan tata kelola Yayasan Pelita Bahagia Anak Nusantara yang berdedikasi melayani dan menjalankan amanah.',
            'structure_caption' => 'Bagan Struktur Kepengurusan & Tata Kelola',
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * Daftar program untuk frontend.
     */
    public function programs(): JsonResponse
    {
        $programs = Program::query()
            ->published()
            ->with(['galleryItems' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order')])
            ->withCollectedAmount()
            ->latest()
            ->get()
            ->map(fn (Program $p) => $this->transformProgram($p));

        return response()->json(['success' => true, 'data' => $programs]);
    }

    /**
     * Detail satu program berdasarkan slug.
     */
    public function program(string $slug): JsonResponse
    {
        $program = Program::query()
            ->published()
            ->where('slug', $slug)
            ->with(['galleryItems' => fn ($q) => $q->where('is_published', true)->orderBy('sort_order')])
            ->withCollectedAmount()
            ->first();

        if (! $program) {
            return response()->json(['success' => false, 'message' => 'Program tidak ditemukan'], 404);
        }

        return response()->json(['success' => true, 'data' => $this->transformProgram($program)]);
    }

    /**
     * Daftar kegiatan terlaksana untuk frontend.
     */
    public function activities(): JsonResponse
    {
        $activities = Activity::query()
            ->published()
            ->with(['program', 'galleryItems' => fn ($q) => $q->orderBy('sort_order')])
            ->latest('start_date')
            ->get()
            ->map(fn (Activity $a) => $this->transformActivity($a));

        return response()->json(['success' => true, 'data' => $activities]);
    }

    /**
     * Detail satu kegiatan berdasarkan slug.
     */
    public function activity(string $slug): JsonResponse
    {
        $activity = Activity::query()
            ->published()
            ->where('slug', $slug)
            ->with(['program', 'galleryItems' => fn ($q) => $q->orderBy('sort_order')])
            ->first();

        if (! $activity) {
            return response()->json(['success' => false, 'message' => 'Kegiatan tidak ditemukan'], 404);
        }

        return response()->json(['success' => true, 'data' => $this->transformActivity($activity)]);
    }

    /**
     * Daftar kampanye galang dana (program yang accepts_donation = true).
     */
    public function campaigns(): JsonResponse
    {
        $campaigns = Program::query()
            ->acceptingDonations()
            ->with(['donationPackages', 'donations' => fn ($q) => $q->where('status', 'paid')])
            ->withCollectedAmount()
            ->latest()
            ->get()
            ->map(fn (Program $p) => $this->transformCampaign($p));

        return response()->json(['success' => true, 'data' => $campaigns]);
    }

    /**
     * Detail satu kampanye galang dana.
     */
    public function campaign(string $slug): JsonResponse
    {
        $program = Program::query()
            ->acceptingDonations()
            ->where('slug', $slug)
            ->with(['donationPackages', 'donations' => fn ($q) => $q->where('status', 'paid')])
            ->withCollectedAmount()
            ->first();

        if (! $program) {
            return response()->json(['success' => false, 'message' => 'Kampanye tidak ditemukan'], 404);
        }

        return response()->json(['success' => true, 'data' => $this->transformCampaign($program)]);
    }

    /**
     * Daftar FAQ.
     */
    public function faqs(): JsonResponse
    {
        $faqs = SiteSetting::faqs();
        return response()->json(['success' => true, 'data' => $faqs]);
    }

    /**
     * Submit donasi dari frontend ke CMS.
     */
    public function donate(Request $request, MidtransService $midtransService): JsonResponse
    {
        // Cari program jika ada program_id atau slug
        $program = null;
        if ($request->filled('program_id')) {
            $program = Program::find($request->input('program_id'));
        } elseif ($request->filled('campaign') || $request->filled('program_slug')) {
            $slug = $request->input('campaign') ?: $request->input('program_slug');
            $program = Program::where('slug', $slug)->first();
        }

        $minAmount = $program?->min_donation_amount ?? 10000;

        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:' . $minAmount],
            'donor_name' => ['nullable', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'donor_email' => ['nullable', 'email', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'donor_phone' => ['nullable', 'string', 'max:30'],
            'phone' => ['nullable', 'string', 'max:30'],
            'is_anonymous' => ['nullable'],
            'message' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'package_name' => ['nullable', 'string', 'max:255'],
            'package_qty' => ['nullable', 'integer'],
        ]);

        $donorName = $validated['donor_name'] ?? $validated['name'] ?? 'Hamba Allah';
        $donorEmail = $validated['donor_email'] ?? $validated['email'] ?? null;
        $donorPhone = $validated['donor_phone'] ?? $validated['phone'] ?? '08120000000';
        $isAnonymous = filter_var($request->input('is_anonymous', false), FILTER_VALIDATE_BOOLEAN) || $donorName === 'Hamba Allah';
        $message = $validated['message'] ?? $validated['notes'] ?? null;

        if ($request->filled('package_name') && $request->filled('package_qty')) {
            $pkgNote = "[Paket: {$request->input('package_name')} x {$request->input('package_qty')}]";
            $message = $message ? $pkgNote . ' ' . $message : $pkgNote;
        }

        $paymentMethod = $request->input('payment_method') ?: 'qris';
        $statusInput = $request->input('status');

        $hasMidtransKey = (bool) (config('services.midtrans.server_key') || env('MIDTRANS_SERVER_KEY'));
        $usesMidtrans = ($paymentMethod === 'midtrans' && $hasMidtransKey);

        $status = $statusInput ?: ($usesMidtrans ? 'pending' : 'paid');
        $paidAt = $status === 'paid' ? now() : null;

        $donation = Donation::create([
            'program_id' => $program?->id,
            'reference_code' => 'YPB-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'donation_type' => $program ? 'program' : 'general',
            'amount' => (int) $validated['amount'],
            'donor_name' => $isAnonymous ? 'Hamba Allah' : $donorName,
            'donor_email' => $donorEmail,
            'donor_phone' => $donorPhone,
            'is_anonymous' => $isAnonymous,
            'message' => $message,
            'payment_method' => $paymentMethod,
            'status' => $status,
            'paid_at' => $paidAt,
        ]);

        $snapToken = null;
        $redirectUrl = null;

        // Coba generate snap transaction jika Midtrans Server Key dikonfigurasi dan metode Midtrans
        if ($usesMidtrans) {
            try {
                $snapTransaction = $midtransService->createSnapTransaction($donation);
                $snapToken = $snapTransaction->token;
                $redirectUrl = $snapTransaction->redirect_url ?? null;
                Cache::put("donation_snap_token_{$donation->reference_code}", $snapToken, now()->addDay());
            } catch (Throwable $e) {
                Log::warning('Midtrans Snap tidak aktif atau gagal: ' . $e->getMessage(), [
                    'donation_id' => $donation->id,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => $status === 'paid' ? 'Terima kasih! Donasi Anda telah berhasil kami terima.' : 'Donasi berhasil dicatat.',
            'reference_code' => $donation->reference_code,
            'snap_token' => $snapToken,
            'redirect_url' => $redirectUrl,
            'donation' => [
                'id' => $donation->id,
                'reference_code' => $donation->reference_code,
                'amount' => $donation->amount,
                'status' => $donation->status,
                'donor_name' => $donation->donor_name,
                'program' => $program?->title ?? 'Donasi Umum',
            ],
        ], 201);
    }

    /**
     * Cek status donasi berdasarkan reference_code.
     */
    public function donationStatus(string $referenceCode, MidtransService $midtransService): JsonResponse
    {
        $donation = Donation::where('reference_code', $referenceCode)->with('program')->first();

        if (! $donation) {
            return response()->json(['success' => false, 'message' => 'Donasi tidak ditemukan'], 404);
        }

        if ($donation->status === 'pending' && (config('services.midtrans.server_key') || env('MIDTRANS_SERVER_KEY'))) {
            try {
                $midtransService->syncTransactionStatus($donation);
                $donation->refresh();
            } catch (Throwable $e) {
                // Abaikan error sync bila offline
            }
        }

        return response()->json([
            'success' => true,
            'reference_code' => $donation->reference_code,
            'status' => $donation->status,
            'status_label' => match ($donation->status) {
                'paid' => 'Berhasil',
                'pending' => 'Menunggu Pembayaran',
                'failed' => 'Gagal',
                'expired' => 'Kedaluwarsa',
                default => ucfirst($donation->status),
            },
            'amount' => $donation->amount,
            'paid_at' => $donation->paid_at?->format('d M Y H:i'),
            'program_title' => $donation->program?->title ?? 'Donasi Umum',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TRANSFORMERS (Memetakan data model CMS ke bentuk yang diharapkan Frontend)
    // ─────────────────────────────────────────────────────────────────────────

    private function transformProgram(Program $p): array
    {
        $gallery = $p->galleryItems->map(fn (GalleryItem $g) => [
            'image' => $g->image_path,
            'caption' => $g->caption ?: $g->title,
        ])->all();

        $daysLeft = null;
        if ($p->end_date) {
            $endDate = Carbon::parse($p->end_date)->endOfDay();
            $diff = now()->floatDiffInDays($endDate, false);
            $daysLeft = max(0, (int) ceil($diff));
        }

        return [
            'id' => $p->id,
            'name' => $p->title,
            'slug' => $p->slug,
            'summary' => $p->summary,
            'description' => $p->description,
            'goal' => $p->goal ?: ($p->beneficiaries ? "Target: {$p->beneficiaries}" : 'Membantu anak-anak nusantara'),
            'target' => $p->beneficiaries ?: 'Anak-anak nusantara',
            'status' => $p->status === 'active' ? 'Aktif' : 'Non-Aktif',
            'category' => 'Pendidikan',
            'period' => $p->start_date ? 'Periode ' . Carbon::parse($p->start_date)->format('Y') : 'Periode 2026',
            'start_date' => $p->start_date ? Carbon::parse($p->start_date)->format('Y-m-d') : null,
            'end_date' => $p->end_date ? Carbon::parse($p->end_date)->format('Y-m-d') : null,
            'image' => $p->cover_image,
            'gallery' => $gallery,
            'accepts_donation' => (bool) $p->accepts_donation,
            'donation_target' => (int) $p->donation_target,
            'collected_amount' => (int) ($p->collected_amount ?? 0),
            'days_left' => $daysLeft,
        ];
    }

    private function transformActivity(Activity $a): array
    {
        $gallery = $a->galleryItems->map(fn (GalleryItem $g) => [
            'image' => $g->image_path,
            'caption' => $g->caption ?: $g->title,
        ])->all();

        return [
            'id' => $a->id,
            'title' => $a->title,
            'slug' => $a->slug,
            'date' => $a->start_date ? Carbon::parse($a->start_date)->format('Y-m-d') : ($a->published_at ? Carbon::parse($a->published_at)->format('Y-m-d') : now()->format('Y-m-d')),
            'location' => 'Nusantara',
            'program_name' => $a->program?->title ?? 'Program Aksi',
            'summary' => $a->excerpt ?: Str::limit(strip_tags($a->body), 160),
            'content' => $a->body,
            'cover' => $a->cover_image,
            'gallery' => $gallery,
        ];
    }

    private function transformCampaign(Program $p): array
    {
        $paidDonations = $p->donations ?? collect();
        $donorsCount = $paidDonations->count();
        $collected = (int) ($p->collected_amount ?? 0);
        $target = (int) ($p->donation_target ?? 0);

        $daysLeft = null;
        if ($p->end_date) {
            $endDate = Carbon::parse($p->end_date)->endOfDay();
            $diff = now()->floatDiffInDays($endDate, false);
            $daysLeft = max(0, (int) ceil($diff));
        }

        $packages = $p->donationPackages->map(fn ($pkg) => [
            'id' => 'pkg-' . $pkg->id,
            'name' => $pkg->name,
            'price' => (int) $pkg->amount,
            'unit' => '/ paket',
            'badge' => null,
            'description' => $pkg->description ?? 'Paket dukungan anak nusantara',
        ])->all();

        $donationType = 'nominal';
        if (! empty($packages)) {
            $donationType = $p->allowsCustomAmount() ? 'both' : 'package';
        }

        $story = array_values(array_filter([
            $p->summary,
            $p->goal ? 'Tujuan: ' . $p->goal : null,
            strip_tags($p->description),
            'Setiap donasi yang Anda salurkan akan dialokasikan langsung untuk operasional dan pemenuhan kebutuhan anak-anak penerima manfaat pada program ini.',
        ]));

        return [
            'id' => $p->id,
            'slug' => $p->slug,
            'title' => $p->title,
            'summary' => $p->summary,
            'category' => 'pendidikan',
            'organization' => 'Yayasan Pelita Bahagia Anak Nusantara',
            'location' => 'Pelosok Nusantara',
            'raised' => $collected,
            'target' => $target,
            'donors' => $donorsCount,
            'days_left' => $daysLeft,
            'urgent' => (bool) $p->is_featured,
            'verified' => true,
            'image' => $p->cover_image,
            'donation_type' => $donationType,
            'min_donation' => (int) ($p->min_donation_amount ?: 10000),
            'packages' => $packages,
            'story' => $story,
        ];
    }
}

