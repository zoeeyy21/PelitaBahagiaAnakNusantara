<?php

/**
 * Global helper functions for Yayasan Pelita Bahagia Frontend
 */

if (! function_exists('cciData')) {
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
}

if (! function_exists('cciSite')) {
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
}

if (! function_exists('cciCampaignCategories')) {
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
}

if (! function_exists('cciCampaign')) {
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
}

if (! function_exists('cciCampaigns')) {
    /** Semua kampanye yang sudah dinormalisasi. */
    function cciCampaigns(): array
    {
        $items = \App\Services\CmsClient::get('campaigns', 'campaigns', fn () => cciData('campaigns'), 120);
        return array_map('cciCampaign', is_array($items) ? $items : []);
    }
}

if (! function_exists('cciProgram')) {
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
}

if (! function_exists('cciActivity')) {
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
}

if (! function_exists('cciPrograms')) {
    /** Semua program dalam bentuk object. */
    function cciPrograms(): array
    {
        $items = \App\Services\CmsClient::get('programs', 'programs', fn () => cciData('programs'), 120);
        return array_map('cciProgram', is_array($items) ? $items : []);
    }
}

if (! function_exists('cciActivities')) {
    /** Semua kegiatan dalam bentuk object. */
    function cciActivities(): array
    {
        $items = \App\Services\CmsClient::get('activities', 'activities', fn () => cciData('activities'), 120);
        return array_map('cciActivity', is_array($items) ? $items : []);
    }
}
