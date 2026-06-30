<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Tanggal nota menjadi LUNAS penuh (basis pengakuan komisi cash-basis). Null = belum lunas.
            $table->timestamp('settled_at')->nullable()->after('due_date');
            $table->index('settled_at');
        });

        // Backfill: nota yang sudah lunas dianggap settled di tanggal nota dibuat. Idempotent.
        DB::statement("UPDATE sales SET settled_at = created_at WHERE payment_status = 'lunas' AND settled_at IS NULL");
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['settled_at']);
            $table->dropColumn('settled_at');
        });
    }
};
