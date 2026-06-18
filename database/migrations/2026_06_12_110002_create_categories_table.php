<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('slug')->unique();

            // Translatable JSON fields (spatie/laravel-translatable): {"en": "...", "ru": "..."}
            $table->json('name');
            $table->json('description')->nullable();

            $table->string('icon')->nullable();   // lucide icon name
            $table->string('image')->nullable();   // stored path
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);

            // SEO
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
