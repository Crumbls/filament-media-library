<?php

declare(strict_types=1);

test('config has all expected top-level keys', function (): void {
    $config = config('filament-media-library');

    expect($config)->toHaveKeys([
        'disk',
        'accepted_file_types',
        'max_file_size',
        'image_conversions',
        'collections',
        'models',
        'routes',
        'tenancy',
        'filament',
    ]);
});

test('config has correct default disk', function (): void {
    expect(config('filament-media-library.disk'))->toBe('public');
});

test('config has correct default max file size', function (): void {
    expect(config('filament-media-library.max_file_size'))->toBe(10240);
});

test('config has expected image conversions', function (): void {
    $conversions = config('filament-media-library.image_conversions');

    expect($conversions)->toHaveKeys(['thumbnail', 'medium', 'large']);
    expect($conversions['thumbnail'])->toBe(['width' => 150, 'height' => 150]);
    expect($conversions['medium'])->toBe(['width' => 300, 'height' => 300]);
    expect($conversions['large'])->toBe(['width' => 1024, 'height' => 1024]);
});

test('config has accepted file types', function (): void {
    expect(config('filament-media-library.accepted_file_types'))
        ->toBe(['image/*', 'video/*', 'application/pdf']);
});

test('config has default media model', function (): void {
    expect(config('filament-media-library.models.media'))
        ->toBe(\Crumbls\FilamentMediaLibrary\Models\Media::class);
});

test('config has filament navigation defaults', function (): void {
    $filament = config('filament-media-library.filament');

    expect($filament['navigation_label'])->toBe('Media Library');
    expect($filament['navigation_icon'])->toBe('heroicon-o-photo');
    expect($filament['navigation_group'])->toBeNull();
    expect($filament['navigation_sort'])->toBeNull();
});
