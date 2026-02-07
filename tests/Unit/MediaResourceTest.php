<?php

declare(strict_types=1);

use Crumbls\FilamentMediaLibrary\Models\Media;
use Crumbls\FilamentMediaLibrary\Resources\MediaResource;

test('resource uses correct model', function (): void {
    expect(MediaResource::getModel())->toBe(Media::class);
});

test('resource returns correct model label', function (): void {
    expect(MediaResource::getModelLabel())->toBe('Media');
});

test('resource returns correct plural model label', function (): void {
    expect(MediaResource::getPluralModelLabel())->toBe('Media');
});

test('resource uses navigation group from config', function (): void {
    config(['filament-media-library.filament.navigation_group' => 'Content']);

    expect(MediaResource::getNavigationGroup())->toBe('Content');
});

test('resource uses navigation sort from config', function (): void {
    config(['filament-media-library.filament.navigation_sort' => 5]);

    expect(MediaResource::getNavigationSort())->toBe(5);
});

test('resource uses navigation label from config', function (): void {
    expect(MediaResource::getNavigationLabel())->toBe('Media Library');
});

test('resource uses navigation icon from config', function (): void {
    expect(MediaResource::getNavigationIcon())->toBe('heroicon-o-photo');
});

test('resource registers index page', function (): void {
    $pages = MediaResource::getPages();

    expect($pages)->toHaveKey('index');
});

test('resource has no create page', function (): void {
    $pages = MediaResource::getPages();

    expect($pages)->not->toHaveKey('create');
});

test('resource has no edit page', function (): void {
    $pages = MediaResource::getPages();

    expect($pages)->not->toHaveKey('edit');
});

test('resource returns empty relations', function (): void {
    expect(MediaResource::getRelations())->toBe([]);
});

test('resource navigation icon can be overridden via config', function (): void {
    config(['filament-media-library.filament.navigation_icon' => 'heroicon-o-camera']);

    expect(MediaResource::getNavigationIcon())->toBe('heroicon-o-camera');
});

test('resource navigation label can be overridden via config', function (): void {
    config(['filament-media-library.filament.navigation_label' => 'My Media']);

    expect(MediaResource::getNavigationLabel())->toBe('My Media');
});
