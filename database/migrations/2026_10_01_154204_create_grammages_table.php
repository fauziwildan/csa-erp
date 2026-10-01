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
        Schema::create('grammages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // e.g. "24s", "30s", "180-190 gr/m2", "200 gsm"
            $table->string('value', 50)->nullable(); // e.g. numeric or text representation if needed
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grammages');
    }
};
