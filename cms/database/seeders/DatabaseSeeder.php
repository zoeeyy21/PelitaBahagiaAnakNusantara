<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\GalleryItem;
use App\Models\ImpactMetric;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin Pelita Bahagia',
            'email' => env('SEED_ADMIN_EMAIL', 'admin@pelitabahagia.test'),
            'password' => Hash::make(env('SEED_ADMIN_PASSWORD', 'password')),
        ]);

        $programs = collect([
            [
                'title' => 'Beasiswa Anak Nusantara',
                'slug' => 'beasiswa-anak-nusantara',
                'summary' => 'Dukungan pendidikan untuk anak-anak agar tetap sekolah dan punya ruang belajar yang layak.',
                'description' => 'Program ini membantu kebutuhan pendidikan dasar, perlengkapan sekolah, dan pendampingan belajar untuk anak-anak penerima manfaat.',
                'goal' => 'Membantu anak tetap bersekolah dan percaya diri mengejar masa depan.',
                'beneficiaries' => 'Anak usia sekolah',
                'cover_image' => 'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'title' => 'Paket Bahagia Keluarga',
                'slug' => 'paket-bahagia-keluarga',
                'summary' => 'Bantuan kebutuhan pokok dan dukungan sosial untuk keluarga rentan.',
                'description' => 'Program ini menyalurkan paket kebutuhan harian dan membangun komunikasi rutin dengan keluarga penerima manfaat.',
                'goal' => 'Meringankan kebutuhan dasar keluarga rentan.',
                'beneficiaries' => 'Keluarga prasejahtera',
                'cover_image' => 'https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&w=900&q=80',
            ],
            [
                'title' => 'Ruang Ceria Anak',
                'slug' => 'ruang-ceria-anak',
                'summary' => 'Kegiatan bermain, membaca, dan pembentukan karakter bersama relawan.',
                'description' => 'Program ini menyediakan ruang aktivitas yang aman dan menggembirakan untuk anak-anak melalui kegiatan kreatif dan edukatif.',
                'goal' => 'Menciptakan ruang tumbuh yang positif dan menyenangkan.',
                'beneficiaries' => 'Anak komunitas dampingan',
                'cover_image' => 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=900&q=80',
            ],
        ])->map(fn (array $program) => Program::create($program + [
            'status' => 'active',
            'is_featured' => true,
            'accepts_donation' => true,
            'donation_target' => 25000000,
            'min_donation_amount' => 10000,
            'published_at' => now(),
        ]));

        Activity::create([
            'program_id' => $programs[0]->id,
            'title' => 'Kelas Belajar Akhir Pekan',
            'slug' => 'kelas-belajar-akhir-pekan',
            'excerpt' => 'Relawan mendampingi anak-anak belajar membaca, berhitung, dan membuat karya kecil.',
            'body' => 'Kegiatan kelas belajar akhir pekan berjalan dengan hangat. Anak-anak mengikuti sesi membaca, berhitung, dan membuat karya sederhana bersama relawan.',
            'cover_image' => 'https://images.unsplash.com/photo-1529390079861-591de354faf5?auto=format&fit=crop&w=900&q=80',
            'status' => 'published',
            'start_date' => now()->subDays(7),
            'published_at' => now()->subDays(6),
        ]);

        Activity::create([
            'program_id' => $programs[1]->id,
            'title' => 'Distribusi Paket Kebutuhan Pokok',
            'slug' => 'distribusi-paket-kebutuhan-pokok',
            'excerpt' => 'Paket kebutuhan pokok disalurkan kepada keluarga penerima manfaat.',
            'body' => 'Tim yayasan dan relawan menyalurkan paket kebutuhan pokok kepada keluarga penerima manfaat. Setiap paket disiapkan berdasarkan kebutuhan dasar keluarga.',
            'cover_image' => 'https://images.unsplash.com/photo-1469571486292-0ba58a3f068b?auto=format&fit=crop&w=900&q=80',
            'status' => 'published',
            'start_date' => now()->subDays(14),
            'published_at' => now()->subDays(13),
        ]);

        collect([
            ['Anak penerima manfaat', 120, '+'],
            ['Program aktif', 3, ''],
            ['Kegiatan terdokumentasi', 24, '+'],
            ['Relawan terlibat', 35, '+'],
        ])->each(function (array $metric, int $index): void {
            ImpactMetric::create([
                'label' => $metric[0],
                'value' => $metric[1],
                'suffix' => $metric[2],
                'sort_order' => $index,
                'is_active' => true,
            ]);
        });

        collect([
            ['Belajar bersama relawan', 'https://images.unsplash.com/photo-1542810634-71277d95dcbb?auto=format&fit=crop&w=900&q=80'],
            ['Kegiatan berbagi paket', 'https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&w=900&q=80'],
            ['Ruang ceria anak', 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=900&q=80'],
        ])->each(function (array $item, int $index) use ($programs): void {
            GalleryItem::create([
                'program_id' => $programs[$index]->id,
                'title' => $item[0],
                'caption' => 'Dokumentasi kegiatan yayasan.',
                'image_path' => $item[1],
                'sort_order' => $index,
                'is_published' => true,
            ]);
        });
    }
}
