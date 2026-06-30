<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // Tidak unique di level DB: data sales lama bisa ada nomor/nama ganda.
            // Keunikan ditegakkan di level aplikasi saat input baru.
            $table->string('phone', 30)->nullable()->index();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            // Kredit ditegakkan GLOBAL via tabel settings; kolom ini opsional override per-customer (null = ikut global).
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->integer('loyalty_points')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
