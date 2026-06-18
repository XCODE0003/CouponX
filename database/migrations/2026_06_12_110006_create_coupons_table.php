<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('affiliate_network_id')->nullable()->constrained('affiliate_networks')->nullOnDelete();

            // type: code (промокод) | deal (скидка) | sale (акция)
            $table->string('type')->default('code');

            // Translatable JSON fields
            $table->json('title');
            $table->json('description')->nullable();
            $table->json('terms')->nullable();

            $table->string('code')->nullable(); // null for deal/sale

            // discount_type: percentage | fixed | free_shipping | bogo | other
            $table->string('discount_type')->nullable();
            $table->decimal('discount_value', 10, 2)->nullable();

            $table->text('destination_url')->nullable(); // specific landing; falls back to store link

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->unsignedBigInteger('used_count')->default(0);
            $table->unsignedTinyInteger('success_rate')->nullable(); // 0-100
            $table->unsignedBigInteger('clicks_count')->default(0);

            $table->boolean('is_exclusive')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_verified')->default(false);

            // status: active | expired | draft | archived
            $table->string('status')->default('active');
            $table->unsignedInteger('position')->default(0);

            // Dedup / import provenance
            $table->string('source')->nullable();       // network slug
            $table->string('external_id')->nullable();
            $table->string('dedupe_hash')->nullable()->index();

            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index(['status', 'is_featured']);
            $table->index('expires_at');
            $table->unique(['source', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
