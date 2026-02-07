<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary\Forms\Components;

use Closure;
use Crumbls\FilamentMediaLibrary\Models\Media;
use Filament\Forms\Components\Field;

class MediaPicker extends Field
{
    protected string $view = 'filament-media-library::components.media-picker';

    protected bool|Closure $isMultiple = false;

    protected string|Closure $collection = 'default';

    protected int|Closure $maxItems = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (MediaPicker $component, $state): void {
            if (is_null($state)) {
                $component->state($component->isMultiple() ? [] : null);
            }
        });

        $this->dehydrateStateUsing(function (MediaPicker $component, $state) {
            if ($component->isMultiple()) {
                return is_array($state) ? $state : [];
            }

            return $state;
        });
    }

    public function multiple(bool|Closure $condition = true): static
    {
        $this->isMultiple = $condition;

        return $this;
    }

    public function isMultiple(): bool
    {
        return (bool) $this->evaluate($this->isMultiple);
    }

    public function collection(string|Closure $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    public function getCollection(): string
    {
        return (string) $this->evaluate($this->collection);
    }

    public function maxItems(int|Closure $maxItems): static
    {
        $this->maxItems = $maxItems;

        return $this;
    }

    public function getMaxItems(): int
    {
        return (int) $this->evaluate($this->maxItems);
    }

    public function getSelectedMedia(): array
    {
        $state = $this->getState();

        if (empty($state)) {
            return [];
        }

        $ids = $this->isMultiple() ? (array) $state : [(int) $state];

        return Media::query()
            ->with('media')
            ->whereIn('id', $ids)
            ->get()
            ->map(fn (Media $media) => [
                'id' => $media->id,
                'title' => $media->title ?? $media->file_name,
                'thumbnail_url' => $media->thumbnail_url,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
            ])
            ->toArray();
    }

    public function getAvailableMedia(string $search = '', int $page = 1, int $perPage = 24): array
    {
        $query = Media::query()->with('media');

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('alt_text', 'like', "%{$search}%")
                    ->orWhere('caption', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $query->orderByDesc('created_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()->map(fn (Media $media) => [
                'id' => $media->id,
                'title' => $media->title ?? $media->file_name,
                'thumbnail_url' => $media->thumbnail_url,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
            ])->toArray(),
            'has_more' => $paginator->hasMorePages(),
            'total' => $paginator->total(),
        ];
    }
}
