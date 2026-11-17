<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('beneficiaries');
            $table->date('end_date')->nullable()->after('start_date');
            $table->unsignedBigInteger('donation_target')->nullable()->after('accepts_donation');
            $table->unsignedBigInteger('min_donation_amount')->nullable()->after('donation_target');
            $table->string('qris_image')->nullable()->after('min_donation_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'end_date', 'donation_target', 'min_donation_amount', 'qris_image']);
        });
    }
};
