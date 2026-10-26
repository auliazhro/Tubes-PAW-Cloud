<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah thread_key jika belum ada
        if (!Schema::hasColumn('conversations', 'thread_key')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->string('thread_key', 50)->nullable()->after('id');
                $table->unique('thread_key');
            });
        }

        // 2. Ubah order_id menjadi nullable
        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable()->change();
        });

        // 3. Cek dulu apakah unique index lama memang ada
        $indexes = Schema::getIndexes('conversations');

        $indexExists = collect($indexes)->contains(function ($index) {
            return ($index['name'] ?? null)
                === 'conversations_order_id_buyer_id_seller_id_unique';
        });

        // Hapus hanya kalau benar-benar ada
        if ($indexExists) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->dropUnique(
                    'conversations_order_id_buyer_id_seller_id_unique'
                );
            });
        }
    }

    public function down(): void
    {
        // Kembalikan unique lama jika belum ada
        $indexes = Schema::getIndexes('conversations');

        $indexExists = collect($indexes)->contains(function ($index) {
            return ($index['name'] ?? null)
                === 'conversations_order_id_buyer_id_seller_id_unique';
        });

        if (!$indexExists) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->unique(
                    ['order_id', 'buyer_id', 'seller_id'],
                    'conversations_order_id_buyer_id_seller_id_unique'
                );
            });
        }

        // Kembalikan order_id menjadi NOT NULL
        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('order_id')->nullable(false)->change();
        });

        // Hapus thread_key
        if (Schema::hasColumn('conversations', 'thread_key')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->dropUnique(['thread_key']);
                $table->dropColumn('thread_key');
            });
        }
    }
};
