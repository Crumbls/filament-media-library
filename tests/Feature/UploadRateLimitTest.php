<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    config(['filament-media-library.disk' => 'public']);

    $this->user = createTestUser();
    $this->actingAs($this->user);
});

test('upload works within rate limit', function (): void {
    config([
        'filament-media-library.routes.upload_throttle' => [
            'enabled' => true,
            'max_attempts' => 5,
            'decay_minutes' => 1,
        ],
    ]);

    $file = UploadedFile::fake()->image('test.jpg', 100, 100);

    $response = $this->postJson(route('filament-media-library.media-picker.upload'), [
        'files' => [$file],
    ]);

    $response->assertOk();
    expect($response->json('uploaded'))->toHaveCount(1);
});

// Config is read inside the RateLimiter closure on each request,
// so changing config in tests affects rate limiting behavior.
test('upload returns 429 after exceeding max attempts', function (): void {
    config([
        'filament-media-library.routes.upload_throttle' => [
            'enabled' => true,
            'max_attempts' => 3,
            'decay_minutes' => 1,
        ],
    ]);

    for ($i = 0; $i < 3; $i++) {
        $file = UploadedFile::fake()->image("test-{$i}.jpg", 10, 10);

        $this->postJson(route('filament-media-library.media-picker.upload'), [
            'files' => [$file],
        ])->assertOk();
    }

    $file = UploadedFile::fake()->image('over-limit.jpg', 10, 10);

    $response = $this->postJson(route('filament-media-library.media-picker.upload'), [
        'files' => [$file],
    ]);

    $response->assertStatus(429);
});

test('disabled throttle allows unlimited uploads', function (): void {
    config([
        'filament-media-library.routes.upload_throttle' => [
            'enabled' => false,
            'max_attempts' => 1,
            'decay_minutes' => 1,
        ],
    ]);

    for ($i = 0; $i < 5; $i++) {
        $file = UploadedFile::fake()->image("test-{$i}.jpg", 10, 10);

        $this->postJson(route('filament-media-library.media-picker.upload'), [
            'files' => [$file],
        ])->assertOk();
    }
});
