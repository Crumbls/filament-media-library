<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Crumbls\FilamentMediaLibrary\FilamentMediaLibraryServiceProvider;
use Crumbls\FilamentMediaLibrary\Tests\Fixtures\User;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            SupportServiceProvider::class,
            LivewireServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
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
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));

        $app['config']->set('auth.providers.users.model', User::class);
    }
}
