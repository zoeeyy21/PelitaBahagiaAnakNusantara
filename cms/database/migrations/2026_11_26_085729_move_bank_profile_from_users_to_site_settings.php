<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $fields = ['bank_name', 'bank_account_number', 'bank_account_holder', 'qris_image'];

    /**
     * Profil bank & QRIS milik yayasan (satu untuk semua akun), jadi dipindah dari users ke site_settings.
     */
    public function up(): void
    {
        $source = DB::table('users')->whereNotNull('qris_image')->orderBy('id')->first()
            ?? DB::table('users')->whereNotNull('bank_name')->orderBy('id')->first();

        if ($source) {
            foreach ($this->fields as $field) {
                DB::table('site_settings')->updateOrInsert(
                    ['key' => 'bank.'.$field],
                    ['value' => $source->{$field}, 'created_at' => now(), 'updated_at' => now()],
                );
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn($this->fields);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('email');
            $table->string('bank_account_number')->nullable()->after('bank_name');
            $table->string('bank_account_holder')->nullable()->after('bank_account_number');
            $table->string('qris_image')->nullable()->after('bank_account_holder');
        });

        $stored = DB::table('site_settings')->where('key', 'like', 'bank.%')->pluck('value', 'key');

        if ($stored->isNotEmpty()) {
            DB::table('users')->update(collect($this->fields)
                ->mapWithKeys(fn (string $field): array => [$field => $stored['bank.'.$field] ?? null])
                ->all());
        }

        DB::table('site_settings')->where('key', 'like', 'bank.%')->delete();
    }
};
