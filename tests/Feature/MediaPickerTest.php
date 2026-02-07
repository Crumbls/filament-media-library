<?php

declare(strict_types=1);

use Crumbls\FilamentMediaLibrary\Forms\Components\MediaPicker;
use Crumbls\FilamentMediaLibrary\Models\Media;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');
    config(['filament-media-library.disk' => 'public']);
});

// --- getAvailableMedia ---

test('getAvailableMedia returns correct structure', function (): void {
    Media::create(['title' => 'Available 1']);
    Media::create(['title' => 'Available 2']);

    $picker = MediaPicker::make('media_id');
    $result = $picker->getAvailableMedia();

    expect($result)->toHaveKeys(['data', 'has_more', 'total']);
    expect($result['total'])->toBeGreaterThanOrEqual(2);
    expect($result['data'])->toBeArray();
});

test('getAvailableMedia returns media item structure', function (): void {
    Media::create(['title' => 'Structure Test']);

    $picker = MediaPicker::make('media_id');
    $result = $picker->getAvailableMedia();

    $item = collect($result['data'])->firstWhere('title', 'Structure Test');

    expect($item)->not->toBeNull();
    expect($item)->toHaveKeys(['id', 'title', 'thumbnail_url', 'file_name', 'mime_type']);
});

test('getAvailableMedia filters by search term', function (): void {
    Media::create(['title' => 'Unique Picker Needle']);
    Media::create(['title' => 'Other Item']);

    $picker = MediaPicker::make('media_id');
    $result = $picker->getAvailableMedia('Unique Picker Needle');

    $titles = array_column($result['data'], 'title');

    expect($titles)->toContain('Unique Picker Needle');
});

test('getAvailableMedia searches alt_text', function (): void {
    Media::create(['title' => 'Alt Picker', 'alt_text' => 'picker-alt-unique']);

    $picker = MediaPicker::make('media_id');
    $result = $picker->getAvailableMedia('picker-alt-unique');

    $titles = array_column($result['data'], 'title');

    expect($titles)->toContain('Alt Picker');
});

test('getAvailableMedia searches caption', function (): void {
    Media::create(['title' => 'Caption Picker', 'caption' => 'picker-caption-unique']);

    $picker = MediaPicker::make('media_id');
    $result = $picker->getAvailableMedia('picker-caption-unique');

    $titles = array_column($result['data'], 'title');

    expect($titles)->toContain('Caption Picker');
});

test('getAvailableMedia paginates results', function (): void {
    for ($i = 0; $i < 5; $i++) {
        Media::create(['title' => "Paginated Picker {$i}"]);
    }

    $picker = MediaPicker::make('media_id');
    $result = $picker->getAvailableMedia('', 1, 2);

    expect(count($result['data']))->toBeLessThanOrEqual(2);
    expect($result['total'])->toBeGreaterThanOrEqual(5);
    expect($result['has_more'])->toBeTrue();
});

test('getAvailableMedia returns empty data for no matches', function (): void {
    $picker = MediaPicker::make('media_id');
    $result = $picker->getAvailableMedia('nonexistent-search-term-xyz-12345');

    expect($result['data'])->toBe([]);
    expect($result['total'])->toBe(0);
});

test('getAvailableMedia orders by created_at descending', function (): void {
    $old = Media::create(['title' => 'Picker Old']);
    $new = Media::create(['title' => 'Picker New']);

    $picker = MediaPicker::make('media_id');
    $result = $picker->getAvailableMedia();

    $ids = array_column($result['data'], 'id');
    $oldIndex = array_search($old->id, $ids);
    $newIndex = array_search($new->id, $ids);

    if ($oldIndex !== false && $newIndex !== false) {
        expect($newIndex)->toBeLessThan($oldIndex);
    }
});

// --- getAvailableMedia uses title as fallback for null title ---

test('getAvailableMedia searches description', function (): void {
    Media::create(['title' => 'Desc Picker', 'description' => 'picker-description-unique']);

    $picker = MediaPicker::make('media_id');
    $result = $picker->getAvailableMedia('picker-description-unique');

    $titles = array_column($result['data'], 'title');

    expect($titles)->toContain('Desc Picker');
});

test('getAvailableMedia uses file_name fallback when title is null', function (): void {
    $media = Media::create(['title' => null]);
    $media->addMedia(createTestGifFile())
        ->usingFileName('fallback-name.gif')
        ->toMediaCollection('default');

    $picker = MediaPicker::make('media_id');
    $result = $picker->getAvailableMedia();

    $item = collect($result['data'])->firstWhere('id', $media->id);

    expect($item)->not->toBeNull();
    expect($item['title'])->toBe('fallback-name.gif');
});
