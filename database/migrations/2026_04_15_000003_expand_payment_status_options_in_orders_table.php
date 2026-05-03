<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY payment_status ENUM('menunggu_pembayaran','pending','dibayar','gagal','kadaluarsa','dibatalkan') NOT NULL DEFAULT 'menunggu_pembayaran'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE orders SET payment_status = 'menunggu_pembayaran' WHERE payment_status IN ('pending','gagal','kadaluarsa','dibatalkan')");
        DB::statement("ALTER TABLE orders MODIFY payment_status ENUM('menunggu_pembayaran','dibayar') NOT NULL DEFAULT 'menunggu_pembayaran'");
    }
};
