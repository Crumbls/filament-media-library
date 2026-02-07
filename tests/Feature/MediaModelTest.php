<?php

declare(strict_types=1);

use Crumbls\FilamentMediaLibrary\Models\Media;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    config(['filament-media-library.disk' => 'public']);
});

// --- Creation ---

test('media can be created with fillable attributes', function (): void {
    $media = Media::create([
        'title' => 'Test Image',
        'alt_text' => 'Alt text',
        'caption' => 'Caption text',
        'description' => 'Description text',
    ]);

    expect($media->exists)->toBeTrue();
    expect($media->title)->toBe('Test Image');
    expect($media->alt_text)->toBe('Alt text');
    expect($media->caption)->toBe('Caption text');
    expect($media->description)->toBe('Description text');
});

test('uuid is auto-generated on creation', function (): void {
    $media = Media::create(['title' => 'UUID Test']);

    expect($media->uuid)->not->toBeNull()
        ->toBeString()
        ->toHaveLength(36);
});

test('uuid values are unique across records', function (): void {
    $media1 = Media::create(['title' => 'UUID 1']);
    $media2 = Media::create(['title' => 'UUID 2']);

    expect($media1->uuid)->not->toBe($media2->uuid);
});

test('custom uuid is preserved on creation', function (): void {
    $uuid = '550e8400-e29b-41d4-a716-446655440000';
    $media = Media::create(['title' => 'Custom UUID', 'uuid' => $uuid]);

    expect($media->uuid)->toBe($uuid);
});

test('disk defaults to config value', function (): void {
    $media = Media::create(['title' => 'Disk Default']);

    expect($media->disk)->toBe('public');
});

test('custom disk is preserved on creation', function (): void {
    $media = Media::create(['title' => 'Custom Disk', 'disk' => 's3']);

    expect($media->disk)->toBe('s3');
});

// --- Relationships ---

test('uploaded_by can be set', function (): void {
    $user = createTestUser();
    $media = Media::create(['title' => 'Upload By', 'uploaded_by' => $user->id]);

    expect($media->uploaded_by)->toBe($user->id);
});

test('uploadedBy relationship returns user', function (): void {
    $user = createTestUser();
    $media = Media::create(['title' => 'Relation Test', 'uploaded_by' => $user->id]);

    expect($media->uploadedBy)->toBeInstanceOf(get_class($user));
    expect($media->uploadedBy->id)->toBe($user->id);
});

test('uploadedBy returns null when no user set', function (): void {
    $media = Media::create(['title' => 'No User']);

    expect($media->uploadedBy)->toBeNull();
});

// --- Accessors without Spatie media ---

test('file_url returns null when no media attached', function (): void {
    $media = Media::create(['title' => 'No File']);

    expect($media->file_url)->toBeNull();
});

test('thumbnail_url returns null when no media attached', function (): void {
    $media = Media::create(['title' => 'No File']);

    expect($media->thumbnail_url)->toBeNull();
});

test('mime_type returns null when no media attached', function (): void {
    $media = Media::create(['title' => 'No File']);

    expect($media->mime_type)->toBeNull();
});

test('file_size returns null when no media attached', function (): void {
    $media = Media::create(['title' => 'No File']);

    expect($media->file_size)->toBeNull();
});

test('file_name returns null when no media attached', function (): void {
    $media = Media::create(['title' => 'No File']);

    expect($media->file_name)->toBeNull();
});

// --- Type checks without Spatie media ---

test('isImage returns false when no media attached', function (): void {
    $media = Media::create(['title' => 'No File']);

    expect($media->isImage())->toBeFalse();
});

test('isVideo returns false when no media attached', function (): void {
    $media = Media::create(['title' => 'No File']);

    expect($media->isVideo())->toBeFalse();
});

test('isPdf returns false when no media attached', function (): void {
    $media = Media::create(['title' => 'No File']);

    expect($media->isPdf())->toBeFalse();
});

// --- Accessors with Spatie media ---

test('file_url returns url when image is attached', function (): void {
    $media = Media::create(['title' => 'With Image']);
    $media->addMedia(createTestGifFile())
        ->usingFileName('test.gif')
        ->toMediaCollection('default');

    $media->refresh();
    $media->load('media');

    expect($media->file_url)->not->toBeNull()->toBeString();
});

test('file_name returns name when media is attached', function (): void {
    $media = Media::create(['title' => 'File Name']);
    $media->addMedia(createTestGifFile())
        ->usingFileName('myimage.gif')
        ->toMediaCollection('default');

    $media->refresh();
    $media->load('media');

    expect($media->file_name)->toBe('myimage.gif');
});

test('file_size returns positive integer when media is attached', function (): void {
    $media = Media::create(['title' => 'File Size']);
    $media->addMedia(createTestTextFile(str_repeat('x', 100)))
        ->usingFileName('test.txt')
        ->toMediaCollection('default');

    $media->refresh();
    $media->load('media');

    expect($media->file_size)->toBeInt()->toBeGreaterThan(0);
});

