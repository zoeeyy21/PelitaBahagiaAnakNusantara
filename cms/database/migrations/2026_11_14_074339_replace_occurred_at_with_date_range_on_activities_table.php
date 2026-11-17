<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanggal kegiatan kini berupa rentang (mulai–selesai), menggantikan satu tanggal "occurred_at".
     */
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('status');
            $table->date('end_date')->nullable()->after('start_date');
            $table->index('start_date');
        });

        DB::table('activities')->update([
            'start_date' => DB::raw('occurred_at'),
            'end_date' => DB::raw('occurred_at'),
        ]);

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['occurred_at']);
            $table->dropColumn('occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->date('occurred_at')->nullable()->after('status');
            $table->index('occurred_at');
        });

        DB::table('activities')->update(['occurred_at' => DB::raw('start_date')]);

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['start_date']);
            $table->dropColumn(['start_date', 'end_date']);
        });
    }
};
