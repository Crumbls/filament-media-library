<?php

declare(strict_types=1);

use Crumbls\FilamentMediaLibrary\Models\Media;
use Crumbls\FilamentMediaLibrary\Resources\MediaResource;
use Crumbls\FilamentMediaLibrary\Resources\MediaResource\Pages\ListMedia;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function (): void {
    Storage::fake('public');
    config(['filament-media-library.disk' => 'public']);

    $this->user = \App\Models\User::first();
    $this->actingAs($this->user);
});

// --- Page accessibility ---

test('list page is accessible when authenticated', function (): void {
    $this->get(MediaResource::getUrl('index'))
        ->assertSuccessful();
});

test('list page requires authentication', function (): void {
    auth()->logout();

    $this->get(MediaResource::getUrl('index'))
        ->assertRedirect();
});

// --- Grid media query ---

test('getGridMedia returns paginated results', function (): void {
    Media::create(['title' => 'Grid Test']);

    $page = Livewire::test(ListMedia::class);

    $page->assertSee('Grid Test');
});

test('search filters media by title', function (): void {
    Media::create(['title' => 'Findable Needle']);
    Media::create(['title' => 'Invisible Item']);

    $page = Livewire::test(ListMedia::class)
        ->set('gridSearch', 'Findable Needle');

    $page->assertSee('Findable Needle');
});

test('search filters media by alt text', function (): void {
    Media::create(['title' => 'AltSearch', 'alt_text' => 'unique-alt-text-xyz']);

    Livewire::test(ListMedia::class)
        ->set('gridSearch', 'unique-alt-text-xyz')
        ->assertSee('AltSearch');
});

test('type filter shows only images', function (): void {
    $image = Media::create(['title' => 'Test Image']);
    $image->addMedia(createTestGifFile())
        ->usingFileName('image.gif')
        ->toMediaCollection('default');

    $doc = Media::create(['title' => 'Test Doc']);
    $doc->addMedia(createTestTextFile())
        ->usingFileName('doc.txt')
        ->toMediaCollection('default');

    Livewire::test(ListMedia::class)
        ->set('filterType', 'image')
        ->assertSee('Test Image');
});

test('type filter shows only documents', function (): void {
    $doc = Media::create(['title' => 'Filterable Doc']);
    $doc->addMedia(createTestTextFile())
        ->usingFileName('doc.txt')
        ->toMediaCollection('default');

    Livewire::test(ListMedia::class)
        ->set('filterType', 'document')
        ->assertSee('Filterable Doc');
});

// --- Pagination state ---

test('search resets page to 1', function (): void {
    Livewire::test(ListMedia::class)
        ->set('gridPage', 3)
        ->set('gridSearch', 'something')
        ->assertSet('gridPage', 1);
});

test('filter type resets page to 1', function (): void {
    Livewire::test(ListMedia::class)
        ->set('gridPage', 3)
        ->set('filterType', 'image')
        ->assertSet('gridPage', 1);
});

test('setGridPage updates the page number', function (): void {
    Livewire::test(ListMedia::class)
        ->call('setGridPage', 5)
        ->assertSet('gridPage', 5);
});

// --- Upload zone ---

test('upload zone is hidden by default', function (): void {
    Livewire::test(ListMedia::class)
        ->assertSet('showUploadZone', false);
});

test('toggleUploadZone shows the upload zone', function (): void {
    Livewire::test(ListMedia::class)
        ->call('toggleUploadZone')
        ->assertSet('showUploadZone', true);
});

test('toggleUploadZone hides when called twice', function (): void {
    Livewire::test(ListMedia::class)
        ->call('toggleUploadZone')
        ->call('toggleUploadZone')
        ->assertSet('showUploadZone', false);
});

test('toggleUploadZone clears uploads array', function (): void {
    Livewire::test(ListMedia::class)
        ->call('toggleUploadZone')
        ->assertSet('uploads', []);
});

// --- Media detail modal ---

