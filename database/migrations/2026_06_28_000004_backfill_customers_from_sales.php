<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Membentuk baris `customers` dari data `sales` lama lalu menautkan sales.customer_id.
 * IDEMPOTENT: pakai NOT EXISTS / WHERE customer_id IS NULL sehingga aman dijalankan ulang
 * dan tidak menyentuh angka finansial apa pun (cuma identitas + relasi).
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Customer yang punya nomor telp -> dedupe per nomor telp.
        DB::statement("
            INSERT INTO customers (name, phone, is_active, created_at, updated_at)
            SELECT MAX(s.customer_name), s.customer_phone, 1, NOW(), NOW()
            FROM sales s
            WHERE s.customer_phone IS NOT NULL AND s.customer_phone <> ''
              AND NOT EXISTS (SELECT 1 FROM customers c WHERE c.phone = s.customer_phone)
            GROUP BY s.customer_phone
        ");

        // 2) Customer tanpa telp tapi punya nama -> dedupe per nama (phone NULL).
        DB::statement("
            INSERT INTO customers (name, phone, is_active, created_at, updated_at)
            SELECT s.customer_name, NULL, 1, NOW(), NOW()
            FROM sales s
            WHERE (s.customer_phone IS NULL OR s.customer_phone = '')
              AND s.customer_name IS NOT NULL AND s.customer_name <> ''
              AND NOT EXISTS (
                  SELECT 1 FROM customers c WHERE c.name = s.customer_name AND c.phone IS NULL
              )
            GROUP BY s.customer_name
        ");

        // 3) Tautkan sales.customer_id berdasarkan nomor telp.
        DB::statement("
            UPDATE sales s
            JOIN customers c ON c.phone = s.customer_phone
            SET s.customer_id = c.id
            WHERE s.customer_phone IS NOT NULL AND s.customer_phone <> ''
              AND s.customer_id IS NULL
        ");

        // 4) Tautkan sisanya berdasarkan nama (untuk yang tanpa telp).
        DB::statement("
            UPDATE sales s
            JOIN customers c ON c.name = s.customer_name AND c.phone IS NULL
            SET s.customer_id = c.id
            WHERE (s.customer_phone IS NULL OR s.customer_phone = '')
              AND s.customer_name IS NOT NULL AND s.customer_name <> ''
              AND s.customer_id IS NULL
        ");
    }

    public function down(): void
    {
        // Lepas tautan; tabel customers dibiarkan (drop ditangani migration create_customers saat rollback penuh).
        DB::statement("UPDATE sales SET customer_id = NULL WHERE customer_id IS NOT NULL");
    }
};
