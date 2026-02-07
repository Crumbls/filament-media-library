<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

class Media extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    protected $table = 'media_library';

    protected $fillable = [
        'uuid',
        'title',
        'alt_text',
        'caption',
        'description',
        'disk',
        'uploaded_by',
    ];

    protected static function booted(): void
    {
        static::creating(function (Media $media): void {
            if (empty($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }

            if (empty($media->disk)) {
                $media->disk = config('filament-media-library.disk', 'public');
            }
        });
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', 'App\\Models\\User'), 'uploaded_by');
    }

    public function registerMediaConversions(?SpatieMedia $media = null): void
    {
        $conversions = config('filament-media-library.image_conversions', []);

        foreach ($conversions as $name => $dimensions) {
            $this->addMediaConversion($name)
                ->width($dimensions['width'])
                ->height($dimensions['height'])
                ->sharpen(10)
                ->nonQueued();
        }
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('default')
            ->useDisk(config('filament-media-library.disk', 'public'));
    }

    public function getFileUrlAttribute(): ?string
    {
        $spatieMedia = $this->getFirstMedia('default');

        if (! $spatieMedia) {
            return null;
        }

        return $spatieMedia->getUrl();
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        $spatieMedia = $this->getFirstMedia('default');

        if (! $spatieMedia) {
            return null;
        }

        if ($spatieMedia->hasGeneratedConversion('thumbnail')) {
            return $spatieMedia->getUrl('thumbnail');
        }

        return $spatieMedia->getUrl();
    }

    public function getMimeTypeAttribute(): ?string
    {
        $spatieMedia = $this->getFirstMedia('default');

        return $spatieMedia?->mime_type;
    }

    public function getFileSizeAttribute(): ?int
    {
        $spatieMedia = $this->getFirstMedia('default');

        return $spatieMedia?->size;
    }

    public function getFileNameAttribute(): ?string
    {
        $spatieMedia = $this->getFirstMedia('default');

        return $spatieMedia?->file_name;
    }

    public function isImage(): bool
    {
        $mimeType = $this->mime_type;

        if (! $mimeType) {
            return false;
        }

        return str_starts_with($mimeType, 'image/');
    }

    public function isVideo(): bool
    {
        $mimeType = $this->mime_type;

        if (! $mimeType) {
            return false;
        }

        return str_starts_with($mimeType, 'video/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }
}
