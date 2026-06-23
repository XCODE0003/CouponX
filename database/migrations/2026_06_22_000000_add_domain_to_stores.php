<?php

declare(strict_types=1);

use App\Support\Domain;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical merchant key for import de-duplication. Imports match an incoming
 * store by `domain` first, so the same merchant across networks (Admitad,
 * Indoleads, …) lands in one store card and manual edits are never overwritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('domain')->nullable()->after('website_url')->index();
        });

        // Backfill from existing website URLs so domain-matching works immediately.
        foreach (DB::table('stores')->whereNotNull('website_url')->get(['id', 'website_url']) as $store) {
            $domain = Domain::fromUrl($store->website_url);
            if ($domain !== null) {
                DB::table('stores')->where('id', $store->id)->update(['domain' => $domain]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn('domain');
        });
    }
};
