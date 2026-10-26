<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah thread_key jika belum ada
        if (!Schema::hasColumn('conversations', 'thread_key')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->string('thread_key', 191)
                    ->nullable()
                    ->after('seller_id');
            });
        }

        // 2. Isi thread_key untuk data lama jika ada
        DB::table('conversations')
            ->whereNull('thread_key')
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $c) {
                    $key = $c->order_id
                        ? "ord:{$c->order_id}:{$c->buyer_id}:{$c->seller_id}"
                        : "pre:{$c->buyer_id}:{$c->seller_id}";

                    DB::table('conversations')
                        ->where('id', $c->id)
                        ->update([
                            'thread_key' => $key
                        ]);
                }
            });

        // 3. order_id dibuat nullable
        if (Schema::hasColumn('conversations', 'order_id')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->unsignedBigInteger('order_id')
                    ->nullable()
                    ->change();
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

        // 5. thread_key dibuat NOT NULL
        if (Schema::hasColumn('conversations', 'thread_key')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->string('thread_key', 191)
                    ->nullable(false)
                    ->change();
            });
        }

        // 6. Cek apakah unique thread_key sudah ada
        $indexes = Schema::getIndexes('conversations');

        $threadKeyUniqueExists = collect($indexes)->contains(function ($index) {
            return ($index['name'] ?? null)
                === 'conversations_thread_key_unique';
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
    }

    public function down(): void
    {
        // Hapus unique thread_key kalau ada
        $indexes = Schema::getIndexes('conversations');

        $threadKeyUniqueExists = collect($indexes)->contains(function ($index) {
            return ($index['name'] ?? null)
                === 'conversations_thread_key_unique';
        });

        if ($threadKeyUniqueExists) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->dropUnique(
                    'conversations_thread_key_unique'
                );
            });
        }

        // Kembalikan unique lama jika belum ada
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

        // Kembalikan order_id jadi NOT NULL
        if (Schema::hasColumn('conversations', 'order_id')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->unsignedBigInteger('order_id')
                    ->nullable(false)
                    ->change();
            });
        }

        // Hapus thread_key
        if (Schema::hasColumn('conversations', 'thread_key')) {
            Schema::table('conversations', function (Blueprint $table) {
                $table->dropColumn('thread_key');
            });
        }
    }
};
