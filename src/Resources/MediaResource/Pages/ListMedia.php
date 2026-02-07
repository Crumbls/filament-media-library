<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary\Resources\MediaResource\Pages;

use Crumbls\FilamentMediaLibrary\Models\Media;
use Crumbls\FilamentMediaLibrary\Resources\MediaResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Attributes\Validate;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ListMedia extends ListRecords
{
    use WithFileUploads;

    protected static string $resource = MediaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uploadMedia')
                ->label('Upload Files')
                ->icon('heroicon-m-arrow-up-tray')
                ->action('toggleUploadZone'),
        ];
    }

    #[Url(as: 'search')]
    public string $gridSearch = '';

    #[Url(as: 'page')]
    public int $gridPage = 1;

    #[Url(as: 'type')]
    public string $filterType = '';

    public int $perPage = 32;

    public bool $showUploadZone = false;

    /** @var array<TemporaryUploadedFile> */
    public array $uploads = [];

    public bool $isUploading = false;

    /** @var TemporaryUploadedFile|null */
    public $editedImage = null;

    public ?int $editingMediaId = null;

    #[Validate('max:255')]
    public string $editTitle = '';

    #[Validate('max:255')]
    public string $editAltText = '';

    #[Validate('max:1000')]
    public string $editCaption = '';

    #[Validate('max:5000')]
    public string $editDescription = '';

    public function toggleUploadZone(): void
    {
        $this->showUploadZone = ! $this->showUploadZone;
        $this->uploads = [];
    }

    public function updatedUploads(): void
    {
        $this->isUploading = true;

        $acceptedTypes = config('filament-media-library.accepted_file_types', ['image/*', 'video/*', 'application/pdf']);
        $maxSize = config('filament-media-library.max_file_size', 10240);
        $rejected = [];

        foreach ($this->uploads as $file) {
            if (! $file instanceof TemporaryUploadedFile) {
                continue;
            }

            $originalName = $file->getClientOriginalName();

            if ($file->getSize() > $maxSize * 1024) {
                $rejected[] = ['name' => $originalName, 'reason' => 'File exceeds maximum size'];

                continue;
            }

            if (! Media::mimeTypeMatchesAccepted($file->getMimeType() ?? '', $acceptedTypes)) {
                $rejected[] = ['name' => $originalName, 'reason' => 'File type not accepted'];

                continue;
            }

            $safeName = Media::sanitizeFileName($originalName, $file->getMimeType());

            try {
                $media = Media::create([
                    'title' => pathinfo($originalName, PATHINFO_FILENAME),
                    'uploaded_by' => Auth::id(),
                ]);

                $media->addMedia($file->getRealPath())
                    ->usingFileName($safeName)
                    ->toMediaCollection('default');
            } catch (\Throwable $e) {
                $rejected[] = ['name' => $originalName, 'reason' => 'Upload failed'];

                if (isset($media) && $media->exists) {
                    $media->forceDelete();
                }
            }
        }

        $this->uploads = [];
        $this->isUploading = false;

        if (! empty($rejected)) {
            $this->dispatch('fml-uploads-rejected', rejected: $rejected);
        }

        $this->dispatch('fml-uploads-complete');
    }

    public function updatedEditedImage(): void
    {
        if (! $this->editingMediaId || ! $this->editedImage) {
            return;
        }

        $media = Media::findOrFail($this->editingMediaId);
        $originalFileName = $media->getFirstMedia('default')?->file_name ?? 'edited-image.jpg';

        $media->clearMediaCollection('default');
        $media->addMedia($this->editedImage->getRealPath())
            ->usingFileName($originalFileName)
            ->toMediaCollection('default');

        $this->editedImage = null;
        $this->dispatch('fml-image-edited');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('filament-media-library::pages.media-grid'),
            ]);
    }

    public function getGridMedia(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Media::query()->with('media');

        if ($this->gridSearch) {
            $escaped = str_replace(['%', '_'], ['\%', '\_'], $this->gridSearch);
            $query->where(function ($q) use ($escaped): void {
                $q->where('title', 'like', "%{$escaped}%")
                    ->orWhere('alt_text', 'like', "%{$escaped}%")
                    ->orWhere('caption', 'like', "%{$escaped}%")
                    ->orWhere('description', 'like', "%{$escaped}%");
            });
        }

        if ($this->filterType === 'image') {
            $query->whereHas('media', fn ($q) => $q->where('mime_type', 'like', 'image/%'));
        } elseif ($this->filterType === 'video') {
            $query->whereHas('media', fn ($q) => $q->where('mime_type', 'like', 'video/%'));
        } elseif ($this->filterType === 'document') {
            $query->whereHas('media', fn ($q) => $q->where('mime_type', 'not like', 'image/%')->where('mime_type', 'not like', 'video/%'));
        }

        return $query->orderByDesc('created_at')->paginate($this->perPage, ['*'], 'page', $this->gridPage);
    }

    public function updatedGridSearch(): void
    {
        $this->gridPage = 1;
    }

    public function updatedFilterType(): void
    {
        $this->gridPage = 1;
    }

    public function setGridPage(int $page): void
    {
        $this->gridPage = $page;
    }

    public function openMediaDetail(int $id): void
    {
        $media = Media::with('media')->findOrFail($id);

        $this->editingMediaId = $media->id;
        $this->editTitle = $media->title ?? '';
        $this->editAltText = $media->alt_text ?? '';
        $this->editCaption = $media->caption ?? '';
        $this->editDescription = $media->description ?? '';

        $this->dispatch('fml-modal-opened');
    }

    public function getEditingMedia(): ?Media
    {
        if (! $this->editingMediaId) {
            return null;
        }

        return Media::with('media')->find($this->editingMediaId);
    }

    public function saveMediaDetail(): void
    {
        if (! $this->editingMediaId) {
            return;
        }

        $this->validate();

        $media = Media::findOrFail($this->editingMediaId);

        $media->update([
            'title' => $this->editTitle,
            'alt_text' => $this->editAltText,
            'caption' => $this->editCaption,
            'description' => $this->editDescription,
        ]);

        Notification::make()
            ->title('Media details saved')
            ->success()
            ->send();

        $this->dispatch('fml-detail-saved');
    }

    public function deleteMedia(?int $id = null): void
    {
        $id = $id ?? $this->editingMediaId;

        if (! $id) {
            return;
        }

        $media = Media::findOrFail($id);
        $media->clearMediaCollection('default');
        $media->forceDelete();

        $this->closeMediaDetail();
    }

    public function closeMediaDetail(): void
    {
        $this->editingMediaId = null;
        $this->editTitle = '';
        $this->editAltText = '';
        $this->editCaption = '';
        $this->editDescription = '';
    }
}
