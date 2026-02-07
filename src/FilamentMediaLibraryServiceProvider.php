<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary;

use Crumbls\FilamentMediaLibrary\Http\Controllers\MediaPickerController;
use Illuminate\Support\Facades\Route;
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

        Route::middleware(['web', 'auth'])
            ->prefix('filament-media-library')
            ->group(function (): void {
                Route::get('media-picker', [MediaPickerController::class, 'index'])
                    ->name('filament-media-library.media-picker');
                Route::post('media-picker/upload', [MediaPickerController::class, 'upload'])
                    ->name('filament-media-library.media-picker.upload');
                Route::get('media-picker/{id}', [MediaPickerController::class, 'show'])
                    ->name('filament-media-library.media-picker.show');
                Route::patch('media-picker/{id}', [MediaPickerController::class, 'update'])
                    ->name('filament-media-library.media-picker.update');
                Route::delete('media-picker/{id}', [MediaPickerController::class, 'destroy'])
                    ->name('filament-media-library.media-picker.destroy');
            });
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
