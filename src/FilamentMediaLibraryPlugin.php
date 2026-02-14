<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary;

use Crumbls\FilamentMediaLibrary\Resources\MediaResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

class FilamentMediaLibraryPlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'filament-media-library';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            MediaResource::class,
        ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }
}
