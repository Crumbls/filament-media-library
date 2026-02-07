<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary\Models;

use Crumbls\FilamentMediaLibrary\Database\Factories\MediaFactory;
use Crumbls\FilamentMediaLibrary\Events\MediaCreated;
use Crumbls\FilamentMediaLibrary\Events\MediaDeleted;
use Crumbls\FilamentMediaLibrary\Events\MediaUpdated;
use Crumbls\FilamentMediaLibrary\Scopes\TenantScope;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;

class Media extends Model implements HasMedia
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    use InteractsWithMedia;

    protected $table = 'media_library';

    protected $fillable = [
        'uuid',
        'title',
        'alt_text',
        'caption',
        'description',
        'disk',
        'uploaded_by',
        'tenant_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'uploaded_by' => 'integer',
    ];

    protected static function newFactory(): MediaFactory
    {
        return MediaFactory::new();
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Media $media): void {
            if (empty($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }

            if (empty($media->disk)) {
                $media->disk = config('filament-media-library.disk', 'public');
            }

            if (empty($media->tenant_id) && TenantScope::shouldScope()) {
                $media->tenant_id = TenantScope::resolveTenantId();
            }
        });

        static::created(fn (Media $media) => event(new MediaCreated($media)));
        static::updated(fn (Media $media) => event(new MediaUpdated($media)));
        static::deleted(fn (Media $media) => event(new MediaDeleted($media)));
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        $escaped = str_replace(['%', '_'], ['\%', '\_'], $search);

        return $query->where(function ($q) use ($escaped): void {
            $q->where('title', 'like', "%{$escaped}%")
                ->orWhere('alt_text', 'like', "%{$escaped}%")
                ->orWhere('caption', 'like', "%{$escaped}%")
                ->orWhere('description', 'like', "%{$escaped}%");
        });
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        if (! $type) {
            return $query;
        }

        return match ($type) {
            'image' => $query->whereHas('media', fn ($q) => $q->where('mime_type', 'like', 'image/%')),
            'video' => $query->whereHas('media', fn ($q) => $q->where('mime_type', 'like', 'video/%')),
            'document' => $query->whereHas('media', fn ($q) => $q->where('mime_type', 'not like', 'image/%')->where('mime_type', 'not like', 'video/%')),
            default => $query,
        };
    }

    public function toPickerArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title ?? $this->file_name,
            'thumbnail_url' => $this->thumbnail_url,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
        ];
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

    public static function mimeTypeMatchesAccepted(string $mimeType, array $acceptedTypes): bool
    {
        foreach ($acceptedTypes as $pattern) {
            if ($pattern === $mimeType) {
                return true;
            }

            if (str_contains($pattern, '/*')) {
                $prefix = Str::before($pattern, '/*');

                if (str_starts_with($mimeType, "{$prefix}/")) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function sanitizeFileName(string $name, ?string $mimeType = null): string
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $filename = pathinfo($name, PATHINFO_FILENAME);

        if ($mimeType) {
            $derivedExtension = static::deriveExtensionFromMime($mimeType);

            if ($derivedExtension) {
                $extension = $derivedExtension;
            }
        }

        $slug = Str::slug($filename);

        if ($slug === '') {
            $slug = 'file';
        }

        return "{$slug}.{$extension}";
    }

    public static function deriveExtensionFromMime(string $mimeType): ?string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            'image/bmp' => 'bmp',
            'image/tiff' => 'tiff',
            'image/avif' => 'avif',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            'application/pdf' => 'pdf',
            'application/zip' => 'zip',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        ];

        return $map[$mimeType] ?? null;
    }

    public function tenant(): BelongsTo
    {
        $tenantModel = Filament::getTenantModel();
        $column = config('filament-media-library.tenancy.column', 'tenant_id');

        if (! $tenantModel) {
            return $this->belongsTo(self::class, 'id', 'id');
        }

        return $this->belongsTo($tenantModel, $column);
    }
}
