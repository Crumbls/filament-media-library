<?php

declare(strict_types=1);

use Crumbls\FilamentMediaLibrary\FilamentMediaLibraryPlugin;
use Filament\Contracts\Plugin;

test('plugin returns correct id', function (): void {
    $plugin = new FilamentMediaLibraryPlugin;

    expect($plugin->getId())->toBe('filament-media-library');
});

test('plugin implements Plugin interface', function (): void {
    expect(FilamentMediaLibraryPlugin::class)
        ->toImplement(Plugin::class);
});
