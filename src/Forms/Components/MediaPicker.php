<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary\Forms\Components;

use Closure;
use Crumbls\FilamentMediaLibrary\Models\Media;
use Crumbls\FilamentMediaLibrary\Traits\HasMediaLibrary;
use Filament\Forms\Components\Field;
use Illuminate\Database\Eloquent\Model;

class MediaPicker extends Field
{
    protected string $view = 'filament-media-library::components.media-picker';

    protected bool|Closure $isMultiple = false;

    protected string|Closure $collection = 'default';

    protected int|Closure $maxItems = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (MediaPicker $component, ?Model $record): void {
            if (! $record || ! $this->modelHasMediaLibrary($record)) {
                $component->state($component->isMultiple() ? [] : null);

                return;
            }

            $collection = $component->getCollection();
            $ids = $record->mediaInCollection($collection)->pluck('media_library.id')->toArray();

            $component->state($component->isMultiple() ? $ids : ($ids[0] ?? null));
        });

        $this->dehydrated(false);

        $this->saveRelationshipsUsing(function (MediaPicker $component, ?Model $record, $state): void {
            if (! $record || ! $this->modelHasMediaLibrary($record)) {
                return;
            }

            $collection = $component->getCollection();

            if ($component->isMultiple()) {
                $ids = is_array($state) ? array_filter(array_map('intval', $state)) : [];
            } else {
                $ids = ! empty($state) ? [(int) $state] : [];
            }

            $record->syncMedia($ids, $collection);
        });
    }

    protected function modelHasMediaLibrary(Model $model): bool
    {
        return in_array(HasMediaLibrary::class, class_uses_recursive($model));
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
            ->map(fn (Media $m) => $m->toPickerArray())
            ->toArray();
    }

    public function getAvailableMedia(string $search = '', int $page = 1, int $perPage = 24): array
    {
        $perPage = min($perPage, 100);

        $query = Media::query()->with('media');

        if ($search) {
            $query->search($search);
        }

        $query->orderByDesc('created_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $paginator->getCollection()->map(fn (Media $m) => $m->toPickerArray())->toArray(),
            'has_more' => $paginator->hasMorePages(),
            'total' => $paginator->total(),
        ];
    }
}
