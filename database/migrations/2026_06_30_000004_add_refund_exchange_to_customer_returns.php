<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_returns', function (Blueprint $table) {
            // Jenis retur: refund (uang kembali) atau exchange (tukar barang).
            $table->string('type', 20)->default('refund')->after('return_reason_id');

            // Detail refund (wajib bila refund via transfer).
            $table->string('refund_method', 20)->nullable()->after('refund_amount'); // cash | transfer
            $table->string('refund_bank_name', 100)->nullable()->after('refund_method');
            $table->string('refund_bank_account', 100)->nullable()->after('refund_bank_name');
            $table->string('refund_account_holder', 150)->nullable()->after('refund_bank_account');
            $table->string('refund_proof_path')->nullable()->after('refund_account_holder');

            // Tukar barang: nota pengganti + selisih (replacement − returned).
            $table->foreignId('exchange_sale_id')->nullable()->after('refund_proof_path')->constrained('sales')->nullOnDelete();
            $table->decimal('exchange_diff', 15, 2)->default(0)->after('exchange_sale_id');
        });
    }

    public function down(): void
    {
        Schema::table('customer_returns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exchange_sale_id');
            $table->dropColumn([
                'type', 'refund_method', 'refund_bank_name', 'refund_bank_account',
                'refund_account_holder', 'refund_proof_path', 'exchange_diff',
            ]);
        });
    }
};
