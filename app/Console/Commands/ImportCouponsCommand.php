<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AffiliateNetwork;
use App\Services\Import\AdapterRegistry;
use App\Services\Import\CouponImporter;
use App\Services\Notifications\AdminNotifier;
use Illuminate\Console\Command;
use Throwable;

class ImportCouponsCommand extends Command
{
    protected $signature = 'coupons:import {network? : Affiliate network slug to import (defaults to all active)}';

    protected $description = 'Import coupons from affiliate networks via their registered adapters';

    public function handle(CouponImporter $importer, AdapterRegistry $registry, AdminNotifier $notifier): int
    {
        $slug = $this->argument('network');

        $networks = AffiliateNetwork::query()
            ->where('is_active', true)
            ->when(is_string($slug) && $slug !== '', fn ($q) => $q->where('slug', $slug))
            ->get()
            ->filter(fn (AffiliateNetwork $n): bool => $registry->has((string) $n->integration));

        if ($networks->isEmpty()) {
            $this->warn('No active networks with a registered adapter to import.');

            return self::SUCCESS;
        }

        $failed = false;

        foreach ($networks as $network) {
            $this->info("Importing {$network->name} ({$network->integration})...");

            try {
                $result = $importer->import($network);
                $this->line('  '.$result->summary());

                if ($result->hasErrors()) {
                    $notifier->error(
                        "Import completed with errors: {$network->name}",
                        implode("\n", array_slice($result->errors, 0, 20)),
                    );
                }
            } catch (Throwable $e) {
                $failed = true;
                $this->error("  Failed: {$e->getMessage()}");
                $notifier->error("Import failed: {$network->name}", $e->getMessage());
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
