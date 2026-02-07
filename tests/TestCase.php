<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary\Tests;

use Crumbls\FilamentMediaLibrary\FilamentMediaLibraryServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Support\SupportServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            SupportServiceProvider::class,
            FilamentServiceProvider::class,
            MediaLibraryServiceProvider::class,
            FilamentMediaLibraryServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('filament-media-library.disk', 'public');
        $app['config']->set('media-library.disk_name', 'public');

        $app['config']->set('auth.providers.users.model', \Crumbls\FilamentMediaLibrary\Tests\Fixtures\User::class);
    }
}
