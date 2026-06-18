<?php

declare(strict_types=1);

namespace App\Filament\Resources\BlogPosts\Schemas;

use App\Enums\BlogPostStatus;
use App\Filament\Support\Translatable;
use App\Support\ImageOptimizer;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BlogPostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Статья')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title.'.Translatable::defaultLocale())
                            ->label('Заголовок (язык по умолчанию)')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, callable $set): void {
                                if ($operation === 'create') {
                                    $set('slug', Str::slug((string) $state));
                                }
                            }),
                        TextInput::make('slug')->label('URL')->required()->unique(ignoreRecord: true),
                        Select::make('author_id')
                            ->label('Автор')
                            ->relationship('author', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('status')
                            ->label('Статус')
                            ->options(self::statusOptions())
                            ->default(BlogPostStatus::Draft->value)
                            ->required(),
                        DateTimePicker::make('published_at')->label('Опубликовано'),
                        FileUpload::make('cover_image')
                            ->label('Обложка')
                            ->image()
                            ->disk('public')
                            ->directory('blog')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                            ->maxSize(4096)
                            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): ?string => ImageOptimizer::storeAsWebp($file, 'blog', 1280)),
                    ]),

                Translatable::tabs(fn (string $locale, bool $isDefault): array => [
                    TextInput::make("title.$locale")->label('Заголовок')->required($isDefault)->maxLength(255),
                    Textarea::make("excerpt.$locale")->label('Краткое описание')->rows(2)->maxLength(500),
                    RichEditor::make("body.$locale")->label('Текст'),
                    TextInput::make("meta_title.$locale")->label('Meta-заголовок')->maxLength(255),
                    Textarea::make("meta_description.$locale")->label('Meta-описание')->rows(2)->maxLength(500),
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
