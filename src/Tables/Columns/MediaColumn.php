<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary\Tables\Columns;

use Closure;
use Crumbls\FilamentMediaLibrary\Models\Media;
use Filament\Tables\Columns\Column;

class MediaColumn extends Column
{
    protected string $view = 'filament-media-library::components.media-column';

    protected string|Closure $collection = 'default';

    protected int|string|Closure $size = 40;

    protected bool|Closure $isCircular = false;

    protected bool|Closure $isSquare = true;

    public function collection(string|Closure $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    public function getCollection(): string
    {
        return (string) $this->evaluate($this->collection);
    }

    public function size(int|string|Closure $size): static
    {
        $this->size = $size;

        return $this;
    }

    public function getSize(): int|string
    {
        return $this->evaluate($this->size);
    }

    public function circular(bool|Closure $condition = true): static
    {
        $this->isCircular = $condition;

        return $this;
    }

    public function isCircular(): bool
    {
        return (bool) $this->evaluate($this->isCircular);
    }

    public function square(bool|Closure $condition = true): static
    {
        $this->isSquare = $condition;

        return $this;
    }

    public function isSquare(): bool
    {
        return (bool) $this->evaluate($this->isSquare);
    }

    /**
     * When using this column in a Filament table, eager-load the relation to
     * avoid N+1 queries: `->modifyQueryUsing(fn ($query) => $query->with('mediaLibrary.media'))`
     */
    public function getThumbnailUrl(): ?string
    {
        $record = $this->getRecord();

        if (! $record) {
            return null;
        }

        if ($record instanceof Media) {
            return $record->thumbnail_url;
        }

        $relation = $record->mediaLibrary ?? null;

        if (! $relation) {
            return null;
        }

        $media = $relation
            ->where('mediables.collection', $this->getCollection())
            ->first();

        return $media?->thumbnail_url;
    }
}
