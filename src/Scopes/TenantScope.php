<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary\Scopes;

use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! static::shouldScope()) {
            return;
        }

        $tenantId = static::resolveTenantId();

        if ($tenantId === null) {
            return;
        }

        $column = config('filament-media-library.tenancy.column', 'tenant_id');

        $builder->where($model->qualifyColumn($column), $tenantId);
    }

    public static function shouldScope(): bool
    {
        $configEnabled = config('filament-media-library.tenancy.enabled');

        if ($configEnabled === null) {
            return Filament::hasTenancy();
        }

        return (bool) $configEnabled;
    }

    public static function resolveTenantId(): mixed
    {
        return Filament::getTenant()?->getKey();
    }
}
