<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')->label('Имя')->required()->maxLength(255),
                TextInput::make('email')->label('Email')->email()->required()->unique(ignoreRecord: true),
                TextInput::make('password')
                    ->label('Пароль')
                    ->password()
                    ->revealable()
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->helperText('Оставьте пустым, чтобы сохранить текущий пароль'),
                Select::make('role')
                    ->label('Роль')
                    ->options(self::roleOptions())
                    ->default(UserRole::Editor->value)
                    ->required(),
                Toggle::make('is_active')->label('Активен')->default(true)->inline(false),
            ]);
    }

    /** @return array<string, string> */
    private static function roleOptions(): array
    {
        $options = [];
        foreach (UserRole::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