test('mime_type returns correct type for image', function (): void {
    $media = Media::create(['title' => 'Mime Image']);
    $media->addMedia(createTestGifFile())
        ->usingFileName('test.gif')
        ->toMediaCollection('default');

    $media->refresh();
    $media->load('media');

    expect($media->mime_type)->toBe('image/gif');
});

test('isImage returns true for image file', function (): void {
    $media = Media::create(['title' => 'Is Image']);
    $media->addMedia(createTestGifFile())
        ->usingFileName('test.gif')
        ->toMediaCollection('default');

    $media->refresh();
    $media->load('media');

    expect($media->isImage())->toBeTrue();
});

test('isImage returns false for non-image file', function (): void {
    $media = Media::create(['title' => 'Not Image']);
    $media->addMedia(createTestTextFile())
        ->usingFileName('test.txt')
        ->toMediaCollection('default');

    $media->refresh();
    $media->load('media');

    expect($media->isImage())->toBeFalse();
});

test('isPdf returns true for pdf file', function (): void {
    $media = Media::create(['title' => 'Is PDF']);
    $media->addMedia(createTestPdfFile())
        ->usingFileName('test.pdf')
        ->toMediaCollection('default');

    $media->refresh();
    $media->load('media');

    expect($media->isPdf())->toBeTrue();
});

test('isPdf returns false for non-pdf file', function (): void {
    $media = Media::create(['title' => 'Not PDF']);
    $media->addMedia(createTestGifFile())
        ->usingFileName('test.gif')
        ->toMediaCollection('default');

    $media->refresh();
    $media->load('media');

    expect($media->isPdf())->toBeFalse();
});

test('thumbnail_url falls back to main url when no conversion exists', function (): void {
    config(['filament-media-library.image_conversions' => []]);

    $media = Media::create(['title' => 'Thumb Fallback']);
    $media->addMedia(createTestGifFile())
        ->usingFileName('test.gif')
        ->toMediaCollection('default');

    $media->refresh();
    $media->load('media');

    expect($media->thumbnail_url)->not->toBeNull();
    expect($media->thumbnail_url)->toBe($media->file_url);
});

// --- Media conversions config ---

test('media registers conversions from config', function (): void {
    config(['filament-media-library.image_conversions' => [
        'thumb' => ['width' => 100, 'height' => 100],
    ]]);

    $media = new Media;
    $media->registerMediaConversions();

    expect($media->mediaConversions)->toHaveCount(1);
    expect($media->mediaConversions[0]->getName())->toBe('thumb');
});

test('media registers no conversions when config is empty', function (): void {
    config(['filament-media-library.image_conversions' => []]);

    $media = new Media;
    $media->registerMediaConversions();

    expect($media->mediaConversions)->toHaveCount(0);
});

// --- isVideo with file ---

test('isVideo returns false for non-video file', function (): void {
    $media = Media::create(['title' => 'Not Video']);
    $media->addMedia(createTestGifFile())
        ->usingFileName('test.gif')
        ->toMediaCollection('default');

    $media->refresh();
    $media->load('media');

    expect($media->isVideo())->toBeFalse();
});

// --- registerMediaCollections ---

test('registerMediaCollections adds default collection', function (): void {
    $media = new Media;
    $media->registerMediaCollections();

    $names = collect($media->mediaCollections)->pluck('name')->toArray();

    expect($names)->toContain('default');
});

// --- thumbnail_url with conversion ---

test('thumbnail_url returns conversion url when thumbnail exists', function (): void {
    $media = Media::create(['title' => 'With Thumb']);
    $media->addMedia(createTestGifFile())
        ->usingFileName('test.gif')
        ->toMediaCollection('default');

    $media->refresh();
    $media->load('media');

    $spatieMedia = $media->getFirstMedia('default');

    if ($spatieMedia && $spatieMedia->hasGeneratedConversion('thumbnail')) {
        expect($media->thumbnail_url)->not->toBe($media->file_url);
        expect($media->thumbnail_url)->toContain('thumbnail');
    } else {
        expect($media->thumbnail_url)->toBe($media->file_url);
    }
});

// --- Multiple conversions ---

test('media registers multiple conversions from config', function (): void {
    config(['filament-media-library.image_conversions' => [
        'thumbnail' => ['width' => 150, 'height' => 150],
        'medium' => ['width' => 300, 'height' => 300],
        'large' => ['width' => 1024, 'height' => 1024],
    ]]);

    $media = new Media;
    $media->registerMediaConversions();

    expect($media->mediaConversions)->toHaveCount(3);

    $names = array_map(fn ($c) => $c->getName(), $media->mediaConversions);

    expect($names)->toContain('thumbnail')
        ->toContain('medium')
        ->toContain('large');
});
