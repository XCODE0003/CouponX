<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Aliases let several imported store names (e.g. "AliExpress WW",
     * "AliExpress RU", "AliExpress Global") map to one canonical store entity.
     */
    public function up(): void
    {
        Schema::create('store_aliases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('name');
            $table->string('normalized')->index(); // lowercased/trimmed for matching
            $table->string('source')->nullable();   // network slug the alias came from
            $table->string('external_id')->nullable();
            $table->timestamps();

            $table->unique(['normalized', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_aliases');
    }
};
