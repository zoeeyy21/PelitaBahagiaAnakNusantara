<?php

namespace Database\Seeders;

use App\Models\Donation;
use App\Models\Program;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DonationSeeder extends Seeder
{
    /**
     * Isi data donasi dummy dengan variasi status, program, nominal, dan tanggal.
     */
    public function run(): void
    {
        $faker = fake('id_ID');
        $programIds = Program::query()->where('status', 'active')->pluck('id')->all();
        $statuses = ['pending', 'pending', 'paid', 'paid', 'paid', 'paid', 'failed', 'expired', 'cancelled'];
        $amounts = [25000, 50000, 75000, 100000, 150000, 250000, 500000, 1000000];
        $messages = [
            'Semoga bermanfaat untuk adik-adik.',
            'Semangat terus untuk program yayasan!',
            'Titip doa untuk keluarga kami.',
            'Sedikit rezeki, semoga berkah.',
            null,
            null,
        ];

        foreach (range(1, 30) as $index) {
            $createdAt = now()->subDays($faker->numberBetween(0, 60))->subMinutes($faker->numberBetween(0, 1440));
            $status = $faker->randomElement($statuses);
            $programId = $programIds !== [] && $faker->boolean(70) ? $faker->randomElement($programIds) : null;

            Donation::factory()->create([
                'reference_code' => 'YPB-'.$createdAt->format('Ymd').'-'.Str::upper(Str::random(6)),
                'program_id' => $programId,
                'donation_type' => $programId ? 'program' : 'general',
                'amount' => $faker->randomElement($amounts),
                'donor_name' => $faker->name(),
                'donor_email' => $faker->boolean(60) ? $faker->safeEmail() : null,
                'donor_phone' => '08'.$faker->numerify('##########'),
                'is_anonymous' => $faker->boolean(20),
                'message' => $faker->randomElement($messages),
                'status' => $status,
                'paid_at' => $status === 'paid' ? $createdAt->copy()->addHours($faker->numberBetween(1, 24)) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
