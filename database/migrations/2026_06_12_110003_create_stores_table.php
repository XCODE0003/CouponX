<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name'); // canonical brand name (locale-agnostic)

            // Translatable JSON fields
            $table->json('description')->nullable(); // short tagline
            $table->json('about')->nullable();       // long body / SEO text
            $table->json('cashback_terms')->nullable();

            $table->string('logo')->nullable();
            $table->string('website_url')->nullable();

            $table->decimal('rating', 2, 1)->nullable();
            $table->unsignedInteger('rating_count')->default(0);

            // Cashback display (matches reference "до 5% кэшбэк")
            $table->string('cashback_type')->nullable();   // e.g. "percent"
            $table->string('cashback_value')->nullable();  // e.g. "до 5%"
            $table->string('cashback_payout_terms')->nullable(); // e.g. "30-45 дней"

            $table->foreignId('default_affiliate_network_id')->nullable()->constrained('affiliate_networks')->nullOnDelete();

            // Supported countries (ISO-2 codes) for geo display
            $table->json('countries')->nullable();

            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->unsignedBigInteger('clicks_count')->default(0);

            // SEO
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'is_featured']);
            $table->index('position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
