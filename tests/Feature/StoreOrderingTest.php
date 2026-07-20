<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_position_drives_catalog_order_and_zero_sinks_to_the_end(): void
    {
        Store::factory()->create(['name' => 'Featured no position', 'position' => 0, 'is_featured' => true, 'is_active' => true]);
        Store::factory()->create(['name' => 'Second', 'position' => 2, 'is_featured' => false, 'is_active' => true]);
        Store::factory()->create(['name' => 'First', 'position' => 1, 'is_featured' => false, 'is_active' => true]);
        Store::factory()->create(['name' => 'Zebra no position', 'position' => 0, 'is_featured' => false, 'is_active' => true]);

        $names = Store::query()->orderedByPosition()->pluck('name')->all();

        $this->assertSame([
            // Explicit positions win, lowest first...
            'First',
            'Second',
            // ...then unpositioned stores, featured before the rest.
            'Featured no position',
            'Zebra no position',
        ], $names);
    }

    public function test_editing_position_moves_a_store_above_featured_ones(): void
    {
        $featured = Store::factory()->create(['name' => 'Seeded featured', 'position' => 0, 'is_featured' => true, 'is_active' => true]);
        $imported = Store::factory()->create(['name' => 'Imported store', 'position' => 0, 'is_featured' => false, 'is_active' => true]);

        // Before: the featured store leads.
        $this->assertSame($featured->id, Store::query()->orderedByPosition()->first()?->id);

        // The admin sets "Позиция" on the imported store — it must now lead.
        $imported->update(['position' => 1]);

        $this->assertSame($imported->id, Store::query()->orderedByPosition()->first()?->id);
    }
}
