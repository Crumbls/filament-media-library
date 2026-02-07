<?php

declare(strict_types=1);

test('config is merged into application', function (): void {
    expect(config('filament-media-library'))->not->toBeNull();
    expect(config('filament-media-library.disk'))->toBeString();
});

test('views are registered under correct namespace', function (): void {
    $viewFactory = app('view');

    expect($viewFactory->exists('filament-media-library::pages.media-grid'))->toBeTrue();
});

test('service provider is loaded', function (): void {
    $providers = app()->getLoadedProviders();

    expect($providers)->toHaveKey(\Crumbls\FilamentMediaLibrary\FilamentMediaLibraryServiceProvider::class);
});

test('config publish group is registered', function (): void {
    $groups = \Illuminate\Support\ServiceProvider::$publishGroups ?? [];

    expect($groups)->toHaveKey('filament-media-library-config');
});

test('migrations publish group is registered', function (): void {
    $groups = \Illuminate\Support\ServiceProvider::$publishGroups ?? [];

    expect($groups)->toHaveKey('filament-media-library-migrations');
});

test('views publish group is registered', function (): void {
    $groups = \Illuminate\Support\ServiceProvider::$publishGroups ?? [];

    expect($groups)->toHaveKey('filament-media-library-views');
});