test('openMediaDetail sets editing state', function (): void {
    $media = Media::create([
        'title' => 'Detail Test',
        'alt_text' => 'Alt',
        'caption' => 'Cap',
        'description' => 'Desc',
    ]);

    Livewire::test(ListMedia::class)
        ->call('openMediaDetail', $media->id)
        ->assertSet('editingMediaId', $media->id)
        ->assertSet('editTitle', 'Detail Test')
        ->assertSet('editAltText', 'Alt')
        ->assertSet('editCaption', 'Cap')
        ->assertSet('editDescription', 'Desc');
});

test('openMediaDetail handles null metadata gracefully', function (): void {
    $media = Media::create(['title' => null]);

    Livewire::test(ListMedia::class)
        ->call('openMediaDetail', $media->id)
        ->assertSet('editingMediaId', $media->id)
        ->assertSet('editTitle', '')
        ->assertSet('editAltText', '')
        ->assertSet('editCaption', '')
        ->assertSet('editDescription', '');
});

test('closeMediaDetail resets editing state', function (): void {
    $media = Media::create(['title' => 'Close Test']);

    Livewire::test(ListMedia::class)
        ->call('openMediaDetail', $media->id)
        ->call('closeMediaDetail')
        ->assertSet('editingMediaId', null)
        ->assertSet('editTitle', '')
        ->assertSet('editAltText', '')
        ->assertSet('editCaption', '')
        ->assertSet('editDescription', '');
});

// --- Save detail ---

test('saveMediaDetail updates media record', function (): void {
    $media = Media::create(['title' => 'Before']);

    Livewire::test(ListMedia::class)
        ->call('openMediaDetail', $media->id)
        ->set('editTitle', 'After')
        ->set('editAltText', 'New Alt')
        ->set('editCaption', 'New Caption')
        ->set('editDescription', 'New Description')
        ->call('saveMediaDetail');

    $media->refresh();

    expect($media->title)->toBe('After');
    expect($media->alt_text)->toBe('New Alt');
    expect($media->caption)->toBe('New Caption');
    expect($media->description)->toBe('New Description');
});

test('saveMediaDetail does nothing when no media is being edited', function (): void {
    Livewire::test(ListMedia::class)
        ->assertSet('editingMediaId', null)
        ->call('saveMediaDetail');
});

// --- Delete ---

test('deleteMedia removes the media record', function (): void {
    $media = Media::create(['title' => 'Delete Me']);
    $id = $media->id;

    Livewire::test(ListMedia::class)
        ->call('openMediaDetail', $media->id)
        ->call('deleteMedia');

    expect(Media::find($id))->toBeNull();
});

test('deleteMedia clears editing state', function (): void {
    $media = Media::create(['title' => 'Delete Clear']);

    Livewire::test(ListMedia::class)
        ->call('openMediaDetail', $media->id)
        ->call('deleteMedia')
        ->assertSet('editingMediaId', null)
        ->assertSet('editTitle', '');
});

test('deleteMedia accepts explicit id parameter', function (): void {
    $media = Media::create(['title' => 'Delete By ID']);
    $id = $media->id;

    Livewire::test(ListMedia::class)
        ->call('deleteMedia', $media->id);

    expect(Media::find($id))->toBeNull();
});

test('deleteMedia does nothing when no id provided and nothing being edited', function (): void {
    $countBefore = Media::count();

    Livewire::test(ListMedia::class)
        ->call('deleteMedia');

    expect(Media::count())->toBe($countBefore);
});

// --- getEditingMedia ---

test('getEditingMedia returns null when nothing is being edited', function (): void {
    Livewire::test(ListMedia::class)
        ->assertSet('editingMediaId', null);
});

test('getEditingMedia returns media when editing', function (): void {
    $media = Media::create(['title' => 'Editing']);

    $component = Livewire::test(ListMedia::class)
        ->call('openMediaDetail', $media->id);

    $editingMedia = $component->instance()->getEditingMedia();

    expect($editingMedia)->not->toBeNull();
    expect($editingMedia->id)->toBe($media->id);
});

