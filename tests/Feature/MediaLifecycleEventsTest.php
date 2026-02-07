<?php

declare(strict_types=1);

use Crumbls\FilamentMediaLibrary\Events\MediaCreated;
use Crumbls\FilamentMediaLibrary\Events\MediaDeleted;
use Crumbls\FilamentMediaLibrary\Events\MediaUpdated;
use Crumbls\FilamentMediaLibrary\Models\Media;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->user = createTestUser();
    $this->actingAs($this->user);
});

test('MediaCreated event fires on create', function (): void {
    Event::fake([MediaCreated::class]);

    $media = Media::create(['title' => 'Event Test']);

    Event::assertDispatched(MediaCreated::class, function (MediaCreated $event) use ($media): bool {
        return $event->media->id === $media->id;
    });
});

test('MediaUpdated event fires on update', function (): void {
    Event::fake([MediaUpdated::class]);

    $media = Media::withoutEvents(fn () => Media::create(['title' => 'Before Update']));

    $media->update(['title' => 'After Update']);

    Event::assertDispatched(MediaUpdated::class, function (MediaUpdated $event) use ($media): bool {
        return $event->media->id === $media->id;
    });
});

test('MediaDeleted event fires on delete', function (): void {
    Event::fake([MediaDeleted::class]);

    $media = Media::withoutEvents(fn () => Media::create(['title' => 'Delete Me']));

    $media->delete();

    Event::assertDispatched(MediaDeleted::class, function (MediaDeleted $event) use ($media): bool {
        return $event->media->id === $media->id;
    });
});

test('MediaCreated does not fire on update', function (): void {
    $media = Media::create(['title' => 'Original']);

    Event::fake([MediaCreated::class]);

    $media->update(['title' => 'Changed']);

    Event::assertNotDispatched(MediaCreated::class);
});

test('event carries the correct media instance', function (): void {
    Event::fake([MediaCreated::class]);

    $media = Media::create(['title' => 'Instance Check', 'alt_text' => 'Alt']);

    Event::assertDispatched(MediaCreated::class, function (MediaCreated $event): bool {
        return $event->media->title === 'Instance Check'
            && $event->media->alt_text === 'Alt';
    });
});
