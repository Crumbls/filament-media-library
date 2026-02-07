<?php

declare(strict_types=1);

use Crumbls\FilamentMediaLibrary\Models\Media;
use Crumbls\FilamentMediaLibrary\Scopes\TenantScope;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

test('null config auto-detects from panel with tenancy', function (): void {
    config(['filament-media-library.tenancy.enabled' => null]);

    Filament\Facades\Filament::shouldReceive('hasTenancy')->andReturn(true);
    Filament\Facades\Filament::shouldReceive('getTenant')
        ->andReturn(new class
        {
            public function getKey(): int
            {
                return 5;
            }
        });

    Media::withoutGlobalScope(TenantScope::class)->create(['title' => 'Mine', 'tenant_id' => 5]);
    Media::withoutGlobalScope(TenantScope::class)->create(['title' => 'Theirs', 'tenant_id' => 99]);

    expect(Media::count())->toBe(1);
    expect(Media::first()->title)->toBe('Mine');
});

test('null config auto-detects from panel without tenancy', function (): void {
    config(['filament-media-library.tenancy.enabled' => null]);

    Filament\Facades\Filament::shouldReceive('hasTenancy')->andReturn(false);

    Media::withoutGlobalScope(TenantScope::class)->create(['title' => 'A', 'tenant_id' => 1]);
    Media::withoutGlobalScope(TenantScope::class)->create(['title' => 'B', 'tenant_id' => 2]);

    expect(Media::count())->toBe(2);
});

test('tenancy disabled shows all media', function (): void {
    config(['filament-media-library.tenancy.enabled' => false]);

    Media::withoutGlobalScope(TenantScope::class)->create(['title' => 'A', 'tenant_id' => 1]);
    Media::withoutGlobalScope(TenantScope::class)->create(['title' => 'B', 'tenant_id' => 2]);
    Media::withoutGlobalScope(TenantScope::class)->create(['title' => 'C', 'tenant_id' => null]);

    expect(Media::count())->toBe(3);
});

test('tenancy enabled filters by tenant id', function (): void {
    config(['filament-media-library.tenancy.enabled' => true]);

    // Mock Filament::getTenant() to return tenant with id 1
    Filament\Facades\Filament::shouldReceive('getTenant')
        ->andReturn(new class
        {
            public function getKey(): int
            {
                return 1;
            }
        });

    Media::withoutGlobalScope(TenantScope::class)->create(['title' => 'Tenant 1 A', 'tenant_id' => 1]);
    Media::withoutGlobalScope(TenantScope::class)->create(['title' => 'Tenant 1 B', 'tenant_id' => 1]);
    Media::withoutGlobalScope(TenantScope::class)->create(['title' => 'Tenant 2', 'tenant_id' => 2]);

    $results = Media::all();

    expect($results)->toHaveCount(2);
    expect($results->pluck('title')->toArray())->each->toContain('Tenant 1');
});

test('tenant id is auto-populated on create when tenancy enabled', function (): void {
    config(['filament-media-library.tenancy.enabled' => true]);

    Filament\Facades\Filament::shouldReceive('getTenant')
        ->andReturn(new class
        {
            public function getKey(): int
            {
                return 42;
            }
        });

    $media = Media::withoutGlobalScope(TenantScope::class)->create(['title' => 'Auto Tenant']);

    expect($media->tenant_id)->toBe(42);
});

test('withoutGlobalScope bypasses tenant filtering', function (): void {
    config(['filament-media-library.tenancy.enabled' => true]);

    Filament\Facades\Filament::shouldReceive('getTenant')
        ->andReturn(new class
        {
            public function getKey(): int
            {
                return 1;
            }
        });

    Media::withoutGlobalScope(TenantScope::class)->create(['title' => 'Scoped', 'tenant_id' => 1]);
    Media::withoutGlobalScope(TenantScope::class)->create(['title' => 'Other', 'tenant_id' => 99]);

    $all = Media::withoutGlobalScope(TenantScope::class)->get();
    $scoped = Media::all();

    expect($all)->toHaveCount(2);
    expect($scoped)->toHaveCount(1);
});

test('cross-tenant isolation works', function (): void {
    config(['filament-media-library.tenancy.enabled' => true]);

    Media::withoutGlobalScope(TenantScope::class)->create(['title' => 'Tenant A Item', 'tenant_id' => 10]);
    Media::withoutGlobalScope(TenantScope::class)->create(['title' => 'Tenant B Item', 'tenant_id' => 20]);

    // Simulate tenant A
    Filament\Facades\Filament::shouldReceive('getTenant')
        ->andReturn(new class
        {
            public function getKey(): int
            {
                return 10;
            }
        });

    $tenantAMedia = Media::all();

    expect($tenantAMedia)->toHaveCount(1);
    expect($tenantAMedia->first()->title)->toBe('Tenant A Item');
});
