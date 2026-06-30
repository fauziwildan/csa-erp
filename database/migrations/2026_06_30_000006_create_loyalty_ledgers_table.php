<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->integer('points'); // + earn, - redeem
            $table->string('type', 20); // earn | redeem | adjust
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('sale_id');
        });

        // Setelan loyalty: 1 poin per kelipatan rupiah ini (default Rp10.000), nilai tukar 1 poin = Rp.
        DB::table('settings')->insert([
            ['key' => 'loyalty_earn_divisor', 'value' => '10000', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'loyalty_point_value',  'value' => '1000',  'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_ledgers');
        DB::table('settings')->whereIn('key', ['loyalty_earn_divisor', 'loyalty_point_value'])->delete();
    }
};
