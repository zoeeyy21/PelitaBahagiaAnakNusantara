<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Status program disederhanakan menjadi "active" dan "inactive",
     * sedangkan kegiatan tidak lagi memiliki status sehingga semuanya tampil.
     */
    public function up(): void
    {
        DB::table('programs')
            ->where('status', '!=', 'active')
            ->update(['status' => 'inactive']);

        DB::table('activities')
            ->where('status', '!=', 'published')
            ->update(['status' => 'published']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('programs')
            ->where('status', 'inactive')
            ->update(['status' => 'draft']);
    }
};
