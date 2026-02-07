<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary\Http\Controllers;

use Crumbls\FilamentMediaLibrary\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

class MediaPickerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search', '')->toString();
        $filterType = $request->string('type', '')->toString();
        $page = $request->integer('page', 1);
        $perPage = min($request->integer('per_page', 24), 100);

        $query = Media::query()->with('media');

        if ($search) {
            $query->search($search);
        }

        if ($filterType === 'image') {
            $query->whereHas('media', fn ($q) => $q->where('mime_type', 'like', 'image/%'));
        } elseif ($filterType === 'video') {
            $query->whereHas('media', fn ($q) => $q->where('mime_type', 'like', 'video/%'));
        } elseif ($filterType === 'document') {
            $query->whereHas('media', fn ($q) => $q->where('mime_type', 'not like', 'image/%')->where('mime_type', 'not like', 'video/%'));
        }

        $query->orderByDesc('created_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (Media $m) => $m->toPickerArray())->toArray(),
            'has_more' => $paginator->hasMorePages(),
            'total' => $paginator->total(),
        ]);
    }

    public function upload(Request $request): JsonResponse
    {
        $acceptedTypes = config('filament-media-library.accepted_file_types', ['image/*', 'video/*', 'application/pdf']);
        $maxSize = config('filament-media-library.max_file_size', 10240);

        $files = $request->file('files', []);

        if (! is_array($files)) {
            $files = [$files];
        }

        $uploaded = [];
        $rejected = [];

        foreach ($files as $file) {
            $media = null;

            if (! $file || ! $file->isValid()) {
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

                $media->load('media');

                $uploaded[] = $media->toPickerArray();
            } catch (\Throwable $e) {
                $rejected[] = ['name' => $originalName, 'reason' => 'Upload failed'];

                if (isset($media) && $media->exists) {
                    $media->forceDelete();
                }
            }
        }

        return response()->json([
            'uploaded' => $uploaded,
            'rejected' => $rejected,
        ]);
    }

    public function show(Media $media): JsonResponse
    {
        $media->load('media');

        return response()->json([
            'data' => $this->formatMediaDetail($media),
        ]);
    }

    public function update(Request $request, Media $media): JsonResponse
    {
        $media->load('media');

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $media->update($validated);

        return response()->json([
            'data' => $this->formatMediaDetail($media->fresh('media')),
        ]);
    }

    public function destroy(Media $media): JsonResponse
    {
        $media->clearMediaCollection('default');
        $media->forceDelete();

        return response()->json([
            'success' => true,
        ]);
    }

    private function formatMediaDetail(Media $media): array
    {
        return [
            'id' => $media->id,
            'title' => $media->title,
            'alt_text' => $media->alt_text,
            'caption' => $media->caption,
            'description' => $media->description,
            'file_name' => $media->file_name,
            'file_url' => $media->file_url,
            'thumbnail_url' => $media->thumbnail_url,
            'mime_type' => $media->mime_type,
            'file_size' => $media->file_size,
            'is_image' => $media->isImage(),
            'is_video' => $media->isVideo(),
            'is_pdf' => $media->isPdf(),
            'created_at' => $media->created_at?->toIso8601String(),
        ];
    }
}
