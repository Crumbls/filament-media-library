<?php

declare(strict_types=1);

use Crumbls\FilamentMediaLibrary\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->testModel = FmlTestModel::create([
        'name' => 'FML Test User',
        'email' => 'fml-trait-'.uniqid().'@test.com',
        'password' => bcrypt('password'),
    ]);
});

// --- Relationship ---

test('mediaLibrary returns MorphToMany relationship', function (): void {
    expect($this->testModel->mediaLibrary())->toBeInstanceOf(MorphToMany::class);
});

test('mediaLibrary returns empty collection initially', function (): void {
    expect($this->testModel->mediaLibrary()->count())->toBe(0);
});

// --- Attach ---

test('can attach media by id', function (): void {
    $media = Media::create(['title' => 'Attach By ID']);

    $this->testModel->attachMedia($media->id);

    expect($this->testModel->mediaLibrary()->count())->toBe(1);
    expect($this->testModel->mediaLibrary()->first()->id)->toBe($media->id);
});

test('can attach media by model instance', function (): void {
    $media = Media::create(['title' => 'Attach By Instance']);

    $this->testModel->attachMedia($media);

    expect($this->testModel->mediaLibrary()->count())->toBe(1);
});

test('attach sets default collection', function (): void {
    $media = Media::create(['title' => 'Default Collection']);
    $this->testModel->attachMedia($media);

    $pivot = $this->testModel->mediaLibrary()->first()->pivot;

    expect($pivot->collection)->toBe('default');
});

test('attach can set custom collection', function (): void {
    $media = Media::create(['title' => 'Custom Collection']);
    $this->testModel->attachMedia($media, 'featured');

    $pivot = $this->testModel->mediaLibrary()->first()->pivot;

    expect($pivot->collection)->toBe('featured');
});

test('attach auto-increments order', function (): void {
    $media1 = Media::create(['title' => 'Order 1']);
    $media2 = Media::create(['title' => 'Order 2']);

    $this->testModel->attachMedia($media1);
    $this->testModel->attachMedia($media2);

    $items = $this->testModel->mediaLibrary()->get();

    expect($items[0]->pivot->order)->toBe(0);
    expect($items[1]->pivot->order)->toBe(1);
});

test('attach can set custom order', function (): void {
    $media = Media::create(['title' => 'Custom Order']);
    $this->testModel->attachMedia($media, 'default', 5);

    $pivot = $this->testModel->mediaLibrary()->first()->pivot;

    expect($pivot->order)->toBe(5);
});

// --- Attach Many ---

test('can attach multiple media at once', function (): void {
    $media1 = Media::create(['title' => 'Multi 1']);
    $media2 = Media::create(['title' => 'Multi 2']);
    $media3 = Media::create(['title' => 'Multi 3']);

    $this->testModel->attachMediaMany([$media1->id, $media2->id, $media3->id]);

    expect($this->testModel->mediaLibrary()->count())->toBe(3);
});

test('attachMediaMany accepts model instances', function (): void {
    $media1 = Media::create(['title' => 'Instance 1']);
    $media2 = Media::create(['title' => 'Instance 2']);

    $this->testModel->attachMediaMany([$media1, $media2]);

    expect($this->testModel->mediaLibrary()->count())->toBe(2);
});

test('attachMediaMany sets sequential order', function (): void {
    $media1 = Media::create(['title' => 'Seq 1']);
    $media2 = Media::create(['title' => 'Seq 2']);

    $this->testModel->attachMediaMany([$media1->id, $media2->id]);

    $items = $this->testModel->mediaLibrary()->get();

    expect($items[0]->pivot->order)->toBe(0);
    expect($items[1]->pivot->order)->toBe(1);
});

test('attachMediaMany continues order after existing items', function (): void {
    $media1 = Media::create(['title' => 'Existing']);
    $media2 = Media::create(['title' => 'New 1']);
    $media3 = Media::create(['title' => 'New 2']);

    $this->testModel->attachMedia($media1);
    $this->testModel->attachMediaMany([$media2->id, $media3->id]);

    $items = $this->testModel->mediaLibrary()->get();

    expect($items[0]->pivot->order)->toBe(0);
    expect($items[1]->pivot->order)->toBe(1);
    expect($items[2]->pivot->order)->toBe(2);
});

