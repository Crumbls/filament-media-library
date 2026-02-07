# Filament Media Library

A WordPress-style media library for Filament 5. Provides a central media pool with polymorphic attachments, so any model in your application can reference shared media through a clean pivot table.

Built on top of [Spatie Media Library](https://github.com/spatie/laravel-medialibrary) for file handling and image conversions.

## Requirements

- PHP 8.2+
- Laravel 12+
- Filament 5+
- Spatie Laravel Media Library 11+

## Installation

```bash
composer require crumbls/filament-media-library
```

The package auto-discovers its service provider. Migrations run automatically -- no publishing required.

Run the migrations:

```bash
php artisan migrate
```

Register the plugin in your Filament panel provider:

```php
use Crumbls\FilamentMediaLibrary\FilamentMediaLibraryPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugins([
            FilamentMediaLibraryPlugin::make(),
        ]);
}
```

## Configuration

Publish the config file to customize settings:

```bash
php artisan vendor:publish --tag=filament-media-library-config
```

```php
// config/filament-media-library.php
return [
    // Storage disk (any disk defined in config/filesystems.php)
    'disk' => env('MEDIA_LIBRARY_DISK', 'public'),

    // Allowed upload types (glob-style MIME matching)
    'accepted_file_types' => ['image/*', 'video/*', 'application/pdf'],

    // Max upload size in kilobytes
    'max_file_size' => 10240,

    // Automatic image conversions
    'image_conversions' => [
        'thumbnail' => ['width' => 150, 'height' => 150],
        'medium'    => ['width' => 300, 'height' => 300],
        'large'     => ['width' => 1024, 'height' => 1024],
    ],

    // Named collections (for future use)
    'collections' => [],

    // Swap the Media model with your own
    'models' => [
        'media' => \Crumbls\FilamentMediaLibrary\Models\Media::class,
    ],

    // Filament navigation
    'filament' => [
        'navigation_group' => null,
        'navigation_icon'  => 'heroicon-o-photo',
        'navigation_sort'  => null,
        'navigation_label' => 'Media Library',
    ],
];
```

## Usage

### Media Library Page

Once the plugin is registered, a "Media Library" page appears in your Filament panel navigation. It provides:

- Grid view of all uploaded media with thumbnails
- Drag-and-drop file uploads
- Search across title, alt text, caption, and description
- Type filtering (images, videos, documents)
- Detail modal with editable metadata (title, alt text, caption, description)
- Inline image editing (crop, rotate, flip) via CropperJS
- File URL display for easy copying

### Attaching Media to Models

Add the `HasMediaLibrary` trait to any Eloquent model:

```php
use Crumbls\FilamentMediaLibrary\Traits\HasMediaLibrary;

class Post extends Model
{
    use HasMediaLibrary;
}
```

This gives you a full API for managing media attachments:

```php
$post = Post::find(1);

// Attach a single media item
$post->attachMedia($mediaId);

// Attach with a named collection
$post->attachMedia($mediaId, 'featured-image');

// Attach with explicit order
$post->attachMedia($mediaId, 'gallery', order: 3);

// Attach multiple at once (order is auto-incremented)
$post->attachMediaMany([$mediaId1, $mediaId2, $mediaId3]);

// Attach multiple to a specific collection
$post->attachMediaMany([$mediaId1, $mediaId2], 'gallery');

// Detach media
$post->detachMedia($mediaId);

// Detach from a specific collection only
$post->detachMedia($mediaId, 'gallery');

// Sync a collection (replaces existing attachments)
$post->syncMedia([$mediaId1, $mediaId2], 'gallery');

// Query media in a collection
$galleryItems = $post->mediaInCollection('gallery')->get();

// Get all media (default collection)
$media = $post->getMediaByCollection();

// Access the relationship directly
$post->mediaLibrary; // MorphToMany, ordered by pivot 'order'
```

### MediaPicker Form Component

Use the `MediaPicker` in any Filament form to let users select media from the library:

```php
use Crumbls\FilamentMediaLibrary\Forms\Components\MediaPicker;

public static function form(Schema $schema): Schema
{
    return $schema->components([
        MediaPicker::make('featured_image_id'),
    ]);
}
```

**Multiple selection:**

```php
MediaPicker::make('gallery_ids')
    ->multiple(),
```

**Limit selections:**

```php
MediaPicker::make('gallery_ids')
    ->multiple()
    ->maxItems(5),
```

**Named collection:**

```php
MediaPicker::make('hero_image_id')
    ->collection('hero'),
```

The picker opens a modal with search, pagination, and thumbnail previews. Selected media is stored as an ID (single) or array of IDs (multiple) on the model.

#### Database Column Requirements

The MediaPicker stores a `media_library.id` reference directly on your model's table. You must ensure:

1. **The column exists** -- Add a nullable unsigned big integer column for the field name you pass to `MediaPicker::make()`.
2. **The column is fillable** -- Add the column name to your model's `$fillable` array.

Example migration for adding an `avatar` column to a `users` table:

```php
Schema::table('users', function (Blueprint $table) {
    $table->unsignedBigInteger('avatar')->nullable();

    $table->foreign('avatar')
        ->references('id')
        ->on('media_library')
        ->nullOnDelete();
});
```

Then in your model:

```php
protected $fillable = [
    // ...
    'avatar',
];
```

If you have an accessor on the same column (e.g., `getAvatarAttribute()`), remove it -- the MediaPicker needs the raw integer ID during form hydration. Use a separate method to resolve the media URL:

```php
use Crumbls\FilamentMediaLibrary\Models\Media;

public function getAvatarUrl(): ?string
{
    if (! $this->avatar) {
        return null;
    }

    $media = Media::with('media')->find($this->avatar);

    return $media?->thumbnail_url;
}
```

### MediaColumn Table Column

Display media thumbnails in Filament tables:

```php
use Crumbls\FilamentMediaLibrary\Tables\Columns\MediaColumn;

public static function table(Table $table): Table
{
    return $table->columns([
        MediaColumn::make('media')
            ->collection('featured-image')
            ->size(50)
            ->circular(),
    ]);
}
```

**Options:**

```php
// Set thumbnail size in pixels (default: 40)
MediaColumn::make('media')->size(60)

// Circular thumbnail (default: false)
MediaColumn::make('media')->circular()

// Square thumbnail (default: true)
MediaColumn::make('media')->square(false)

// Specify collection (default: 'default')
MediaColumn::make('media')->collection('gallery')
```

**Performance tip:** Eager-load the media relation to avoid N+1 queries:

```php
public static function table(Table $table): Table
{
    return $table
        ->modifyQueryUsing(fn ($query) => $query->with('mediaLibrary.media'))
        ->columns([
            MediaColumn::make('media'),
        ]);
}
```

## Media Model

The `Media` model wraps Spatie Media Library and provides convenient accessors:

```php
use Crumbls\FilamentMediaLibrary\Models\Media;

$media = Media::find(1);

$media->file_url;       // Full URL to the original file
$media->thumbnail_url;  // Thumbnail conversion URL (falls back to original)
$media->mime_type;      // e.g. 'image/jpeg'
$media->file_size;      // Size in bytes
$media->file_name;      // Stored file name

$media->isImage();      // true for image/* MIME types
$media->isVideo();      // true for video/* MIME types
$media->isPdf();        // true for application/pdf
```

**Metadata fields:**

| Field | Type | Max Length |
|-------|------|-----------|
| `title` | string | 255 |
| `alt_text` | string | 255 |
| `caption` | text | 1000 |
| `description` | text | 5000 |

The model uses soft deletes. Each record gets an auto-generated UUID and tracks `uploaded_by` (foreign key to users).

## Database Schema

The package creates two tables:

**`media_library`** -- Central media records

| Column | Type |
|--------|------|
| id | bigint (PK) |
| uuid | string (unique) |
| title | string (nullable) |
| alt_text | string (nullable) |
| caption | text (nullable) |
| description | text (nullable) |
| disk | string (default: 'public') |
| uploaded_by | foreign key to users (nullable) |
| created_at | timestamp |
| updated_at | timestamp |
| deleted_at | timestamp (soft delete) |

**`mediables`** -- Polymorphic pivot table

| Column | Type |
|--------|------|
| id | bigint (PK) |
| media_id | foreign key to media_library |
| mediable_type | string |
| mediable_id | bigint |
| collection | string (default: 'default') |
| order | unsigned int (default: 0) |
| created_at | timestamp |
| updated_at | timestamp |

Spatie Media Library also creates its own `media` table for the underlying file records.

## Publishing Assets

```bash
# Config
php artisan vendor:publish --tag=filament-media-library-config

# Migrations (only if you need to customize them)
php artisan vendor:publish --tag=filament-media-library-migrations

# Views (for template customization)
php artisan vendor:publish --tag=filament-media-library-views
```

## Storage Disk

Set the storage disk via environment variable or config:

```env
MEDIA_LIBRARY_DISK=s3
```

Any disk defined in `config/filesystems.php` works -- `public`, `s3`, `r2`, or a custom disk.

## Extending the Media Model

Swap in your own Media model by updating the config:

```php
// config/filament-media-library.php
'models' => [
    'media' => App\Models\CustomMedia::class,
],
```

Your custom model should extend the package's Media model:

```php
namespace App\Models;

use Crumbls\FilamentMediaLibrary\Models\Media;

class CustomMedia extends Media
{
    // Add custom methods, scopes, or relationships
}
```

## Security

Uploads are validated server-side:

- **MIME type validation** -- Files are checked against `accepted_file_types` using glob-style matching (e.g., `image/*` matches `image/png`). Rejected files are not stored.
- **File size limits** -- Enforced against `max_file_size` config value.
- **Filename sanitization** -- Uploaded filenames are slugified to strip path traversal sequences, special characters, and double extensions.
- **Metadata validation** -- Title and alt text are limited to 255 characters, caption to 1000, description to 5000.

Rejected uploads dispatch an `fml-uploads-rejected` Livewire event with details for frontend notification.

## Development

### Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint) for code formatting. A pre-push git hook runs Pint automatically and blocks pushes with style violations.

Run Pint manually:

```bash
vendor/bin/pint src config database tests
```

### Static Analysis

[Larastan](https://github.com/larastan/larastan) is configured at level 5:

```bash
vendor/bin/phpstan analyse --configuration=packages/filament-media-library/phpstan.neon
```

### Testing

The package uses [Pest](https://pestphp.com/) with 197 tests:

```bash
vendor/bin/pest --test-directory packages/filament-media-library/tests --configuration packages/filament-media-library/phpunit.xml
```

## License

MIT. See [LICENSE](LICENSE).
