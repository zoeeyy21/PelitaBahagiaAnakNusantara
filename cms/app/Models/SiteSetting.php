<?php

namespace App\Models;

use Database\Factories\SiteSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SiteSetting extends Model
{
    /** @use HasFactory<SiteSettingFactory> */
    use HasFactory;

    public const CONTACT_CACHE_KEY = 'site_settings.contact';

    public const FAQS_CACHE_KEY = 'site_settings.faqs';

    public const FAQS_KEY = 'faqs';

    /**
     * Nilai awal kontak, dipakai selama admin belum mengisi data di CMS.
     *
     * @var array{address: string, whatsapp: string, email: string, instagram: string, tiktok: ?string, facebook: ?string, map_url: ?string}
     */
    public const CONTACT_DEFAULTS = [
        'address' => 'Jakarta, Indonesia',
        'whatsapp' => '0812-0000-0000',
        'email' => 'halo@pelitabahagia.org',
        'instagram' => 'pelitabahagia',
        'tiktok' => null,
        'facebook' => null,
        'map_url' => null,
    ];

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Informasi kontak resmi yayasan beserta tautan turunannya.
     *
     * TikTok dan Facebook opsional; tautannya null bila belum diisi.
     *
     * @return array{address: string, whatsapp: string, email: string, instagram: string, tiktok: ?string, facebook: ?string, map_url: ?string, whatsapp_url: string, instagram_url: string, tiktok_url: ?string, facebook_url: ?string}
     */
    public static function contact(): array
    {
        $stored = Cache::rememberForever(self::CONTACT_CACHE_KEY, fn (): array => static::query()
            ->whereIn('key', self::contactKeys())
            ->pluck('value', 'key')
            ->all());

        $contact = array_merge(self::CONTACT_DEFAULTS, array_filter(
            self::stripPrefix($stored),
            fn (?string $value): bool => filled($value),
        ));

        $whatsappDigits = preg_replace('/\D/', '', $contact['whatsapp']);

        return $contact + [
            'whatsapp_url' => 'https://wa.me/'.preg_replace('/^0/', '62', $whatsappDigits),
            'instagram_url' => 'https://instagram.com/'.$contact['instagram'],
            'tiktok_url' => $contact['tiktok'] ? 'https://www.tiktok.com/@'.$contact['tiktok'] : null,
            'facebook_url' => $contact['facebook'],
        ];
    }

    /**
     * Simpan informasi kontak dan bersihkan cache.
     *
     * @param  array<string, ?string>  $contact
     */
    public static function updateContact(array $contact): void
    {
        foreach (array_keys(self::CONTACT_DEFAULTS) as $field) {
            static::updateOrCreate(
                ['key' => 'contact.'.$field],
                ['value' => $contact[$field] ?? null],
            );
        }

        Cache::forget(self::CONTACT_CACHE_KEY);
    }

    /**
     * Daftar FAQ yang tampil di halaman Kontak.
     *
     * @return list<array{question: string, answer: string}>
     */
    public static function faqs(): array
    {
        return Cache::rememberForever(self::FAQS_CACHE_KEY, function (): array {
            $stored = static::query()->where('key', self::FAQS_KEY)->value('value');
            $faqs = $stored ? json_decode($stored, true) : [];

            return is_array($faqs) ? array_values($faqs) : [];
        });
    }

    /**
     * Simpan FAQ dan bersihkan cache.
     *
     * @param  list<array{question: string, answer: string}>  $faqs
     */
    public static function updateFaqs(array $faqs): void
    {
        static::updateOrCreate(
            ['key' => self::FAQS_KEY],
            ['value' => json_encode(array_values($faqs), JSON_UNESCAPED_UNICODE)],
        );

        Cache::forget(self::FAQS_CACHE_KEY);
    }

    public const STRUCTURE_IMAGE_KEY = 'site.structure_image';

    public const STRUCTURE_CACHE_KEY = 'site_settings.structure_image';

    /**
     * Dapatkan path gambar bagan struktur yayasan.
     */
    public static function structureImage(): ?string
    {
        return Cache::rememberForever(self::STRUCTURE_CACHE_KEY, function (): ?string {
            return static::query()->where('key', self::STRUCTURE_IMAGE_KEY)->value('value');
        });
    }

    /**
     * Simpan path gambar bagan struktur yayasan.
     */
    public static function updateStructureImage(?string $path): void
    {
        static::updateOrCreate(
            ['key' => self::STRUCTURE_IMAGE_KEY],
            ['value' => $path],
        );

        Cache::forget(self::STRUCTURE_CACHE_KEY);
    }

    public const BANK_CACHE_KEY = 'site_settings.bank';

    /**
     * Field profil bank & QRIS yayasan, disimpan dengan prefix "bank.".
     *
     * @var list<string>
     */
    public const BANK_FIELDS = ['bank_name', 'bank_account_number', 'bank_account_holder', 'qris_image'];

    /**
     * Profil bank & QRIS yayasan, sama untuk semua akun admin.
     *
     * @return array{bank_name: ?string, bank_account_number: ?string, bank_account_holder: ?string, qris_image: ?string}
     */
    public static function bank(): array
    {
        $stored = Cache::rememberForever(self::BANK_CACHE_KEY, fn (): array => static::query()
            ->where('key', 'like', 'bank.%')
            ->pluck('value', 'key')
            ->all());

        $bank = [];

        foreach (self::BANK_FIELDS as $field) {
            $bank[$field] = $stored['bank.'.$field] ?? null;
        }

        return $bank;
    }

    /**
     * Simpan profil bank & QRIS yayasan dan bersihkan cache.
     *
     * @param  array<string, ?string>  $bank
     */
    public static function updateBank(array $bank): void
    {
        foreach (self::BANK_FIELDS as $field) {
            if (array_key_exists($field, $bank)) {
                static::updateOrCreate(['key' => 'bank.'.$field], ['value' => $bank[$field]]);
            }
        }

        Cache::forget(self::BANK_CACHE_KEY);
    }

    /**
     * Profil bank (bank, rekening, pemilik rekening, dan QRIS) yayasan sudah lengkap.
     */
    public static function hasCompleteBankProfile(): bool
    {
        return collect(self::bank())->every(fn (?string $value): bool => filled($value));
    }

    /**
     * @return list<string>
     */
    private static function contactKeys(): array
    {
        return array_map(fn (string $field): string => 'contact.'.$field, array_keys(self::CONTACT_DEFAULTS));
    }

    /**
     * @param  array<string, ?string>  $stored
     * @return array<string, ?string>
     */
    private static function stripPrefix(array $stored): array
    {
        $contact = [];

        foreach ($stored as $key => $value) {
            $contact[str_replace('contact.', '', $key)] = $value;
        }

        return $contact;
    }
}
