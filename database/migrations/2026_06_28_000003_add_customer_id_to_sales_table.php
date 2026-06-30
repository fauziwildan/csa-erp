<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Nullable + nullOnDelete: nota lama tetap valid, kolom customer_name/customer_phone
            // SENGAJA dipertahankan untuk backward-compat & jejak nama saat transaksi.
            $table->foreignId('customer_id')->nullable()->after('store_id')
                ->constrained('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });
    }
};
