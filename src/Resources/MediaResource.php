<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary\Resources;

use Crumbls\FilamentMediaLibrary\Models\Media;
use Crumbls\FilamentMediaLibrary\Resources\MediaResource\Pages\ListMedia;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    public static function getModelLabel(): string
    {
        return 'Media';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Media';
    }

    public static function getNavigationGroup(): ?string
    {
        return config('filament-media-library.filament.navigation_group');
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-media-library.filament.navigation_sort');
    }

    public static function getNavigationLabel(): string
    {
        return config('filament-media-library.filament.navigation_label', 'Media Library');
    }

    public static function getNavigationIcon(): string|\BackedEnum|null
    {
        return config('filament-media-library.filament.navigation_icon', 'heroicon-o-photo');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
        ];
    }
}
