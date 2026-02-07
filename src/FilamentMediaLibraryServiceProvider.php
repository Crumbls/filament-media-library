<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary;

use Illuminate\Support\ServiceProvider;

class FilamentMediaLibraryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filament-media-library');

        $this->publishes([
            __DIR__.'/../config/filament-media-library.php' => config_path('filament-media-library.php'),
        ], 'filament-media-library-config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations'),
        ], 'filament-media-library-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/filament-media-library'),
        ], 'filament-media-library-views');
    }

    public function register(): void
    {
        parent::register();

        $this->mergeConfigFrom(
            __DIR__.'/../config/filament-media-library.php',
            'filament-media-library'
        );
    }
}
