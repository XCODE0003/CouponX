<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_networks', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('integration')->nullable(); // adapter key, e.g. "admitad", "manual"
            $table->boolean('is_active')->default(true);

            // Redirect/tracking URL template. {target} is replaced by the destination URL.
            $table->text('tracking_template')->nullable();

            // Default UTM params applied to outgoing links.
            $table->json('default_utm')->nullable();

            // Adapter credentials & import settings (encrypted at the model layer where needed).
            $table->json('config')->nullable();

            $table->timestamp('last_imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_networks');
    }
};
