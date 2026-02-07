<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary\Traits;

use Crumbls\FilamentMediaLibrary\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

trait HasMediaLibrary
{
    public function mediaLibrary(): MorphToMany
    {
        $mediaModel = config('filament-media-library.models.media', Media::class);

        return $this->morphToMany($mediaModel, 'mediable', 'mediables', 'mediable_id', 'media_id')
            ->withPivot(['collection', 'order'])
            ->withTimestamps()
            ->orderByPivot('order');
    }

    public function mediaInCollection(string $collection): MorphToMany
    {
        return $this->mediaLibrary()->wherePivot('collection', $collection);
    }

    public function attachMedia(int|Media $media, string $collection = 'default', ?int $order = null): void
    {
        $mediaId = $media instanceof Media ? $media->id : $media;

        $this->mediaLibrary()->attach($mediaId, [
            'collection' => $collection,
            'order' => $order ?? $this->getNextMediaOrder($collection),
        ]);
    }

    /**
     * @param  array<int, int|Media>  $mediaIds
     */
    public function attachMediaMany(array $mediaIds, string $collection = 'default'): void
    {
        $attachData = [];
        $order = $this->getNextMediaOrder($collection);

        foreach ($mediaIds as $media) {
            $mediaId = $media instanceof Media ? $media->id : $media;
            $attachData[$mediaId] = [
                'collection' => $collection,
                'order' => $order++,
            ];
        }

        $this->mediaLibrary()->attach($attachData);
    }

    public function detachMedia(int|Media $media, ?string $collection = null): void
    {
        $mediaId = $media instanceof Media ? $media->id : $media;

        $query = $this->mediaLibrary();

        if ($collection) {
            $query->wherePivot('collection', $collection);
        }

        $query->detach($mediaId);
    }

    /**
     * @param  array<int, int>  $mediaIds
     */
    public function syncMedia(array $mediaIds, string $collection = 'default'): void
    {
        DB::transaction(function () use ($mediaIds, $collection): void {
            $this->mediaInCollection($collection)->detach();

            $this->attachMediaMany($mediaIds, $collection);
        });
    }

    public function getMediaByCollection(string $collection = 'default'): Collection
    {
        return $this->mediaInCollection($collection)->get();
    }

    protected function getNextMediaOrder(string $collection): int
    {
        $maxOrder = $this->mediaLibrary()
            ->wherePivot('collection', $collection)
            ->max('mediables.order');

        return ($maxOrder ?? -1) + 1;
    }
}
