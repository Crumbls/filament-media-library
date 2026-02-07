<?php

declare(strict_types=1);

use Crumbls\FilamentMediaLibrary\Models\Media;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    config(['filament-media-library.disk' => 'public']);

    $this->user = createTestUser();
    $this->actingAs($this->user);
});

// --- Index route ---

test('index route returns paginated media', function (): void {
    Media::create(['title' => 'Index Item 1']);
    Media::create(['title' => 'Index Item 2']);

    $response = $this->getJson(route('filament-media-library.media-picker'));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [['id', 'title', 'thumbnail_url', 'file_name', 'mime_type']],
        'has_more',
        'total',
    ]);
    expect($response->json('total'))->toBeGreaterThanOrEqual(2);
});

test('index route filters by search', function (): void {
    Media::create(['title' => 'Searchable Needle']);
    Media::create(['title' => 'Other Item']);

    $response = $this->getJson(route('filament-media-library.media-picker', ['search' => 'Needle']));

    $response->assertOk();
    $titles = collect($response->json('data'))->pluck('title')->toArray();

    expect($titles)->toContain('Searchable Needle');
    expect($titles)->not->toContain('Other Item');
});

test('index route filters by type image', function (): void {
    $image = Media::create(['title' => 'An Image']);
    $image->addMedia(createTestGifFile())
        ->usingFileName('image.gif')
        ->toMediaCollection('default');

    $doc = Media::create(['title' => 'A Document']);
    $doc->addMedia(createTestPdfFile())
        ->usingFileName('document.pdf')
        ->toMediaCollection('default');

    $response = $this->getJson(route('filament-media-library.media-picker', ['type' => 'image']));

    $response->assertOk();
    $titles = collect($response->json('data'))->pluck('title')->toArray();

    expect($titles)->toContain('An Image');
    expect($titles)->not->toContain('A Document');
});

test('index route requires authentication', function (): void {
    auth()->logout();

    $response = $this->getJson(route('filament-media-library.media-picker'));

    $response->assertUnauthorized();
});

// --- Upload route ---

test('upload creates media records and returns them', function (): void {
    $file = UploadedFile::fake()->image('test-photo.jpg', 100, 100);

    $response = $this->postJson(route('filament-media-library.media-picker.upload'), [
        'files' => [$file],
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'uploaded' => [['id', 'title', 'thumbnail_url', 'file_name', 'mime_type']],
        'rejected',
    ]);

    expect($response->json('uploaded'))->toHaveCount(1);
    expect($response->json('uploaded.0.title'))->toBe('test-photo');
});

test('upload rejects oversized files', function (): void {
    config(['filament-media-library.max_file_size' => 1]);

    $file = UploadedFile::fake()->image('big.jpg')->size(2048);

    $response = $this->postJson(route('filament-media-library.media-picker.upload'), [
        'files' => [$file],
    ]);

    $response->assertOk();
    expect($response->json('uploaded'))->toHaveCount(0);
    expect($response->json('rejected'))->toHaveCount(1);
    expect($response->json('rejected.0.reason'))->toBe(__('filament-media-library::media-library.validation.file_too_large'));
});

test('upload rejects invalid mime types', function (): void {
    config(['filament-media-library.accepted_file_types' => ['image/*']]);

    $file = UploadedFile::fake()->create('script.php', 10, 'application/x-php');

    $response = $this->postJson(route('filament-media-library.media-picker.upload'), [
        'files' => [$file],
    ]);

    $response->assertOk();
    expect($response->json('uploaded'))->toHaveCount(0);
    expect($response->json('rejected'))->toHaveCount(1);
    expect($response->json('rejected.0.reason'))->toBe(__('filament-media-library::media-library.validation.type_not_accepted'));
});

test('upload requires authentication', function (): void {
    auth()->logout();

    $file = UploadedFile::fake()->image('test.jpg');

    $response = $this->postJson(route('filament-media-library.media-picker.upload'), [
        'files' => [$file],
    ]);

    $response->assertUnauthorized();
});

// --- Show route ---

test('show returns full media detail', function (): void {
    $media = Media::create(['title' => 'Detail Test', 'alt_text' => 'Alt', 'caption' => 'Cap']);
    $media->addMedia(createTestGifFile())
        ->usingFileName('detail.gif')
        ->toMediaCollection('default');

    $response = $this->getJson(route('filament-media-library.media-picker.show', $media->id));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'id', 'title', 'alt_text', 'caption', 'description',
            'file_name', 'file_url', 'thumbnail_url', 'mime_type',
            'file_size', 'is_image', 'is_video', 'is_pdf', 'created_at',
        ],
    ]);
    $response->assertJsonPath('data.title', 'Detail Test');
    $response->assertJsonPath('data.is_image', true);
});

test('show returns 404 for nonexistent media', function (): void {
    $response = $this->getJson(route('filament-media-library.media-picker.show', 99999));

    $response->assertNotFound();
});

test('show requires authentication', function (): void {
    auth()->logout();
    $media = Media::create(['title' => 'Auth Test']);

    $response = $this->getJson(route('filament-media-library.media-picker.show', $media->id));

    $response->assertUnauthorized();
});

// --- Update route ---

test('update saves metadata', function (): void {
    $media = Media::create(['title' => 'Original']);

    $response = $this->patchJson(route('filament-media-library.media-picker.update', $media->id), [
        'title' => 'Updated Title',
        'alt_text' => 'Updated Alt',
        'caption' => 'Updated Caption',
        'description' => 'Updated Description',
    ]);

    $response->assertOk();
    $response->assertJsonPath('data.title', 'Updated Title');
    $response->assertJsonPath('data.alt_text', 'Updated Alt');

    $media->refresh();
    expect($media->title)->toBe('Updated Title');
    expect($media->alt_text)->toBe('Updated Alt');
    expect($media->caption)->toBe('Updated Caption');
    expect($media->description)->toBe('Updated Description');
});

test('update validates field lengths', function (): void {
    $media = Media::create(['title' => 'Validate Test']);

    $response = $this->patchJson(route('filament-media-library.media-picker.update', $media->id), [
        'title' => str_repeat('a', 256),
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors('title');
});

test('update returns 404 for nonexistent media', function (): void {
    $response = $this->patchJson(route('filament-media-library.media-picker.update', 99999), [
        'title' => 'Test',
    ]);

    $response->assertNotFound();
});

test('update requires authentication', function (): void {
    auth()->logout();
    $media = Media::create(['title' => 'Auth Update']);

    $response = $this->patchJson(route('filament-media-library.media-picker.update', $media->id), [
        'title' => 'Nope',
    ]);

    $response->assertUnauthorized();
});

// --- Destroy route ---

test('destroy removes media', function (): void {
    $media = Media::create(['title' => 'Delete Me']);
    $media->addMedia(createTestGifFile())
        ->usingFileName('delete.gif')
        ->toMediaCollection('default');

    $response = $this->deleteJson(route('filament-media-library.media-picker.destroy', $media->id));

    $response->assertOk();
    $response->assertJsonPath('success', true);

    expect(Media::find($media->id))->toBeNull();
});

test('destroy returns 404 for nonexistent media', function (): void {
    $response = $this->deleteJson(route('filament-media-library.media-picker.destroy', 99999));

    $response->assertNotFound();
});

test('destroy requires authentication', function (): void {
    auth()->logout();
    $media = Media::create(['title' => 'Auth Delete']);

    $response = $this->deleteJson(route('filament-media-library.media-picker.destroy', $media->id));

    $response->assertUnauthorized();
});
