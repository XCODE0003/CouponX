<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->nullOnDelete();
            $table->foreignId('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('affiliate_network_id')->nullable()->constrained('affiliate_networks')->nullOnDelete();

            $table->string('country_code', 2)->nullable();
            $table->string('locale', 8)->nullable();

            $table->string('ip_hash', 64)->nullable();  // partially masked + hashed, never raw
            $table->text('user_agent')->nullable();
            $table->text('referer')->nullable();
            $table->json('utm')->nullable();

            $table->timestamp('created_at')->nullable()->index();

            $table->index(['store_id', 'created_at']);
            $table->index(['coupon_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clicks');
    }
};
