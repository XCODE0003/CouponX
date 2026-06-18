<?php

namespace App\Filament\Resources\AffiliateNetworks\Pages;

use App\Filament\Resources\AffiliateNetworks\AffiliateNetworkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAffiliateNetwork extends EditRecord
{
    protected static string $resource = AffiliateNetworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
