<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Default kredit GLOBAL. Owner bisa ubah lewat UI nanti.
        // credit_mode: warning | block | approval
        // credit_limit: batas utang per customer (0 = tidak boleh kredit sama sekali)
        DB::table('settings')->insert([
            ['key' => 'credit_mode',  'value' => 'warning', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'credit_limit', 'value' => '0',       'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
