<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary\Tests\Fixtures;

use Crumbls\FilamentMediaLibrary\Traits\HasMediaLibrary;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements FilamentUser
{
    use HasMediaLibrary;

    protected $guarded = [];

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
