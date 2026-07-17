<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pesanan online dari landing page katalog (catalog.wonderkey.store).
 * Alur: order masuk (pending) → superadmin konfirmasi pembayaran → stok gudang dipotong.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 30)->unique();

            // Data pembeli
            $table->string('customer_name', 150);
            $table->string('customer_phone', 30)->nullable();
            $table->text('address');
            $table->string('notes', 500)->nullable();

            // Gudang sumber stok
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnUpdate()->restrictOnDelete();

            $table->decimal('total_amount', 14, 2)->default(0);
            $table->unsignedInteger('total_qty')->default(0);

            // pending = menunggu pembayaran, paid = lunas & stok sudah dipotong, cancelled = batal
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending')->index();
            $table->string('payment_note', 255)->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancel_reason', 255)->nullable();

            $table->string('source', 30)->default('catalog'); // asal order
            $table->timestamps();

            $table->index('created_at');
        });

        Schema::create('online_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('online_order_id')->constrained('online_orders')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();

            // Snapshot data saat order dibuat (harga/nama bisa berubah kemudian)
            $table->string('sku', 100);
            $table->string('product_name', 200);
            $table->string('color', 100)->nullable();
            $table->string('size', 50)->nullable();

            $table->unsignedInteger('qty');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_order_items');
        Schema::dropIfExists('online_orders');
    }
};
