<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsletterSubscribers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class NewsletterSubscriberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
                Select::make('status')
                    ->label('Статус')
                    ->options([
                        'subscribed' => 'Подписан',
                        'unsubscribed' => 'Отписан',
                    ])
                    ->default('subscribed')
                    ->required(),
                Select::make('locale')->label('Язык')->options(['en' => 'English', 'ru' => 'Русский']),
                TextInput::make('country_code')->label('Страна')->maxLength(2)->placeholder('US'),
            ]);
    }
}