// --- Detach ---

test('can detach media by model instance', function (): void {
    $media = Media::create(['title' => 'Detach Instance']);
    $this->testModel->attachMedia($media);

    $this->testModel->detachMedia($media);

    expect($this->testModel->mediaLibrary()->count())->toBe(0);
});

test('can detach media by id', function (): void {
    $media = Media::create(['title' => 'Detach ID']);
    $this->testModel->attachMedia($media);

    $this->testModel->detachMedia($media->id);

    expect($this->testModel->mediaLibrary()->count())->toBe(0);
});

test('detaching media does not delete the media record', function (): void {
    $media = Media::create(['title' => 'Persist After Detach']);
    $this->testModel->attachMedia($media);

    $this->testModel->detachMedia($media);

    expect(Media::find($media->id))->not->toBeNull();
});

test('detaching one media does not affect others', function (): void {
    $media1 = Media::create(['title' => 'Keep']);
    $media2 = Media::create(['title' => 'Remove']);

    $this->testModel->attachMedia($media1);
    $this->testModel->attachMedia($media2);

    $this->testModel->detachMedia($media2);

    expect($this->testModel->mediaLibrary()->count())->toBe(1);
    expect($this->testModel->mediaLibrary()->first()->id)->toBe($media1->id);
});

// --- Sync ---

test('syncMedia replaces existing media in collection', function (): void {
    $media1 = Media::create(['title' => 'Old']);
    $media2 = Media::create(['title' => 'New 1']);
    $media3 = Media::create(['title' => 'New 2']);

    $this->testModel->attachMedia($media1);
    $this->testModel->syncMedia([$media2->id, $media3->id]);

    $ids = $this->testModel->mediaLibrary()->pluck('media_library.id')->toArray();

    expect($ids)->toContain($media2->id)
        ->toContain($media3->id)
        ->not->toContain($media1->id);
});

test('syncMedia preserves media in other collections', function (): void {
    $media1 = Media::create(['title' => 'Gallery Item']);
    $media2 = Media::create(['title' => 'Featured Item']);
    $media3 = Media::create(['title' => 'New Default']);

    $this->testModel->attachMedia($media1, 'gallery');
    $this->testModel->attachMedia($media2, 'default');

    $this->testModel->syncMedia([$media3->id], 'default');

    expect($this->testModel->mediaInCollection('gallery')->count())->toBe(1);
    expect($this->testModel->mediaInCollection('default')->count())->toBe(1);
    expect($this->testModel->mediaInCollection('default')->first()->id)->toBe($media3->id);
});

// --- Collection filtering ---

test('mediaInCollection returns only items in that collection', function (): void {
    $media1 = Media::create(['title' => 'Gallery']);
    $media2 = Media::create(['title' => 'Featured']);

    $this->testModel->attachMedia($media1, 'gallery');
    $this->testModel->attachMedia($media2, 'featured');

    expect($this->testModel->mediaInCollection('gallery')->count())->toBe(1);
    expect($this->testModel->mediaInCollection('gallery')->first()->id)->toBe($media1->id);
});

test('mediaInCollection returns empty when collection has no items', function (): void {
    expect($this->testModel->mediaInCollection('empty')->count())->toBe(0);
});

test('getMediaByCollection returns Collection instance', function (): void {
    $result = $this->testModel->getMediaByCollection('gallery');

    expect($result)->toBeInstanceOf(Collection::class);
});

test('getMediaByCollection defaults to default collection', function (): void {
    $media = Media::create(['title' => 'Default']);
    $this->testModel->attachMedia($media, 'default');

    $result = $this->testModel->getMediaByCollection();

    expect($result)->toHaveCount(1);
});

// --- Ordering ---

test('media is returned in order', function (): void {
    $media1 = Media::create(['title' => 'Third']);
    $media2 = Media::create(['title' => 'First']);
    $media3 = Media::create(['title' => 'Second']);

    $this->testModel->attachMedia($media1, 'default', 2);
    $this->testModel->attachMedia($media2, 'default', 0);
    $this->testModel->attachMedia($media3, 'default', 1);

    $items = $this->testModel->mediaLibrary()->get();

    expect($items[0]->id)->toBe($media2->id);
    expect($items[1]->id)->toBe($media3->id);
    expect($items[2]->id)->toBe($media1->id);
});

