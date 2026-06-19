<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `config` holds an ENCRYPTED blob (model cast `encrypted:array`), not raw JSON.
 * A MySQL `json` column rejects the ciphertext ("Invalid JSON text"), which broke
 * saving API credentials for non-manual networks (Admitad/CJ/Awin). SQLite stored
 * the same column as TEXT, so this only surfaced on MySQL in production.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('affiliate_networks', function (Blueprint $table) {
            $table->text('config')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_networks', function (Blueprint $table) {
            $table->json('config')->nullable()->change();
        });
    }
};
