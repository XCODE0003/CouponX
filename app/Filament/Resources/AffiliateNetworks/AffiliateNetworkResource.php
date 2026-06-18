<?php

namespace App\Filament\Resources\AffiliateNetworks;

use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\AffiliateNetworks\Pages\CreateAffiliateNetwork;
use App\Filament\Resources\AffiliateNetworks\Pages\EditAffiliateNetwork;
use App\Filament\Resources\AffiliateNetworks\Pages\ListAffiliateNetworks;
use App\Filament\Resources\AffiliateNetworks\Schemas\AffiliateNetworkForm;
use App\Filament\Resources\AffiliateNetworks\Tables\AffiliateNetworksTable;
use App\Models\AffiliateNetwork;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AffiliateNetworkResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = AffiliateNetwork::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Маркетинг';

    protected static ?string $navigationLabel = 'Партнёрские сети';

    protected static ?string $modelLabel = 'сеть';

    protected static ?string $pluralModelLabel = 'сети';

    public static function form(Schema $schema): Schema
    {
        return AffiliateNetworkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AffiliateNetworksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAffiliateNetworks::route('/'),
            'create' => CreateAffiliateNetwork::route('/create'),
            'edit' => EditAffiliateNetwork::route('/{record}/edit'),
        ];
    }
}