// --- Multiple models sharing media ---

test('same media can be attached to multiple models', function (): void {
    $media = Media::create(['title' => 'Shared']);

    $model2 = FmlTestModel::create([
        'name' => 'Second User',
        'email' => 'fml-trait-2-'.uniqid().'@test.com',
        'password' => bcrypt('password'),
    ]);

    $this->testModel->attachMedia($media);
    $model2->attachMedia($media);

    expect($this->testModel->mediaLibrary()->count())->toBe(1);
    expect($model2->mediaLibrary()->count())->toBe(1);
});

test('detaching from one model does not affect the other', function (): void {
    $media = Media::create(['title' => 'Shared Detach']);

    $model2 = FmlTestModel::create([
        'name' => 'Other User',
        'email' => 'fml-trait-3-'.uniqid().'@test.com',
        'password' => bcrypt('password'),
    ]);

    $this->testModel->attachMedia($media);
    $model2->attachMedia($media);

    $this->testModel->detachMedia($media);

    expect($this->testModel->mediaLibrary()->count())->toBe(0);
    expect($model2->mediaLibrary()->count())->toBe(1);
});

// --- Detach with collection ---

test('detachMedia with collection only detaches from that collection', function (): void {
    $media = Media::create(['title' => 'Multi Collection']);
    $this->testModel->attachMedia($media, 'gallery');
    $this->testModel->attachMedia($media, 'featured');

    $this->testModel->detachMedia($media, 'gallery');

    expect($this->testModel->mediaInCollection('gallery')->count())->toBe(0);
    expect($this->testModel->mediaInCollection('featured')->count())->toBe(1);
});

test('detachMedia without collection detaches from all collections', function (): void {
    $media = Media::create(['title' => 'Detach All']);
    $this->testModel->attachMedia($media, 'gallery');
    $this->testModel->attachMedia($media, 'featured');

    $this->testModel->detachMedia($media);

    expect($this->testModel->mediaLibrary()->count())->toBe(0);
});

// --- Sync edge cases ---

test('syncMedia with empty array clears the collection', function (): void {
    $media = Media::create(['title' => 'Clear Sync']);
    $this->testModel->attachMedia($media);

    $this->testModel->syncMedia([]);

    expect($this->testModel->mediaInCollection('default')->count())->toBe(0);
});

test('syncMedia does not delete the media records', function (): void {
    $media1 = Media::create(['title' => 'Sync Keep 1']);
    $media2 = Media::create(['title' => 'Sync Keep 2']);
    $this->testModel->attachMedia($media1);

    $this->testModel->syncMedia([$media2->id]);

    expect(Media::find($media1->id))->not->toBeNull();
    expect(Media::find($media2->id))->not->toBeNull();
});

// --- Attach duplicates ---

test('attaching same media to same collection throws unique constraint', function (): void {
    $media = Media::create(['title' => 'Duplicate Attach']);
    $this->testModel->attachMedia($media, 'default');

    expect(fn () => $this->testModel->attachMedia($media, 'default'))
        ->toThrow(\Illuminate\Database\UniqueConstraintViolationException::class);
});

test('attaching same media to different collections is allowed', function (): void {
    $media = Media::create(['title' => 'Multi Collection Attach']);
    $this->testModel->attachMedia($media, 'default');
    $this->testModel->attachMedia($media, 'gallery');

    expect($this->testModel->mediaLibrary()->count())->toBe(2);
});

test('attachMediaMany with custom collection', function (): void {
    $media1 = Media::create(['title' => 'Batch Gallery 1']);
    $media2 = Media::create(['title' => 'Batch Gallery 2']);

    $this->testModel->attachMediaMany([$media1->id, $media2->id], 'gallery');

    expect($this->testModel->mediaInCollection('gallery')->count())->toBe(2);
    expect($this->testModel->mediaInCollection('default')->count())->toBe(0);
});
