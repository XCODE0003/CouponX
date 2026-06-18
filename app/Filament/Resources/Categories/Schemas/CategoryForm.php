<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Schemas;

use App\Filament\Support\Translatable;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Категория')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name.'.Translatable::defaultLocale())
                            ->label('Название (язык по умолчанию)')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')->label('URL')->required()->unique(ignoreRecord: true),
                        Select::make('parent_id')
                            ->label('Родительская категория')
                            ->relationship('parent', 'slug')
                            ->searchable()
                            ->preload(),
                        TextInput::make('icon')->label('Иконка')->maxLength(60)->placeholder('laptop, shirt, home…')
                            ->helperText('Название иконки Lucide'),
                        TextInput::make('position')->label('Позиция')->numeric()->default(0),
                        Toggle::make('is_featured')->label('Рекомендуемый')->inline(false),
                        Toggle::make('is_active')->label('Активен')->default(true)->inline(false),
                    ]),

                Translatable::tabs(fn (string $locale, bool $isDefault): array => [
                    TextInput::make("name.$locale")->label('Название')->required($isDefault)->maxLength(255),
                    Textarea::make("description.$locale")->label('Описание')->rows(2),
                    TextInput::make("meta_title.$locale")->label('Meta-заголовок')->maxLength(255),
                    Textarea::make("meta_description.$locale")->label('Meta-описание')->rows(2)->maxLength(500),
                ]),
            ]);
    }
}
