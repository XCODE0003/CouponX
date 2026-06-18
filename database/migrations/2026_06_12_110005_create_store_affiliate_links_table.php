<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Geo-aware affiliate destinations. A store can have different affiliate
     * URLs per country / network. country_code = null is the global default.
     */
    public function up(): void
    {
        Schema::create('store_affiliate_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('affiliate_network_id')->nullable()->constrained('affiliate_networks')->nullOnDelete();
            $table->string('country_code', 2)->nullable(); // ISO-2, null = default
            $table->text('affiliate_url');
            $table->string('cashback_value')->nullable();
            $table->unsignedInteger('priority')->default(0); // higher wins
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['store_id', 'country_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_affiliate_links');
    }
};
