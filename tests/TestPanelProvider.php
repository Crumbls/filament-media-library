<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary\Tests;

use Crumbls\FilamentMediaLibrary\FilamentMediaLibraryPlugin;
use Filament\Panel;
use Filament\PanelProvider;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->plugins([
                new FilamentMediaLibraryPlugin,
            ]);
    }
}
