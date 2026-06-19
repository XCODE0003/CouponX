<?php

declare(strict_types=1);

namespace App\Filament\Resources\BlogPosts\Tables;

use App\Enums\BlogPostStatus;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BlogPostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                ImageColumn::make('cover_image')->label('Обложка')->disk('public')->toggleable(),
                TextColumn::make('title')->label('Заголовок')->searchable()->sortable()->weight('bold')->limit(50),
                TextColumn::make('slug')->label('URL')->color('gray')->toggleable(),
                TextColumn::make('author.name')->label('Автор')->placeholder('—')->toggleable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->formatStateUsing(fn (BlogPostStatus $state): string => $state->label())
                    ->color(fn (BlogPostStatus $state): string => $state === BlogPostStatus::Published ? 'success' : 'gray'),
                TextColumn::make('published_at')->label('Опубликовано')->dateTime()->sortable()->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')->options(self::statusOptions()),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** @return array<string, string> */
    private static function statusOptions(): array
    {
        $options = [];
        foreach (BlogPostStatus::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