// --- Search by caption and description ---

test('search filters media by caption', function (): void {
    Media::create(['title' => 'CaptionSearch', 'caption' => 'unique-caption-xyz']);

    Livewire::test(ListMedia::class)
        ->set('gridSearch', 'unique-caption-xyz')
        ->assertSee('CaptionSearch');
});

test('search filters media by description', function (): void {
    Media::create(['title' => 'DescSearch', 'description' => 'unique-description-xyz']);

    Livewire::test(ListMedia::class)
        ->set('gridSearch', 'unique-description-xyz')
        ->assertSee('DescSearch');
});

// --- Video type filter ---

test('type filter for video does not error', function (): void {
    Livewire::test(ListMedia::class)
        ->set('filterType', 'video')
        ->assertSet('filterType', 'video')
        ->assertSet('gridPage', 1);
});

// --- Combined search + filter ---

test('search and filter work together', function (): void {
    $image = Media::create(['title' => 'Combined Image']);
    $image->addMedia(createTestGifFile())
        ->usingFileName('combined.gif')
        ->toMediaCollection('default');

    $doc = Media::create(['title' => 'Combined Doc']);
    $doc->addMedia(createTestTextFile())
        ->usingFileName('combined.txt')
        ->toMediaCollection('default');

    Livewire::test(ListMedia::class)
        ->set('gridSearch', 'Combined')
        ->set('filterType', 'image')
        ->assertSee('Combined Image');
});

// --- Default property values ---

test('gridSearch defaults to empty string', function (): void {
    Livewire::test(ListMedia::class)
        ->assertSet('gridSearch', '');
});

test('gridPage defaults to 1', function (): void {
    Livewire::test(ListMedia::class)
        ->assertSet('gridPage', 1);
});

test('filterType defaults to empty string', function (): void {
    Livewire::test(ListMedia::class)
        ->assertSet('filterType', '');
});

test('perPage defaults to 32', function (): void {
    Livewire::test(ListMedia::class)
        ->assertSet('perPage', 32);
});

test('isUploading defaults to false', function (): void {
    Livewire::test(ListMedia::class)
        ->assertSet('isUploading', false);
});

// --- deleteMedia force deletes ---

test('deleteMedia force deletes the record', function (): void {
    $media = Media::create(['title' => 'Force Delete']);
    $id = $media->id;

    Livewire::test(ListMedia::class)
        ->call('deleteMedia', $id);

    expect(Media::withTrashed()->find($id))->toBeNull();
});

// --- saveMediaDetail dispatches event ---

test('openMediaDetail dispatches fml-modal-opened event', function (): void {
    $media = Media::create(['title' => 'Event Test']);

    Livewire::test(ListMedia::class)
        ->call('openMediaDetail', $media->id)
        ->assertDispatched('fml-modal-opened');
});

test('saveMediaDetail dispatches fml-detail-saved event', function (): void {
    $media = Media::create(['title' => 'Save Event']);

    Livewire::test(ListMedia::class)
        ->call('openMediaDetail', $media->id)
        ->set('editTitle', 'Updated')
        ->call('saveMediaDetail')
        ->assertDispatched('fml-detail-saved');
});

// --- MIME type validation ---

test('mimeTypeMatchesAccepted matches exact types', function (): void {
    expect(Media::mimeTypeMatchesAccepted('application/pdf', ['application/pdf']))->toBeTrue();
    expect(Media::mimeTypeMatchesAccepted('application/pdf', ['image/*']))->toBeFalse();
});

test('mimeTypeMatchesAccepted matches wildcard types', function (): void {
    expect(Media::mimeTypeMatchesAccepted('image/png', ['image/*']))->toBeTrue();
    expect(Media::mimeTypeMatchesAccepted('image/jpeg', ['image/*']))->toBeTrue();
    expect(Media::mimeTypeMatchesAccepted('video/mp4', ['image/*']))->toBeFalse();
});

