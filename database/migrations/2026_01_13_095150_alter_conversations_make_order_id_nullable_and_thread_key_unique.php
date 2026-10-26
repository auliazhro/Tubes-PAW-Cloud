<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Pastikan order_id nullable
        if (Schema::hasColumn('conversations', 'order_id')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->unsignedBigInteger('order_id')->nullable()->change();
            });
        }

        // 2. Pastikan thread_key tersedia
        if (!Schema::hasColumn('conversations', 'thread_key')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->string('thread_key', 191)->nullable();
            });
        }

        // 3. Cek apakah unique thread_key sudah ada
        $indexes = Schema::getIndexes('conversations');

        $threadKeyUniqueExists = collect($indexes)->contains(function ($index) {
            return ($index['name'] ?? null) === 'conversations_thread_key_unique';
        });

        // Kalau belum ada, baru dibuat
        if (!$threadKeyUniqueExists) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->unique(
                    'thread_key',
                    'conversations_thread_key_unique'
                );
            });
        }

        // 4. Cek unique index lama
        $indexes = Schema::getIndexes('conversations');

        $oldUniqueExists = collect($indexes)->contains(function ($index) {
            return ($index['name'] ?? null)
                === 'conversations_order_id_buyer_id_seller_id_unique';
        });

        // Hapus hanya kalau memang ada
        if ($oldUniqueExists) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->dropUnique(
                    'conversations_order_id_buyer_id_seller_id_unique'
                );
            });
        }
    }

    public function down(): void
    {
        // 1. Hapus unique thread_key kalau ada
        $indexes = Schema::getIndexes('conversations');

        $threadKeyUniqueExists = collect($indexes)->contains(function ($index) {
            return ($index['name'] ?? null) === 'conversations_thread_key_unique';
        });

        if ($threadKeyUniqueExists) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->dropUnique(
                    'conversations_thread_key_unique'
                );
            });
        }

        // 2. Kembalikan order_id menjadi NOT NULL
        if (Schema::hasColumn('conversations', 'order_id')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->unsignedBigInteger('order_id')
                    ->nullable(false)
                    ->change();
            });
        }

        // 3. Kembalikan unique index lama kalau belum ada
        $indexes = Schema::getIndexes('conversations');

        $oldUniqueExists = collect($indexes)->contains(function ($index) {
            return ($index['name'] ?? null)
                === 'conversations_order_id_buyer_id_seller_id_unique';
        });

        if (!$oldUniqueExists) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->unique(
                    ['order_id', 'buyer_id', 'seller_id'],
                    'conversations_order_id_buyer_id_seller_id_unique'
                );
            });
        }
    }
};