test('mimeTypeMatchesAccepted matches against multiple patterns', function (): void {
    $accepted = ['image/*', 'video/*', 'application/pdf'];

    expect(Media::mimeTypeMatchesAccepted('image/png', $accepted))->toBeTrue();
    expect(Media::mimeTypeMatchesAccepted('video/mp4', $accepted))->toBeTrue();
    expect(Media::mimeTypeMatchesAccepted('application/pdf', $accepted))->toBeTrue();
    expect(Media::mimeTypeMatchesAccepted('application/exe', $accepted))->toBeFalse();
});

test('mimeTypeMatchesAccepted rejects empty mime type', function (): void {
    expect(Media::mimeTypeMatchesAccepted('', ['image/*']))->toBeFalse();
});

// --- Filename sanitization ---

test('sanitizeFileName removes special characters', function (): void {
    expect(Media::sanitizeFileName('my file (1).jpg'))->toBe('my-file-1.jpg');
});

test('sanitizeFileName removes path traversal', function (): void {
    expect(Media::sanitizeFileName('../../etc/passwd.txt'))->toBe('passwd.txt');
    expect(Media::sanitizeFileName('../malicious.php'))->toBe('malicious.php');
});

test('sanitizeFileName handles empty filename', function (): void {
    expect(Media::sanitizeFileName('.jpg'))->toBe('file.jpg');
});

test('sanitizeFileName strips double extensions', function (): void {
    $result = Media::sanitizeFileName('shell.php.jpg');

    expect($result)->not->toContain('.php');
    expect(str_ends_with($result, '.jpg'))->toBeTrue();
});

test('sanitizeFileName preserves normal filenames', function (): void {
    expect(Media::sanitizeFileName('my-photo.png'))->toBe('my-photo.png');
});

// --- Validation rules on saveMediaDetail ---

test('saveMediaDetail rejects title exceeding 255 characters', function (): void {
    $media = Media::create(['title' => 'Validate']);

    Livewire::test(ListMedia::class)
        ->call('openMediaDetail', $media->id)
        ->set('editTitle', str_repeat('a', 256))
        ->call('saveMediaDetail')
        ->assertHasErrors(['editTitle' => 'max']);
});

test('saveMediaDetail rejects alt text exceeding 255 characters', function (): void {
    $media = Media::create(['title' => 'Validate Alt']);

    Livewire::test(ListMedia::class)
        ->call('openMediaDetail', $media->id)
        ->set('editAltText', str_repeat('a', 256))
        ->call('saveMediaDetail')
        ->assertHasErrors(['editAltText' => 'max']);
});

test('saveMediaDetail rejects caption exceeding 1000 characters', function (): void {
    $media = Media::create(['title' => 'Validate Caption']);

    Livewire::test(ListMedia::class)
        ->call('openMediaDetail', $media->id)
        ->set('editCaption', str_repeat('a', 1001))
        ->call('saveMediaDetail')
        ->assertHasErrors(['editCaption' => 'max']);
});

test('saveMediaDetail rejects description exceeding 5000 characters', function (): void {
    $media = Media::create(['title' => 'Validate Desc']);

    Livewire::test(ListMedia::class)
        ->call('openMediaDetail', $media->id)
        ->set('editDescription', str_repeat('a', 5001))
        ->call('saveMediaDetail')
        ->assertHasErrors(['editDescription' => 'max']);
});

test('saveMediaDetail accepts valid-length fields', function (): void {
    $media = Media::create(['title' => 'Valid Lengths']);

    Livewire::test(ListMedia::class)
        ->call('openMediaDetail', $media->id)
        ->set('editTitle', str_repeat('a', 255))
        ->set('editAltText', str_repeat('b', 255))
        ->set('editCaption', str_repeat('c', 1000))
        ->set('editDescription', str_repeat('d', 5000))
        ->call('saveMediaDetail')
        ->assertHasNoErrors()
        ->assertDispatched('fml-detail-saved');
});
