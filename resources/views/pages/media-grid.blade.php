@php
    $media = $this->getGridMedia();
    $editing = $this->getEditingMedia();
    $acceptedTypes = collect(config('filament-media-library.accepted_file_types', ['image/*', 'video/*', 'application/pdf']))->implode(',');
@endphp

<style>
    .fml-toolbar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    .fml-toolbar-left {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .fml-toolbar-right {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex: 1;
        justify-content: flex-end;
    }
    .fml-select,
    .fml-search {
        font-size: 0.875rem;
        line-height: 1.25rem;
        padding: 0.5rem 0.75rem;
        border: 1px solid rgb(209 213 219);
        border-radius: 0.5rem;
        background: white;
        color: rgb(17 24 39);
        outline: none;
        transition: border-color 150ms;
    }
    .fml-select:focus,
    .fml-search:focus {
        border-color: rgb(var(--primary-500));
        box-shadow: 0 0 0 1px rgb(var(--primary-500));
    }
    .fml-search { width: 100%; max-width: 20rem; }
    .fml-count {
        font-size: 0.875rem;
        color: rgb(107 114 128);
        white-space: nowrap;
    }

    /* Upload Zone */
    .fml-upload-zone {
        position: relative;
        margin-bottom: 1.5rem;
        padding: 3rem 2rem;
        border: 2px dashed rgb(209 213 219);
        border-radius: 0.75rem;
        background: rgb(249 250 251);
        text-align: center;
        transition: border-color 200ms, background-color 200ms;
    }
    .fml-upload-zone.fml-drag-over {
        border-color: rgb(var(--primary-500));
        background: rgba(var(--primary-500), 0.05);
    }
    .fml-upload-zone-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 9999px;
        background: rgb(243 244 246);
        margin: 0 auto 1rem;
    }
    .fml-upload-zone-icon svg { width: 1.5rem; height: 1.5rem; color: rgb(156 163 175); }
    .fml-upload-zone h3 {
        font-size: 1rem;
        font-weight: 600;
        color: rgb(17 24 39);
        margin: 0 0 0.25rem;
    }
    .fml-upload-zone p {
        font-size: 0.875rem;
        color: rgb(107 114 128);
        margin: 0 0 1rem;
    }
    .fml-btn-select-files {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.5rem 1.25rem;
        font-size: 0.875rem;
        font-weight: 600;
        border-radius: 0.5rem;
        border: 1px solid rgb(209 213 219);
        background: white;
        color: rgb(55 65 81);
        cursor: pointer;
        position: relative;
        z-index: 1;
        transition: background-color 150ms, border-color 150ms;
    }
    .fml-btn-select-files:hover {
        background: rgb(243 244 246);
        border-color: rgb(156 163 175);
    }
    .fml-upload-hint {
        font-size: 0.75rem;
        color: rgb(156 163 175);
        margin-top: 0.75rem;
    }

    /* Upload progress */
    .fml-upload-progress {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
    }
    .fml-spinner {
        width: 2rem;
        height: 2rem;
        border: 3px solid rgb(229 231 235);
        border-top-color: rgb(var(--primary-500));
        border-radius: 50%;
        animation: fml-spin 0.6s linear infinite;
    }
    @keyframes fml-spin {
        to { transform: rotate(360deg); }
    }
    .fml-spinner-sm {
        display: inline-block;
        width: 0.875rem;
        height: 0.875rem;
        border: 2px solid rgba(255,255,255,0.3);
        border-top-color: white;
        border-radius: 50%;
        animation: fml-spin 0.6s linear infinite;
    }
    .fml-upload-progress p {
        font-size: 0.875rem;
        font-weight: 500;
        color: rgb(107 114 128);
        margin: 0;
    }

    /* Grid */
    .fml-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
    @media (min-width: 640px)  { .fml-grid { grid-template-columns: repeat(4, 1fr); } }
    @media (min-width: 768px)  { .fml-grid { grid-template-columns: repeat(5, 1fr); } }
    @media (min-width: 1024px) { .fml-grid { grid-template-columns: repeat(6, 1fr); } }
    @media (min-width: 1280px) { .fml-grid { grid-template-columns: repeat(8, 1fr); } }

    /* Tiles */
    .fml-tile {
        position: relative;
        display: block;
        aspect-ratio: 1 / 1;
        overflow: hidden;
        border-radius: 0.5rem;
        border: 1px solid rgb(229 231 235);
        background: rgb(243 244 246);
        cursor: pointer;
        padding: 0;
        text-align: left;
        width: 100%;
        transition: box-shadow 150ms, border-color 150ms;
    }
    .fml-tile:hover {
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        border-color: rgb(var(--primary-500));
    }
    .fml-tile:focus {
        outline: 2px solid rgb(var(--primary-500));
        outline-offset: 2px;
    }
    .fml-tile img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .fml-tile-icon {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
        width: 100%;
        height: 100%;
        color: rgb(156 163 175);
    }
    .fml-tile-icon svg { width: 2rem; height: 2rem; }
    .fml-tile-icon span {
        font-size: 0.625rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .fml-tile-overlay {
        position: absolute;
        left: 0; right: 0; bottom: 0;
        padding: 1.5rem 0.5rem 0.5rem;
        background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
        opacity: 0;
        transition: opacity 150ms;
        pointer-events: none;
    }
    .fml-tile:hover .fml-tile-overlay { opacity: 1; }
    .fml-tile-overlay p {
        margin: 0;
        font-size: 0.75rem;
        font-weight: 500;
        color: white;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Empty */
    .fml-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 3rem;
        border: 2px dashed rgb(209 213 219);
        border-radius: 0.75rem;
        text-align: center;
    }
    .fml-empty-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3rem; height: 3rem;
        border-radius: 9999px;
        background: rgb(243 244 246);
        margin-bottom: 1rem;
    }
    .fml-empty-icon svg { width: 1.5rem; height: 1.5rem; color: rgb(156 163 175); }
    .fml-empty h3 { font-size: 0.875rem; font-weight: 500; color: rgb(17 24 39); margin: 0 0 0.25rem; }
    .fml-empty p { font-size: 0.875rem; color: rgb(107 114 128); margin: 0; }

    /* Pagination */
    .fml-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-top: 1rem;
        border-top: 1px solid rgb(229 231 235);
        margin-top: 1rem;
    }
    .fml-pagination-info { font-size: 0.875rem; color: rgb(107 114 128); }
    .fml-pagination-buttons { display: flex; align-items: center; gap: 0.25rem; }
    .fml-page-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2rem;
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 0.5rem;
        border: none;
        background: transparent;
        color: rgb(55 65 81);
        cursor: pointer;
        transition: background-color 150ms;
    }
    .fml-page-btn:hover { background: rgb(243 244 246); }
    .fml-page-btn.active { background: rgb(var(--primary-500)); color: white; }
    .fml-page-btn.active:hover { background: rgb(var(--primary-600)); }
    .fml-page-btn:disabled, .fml-page-btn.disabled {
        color: rgb(209 213 219);
        cursor: default;
        pointer-events: none;
    }
    .fml-page-btn svg { width: 1rem; height: 1rem; }

    /* Detail Modal */
    .fml-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 50;
        background: rgba(0, 0, 0, 0.6);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .fml-modal {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);
        width: 100%;
        max-width: 64rem;
        max-height: calc(100vh - 2rem);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .fml-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgb(229 231 235);
        flex-shrink: 0;
    }
    .fml-modal-header h2 {
        font-size: 1.125rem;
        font-weight: 600;
        color: rgb(17 24 39);
        margin: 0;
    }
    .fml-modal-close svg { width: 1.25rem; height: 1.25rem; }
    .fml-modal-body {
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        flex: 1;
    }
    @media (min-width: 768px) {
        .fml-modal-body { flex-direction: row; }
    }
    .fml-modal-preview {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        background: rgb(243 244 246);
        min-height: 16rem;
        padding: 1.5rem;
        position: relative;
    }
    .fml-modal-preview > img {
        max-width: 100%;
        max-height: 24rem;
        object-fit: contain;
        border-radius: 0.375rem;
    }
    .fml-modal-preview-icon {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        color: rgb(156 163 175);
    }
    .fml-modal-preview-icon svg { width: 4rem; height: 4rem; }
    .fml-modal-preview-icon span { font-size: 0.875rem; font-weight: 500; }

    /* Image Editor */
    .fml-editor-wrap {
        width: 100%;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .fml-editor-toolbar {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.25rem;
        flex-wrap: wrap;
    }
    .fml-editor-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.25rem;
        height: 2.25rem;
        border: 1px solid rgb(209 213 219);
        border-radius: 0.375rem;
        background: white;
        color: rgb(55 65 81);
        cursor: pointer;
        transition: background-color 150ms, border-color 150ms;
        padding: 0;
    }
    .fml-editor-btn:hover {
        background: rgb(243 244 246);
        border-color: rgb(156 163 175);
    }
    .fml-editor-btn svg { width: 1rem; height: 1rem; }
    .fml-editor-btn-sep {
        width: 1px;
        height: 1.5rem;
        background: rgb(209 213 219);
        margin: 0 0.25rem;
    }
    .fml-editor-canvas {
        width: 100%;
        max-height: 20rem;
        overflow: hidden;
    }
    .fml-editor-canvas img {
        display: block;
        max-width: 100%;
    }
    .fml-editor-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }
    .fml-edit-image-wrap {
        position: absolute;
        bottom: 1.5rem;
        left: 50%;
        transform: translateX(-50%);
        z-index: 2;
    }

    .fml-modal-sidebar {
        width: 100%;
        padding: 1.5rem;
        overflow-y: auto;
        border-top: 1px solid rgb(229 231 235);
    }
    @media (min-width: 768px) {
        .fml-modal-sidebar {
            width: 22rem;
            flex-shrink: 0;
            border-top: none;
            border-left: 1px solid rgb(229 231 235);
        }
    }
    .fml-field { margin-bottom: 1rem; }
    .fml-field:last-child { margin-bottom: 0; }
    .fml-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 600;
        color: rgb(55 65 81);
        margin-bottom: 0.375rem;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    .fml-input,
    .fml-textarea {
        width: 100%;
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
        border: 1px solid rgb(209 213 219);
        border-radius: 0.5rem;
        background: white;
        color: rgb(17 24 39);
        outline: none;
        transition: border-color 150ms;
        box-sizing: border-box;
    }
    .fml-input:focus,
    .fml-textarea:focus {
        border-color: rgb(var(--primary-500));
        box-shadow: 0 0 0 1px rgb(var(--primary-500));
    }
    .fml-textarea { resize: vertical; }
    .fml-meta {
        font-size: 0.75rem;
        color: rgb(107 114 128);
        margin-bottom: 1.25rem;
        line-height: 1.6;
    }
    .fml-meta-row {
        display: flex;
        justify-content: space-between;
        padding: 0.25rem 0;
        border-bottom: 1px solid rgb(243 244 246);
    }
    .fml-meta-label { font-weight: 600; color: rgb(55 65 81); }
    .fml-modal-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.5rem;
        border-top: 1px solid rgb(229 231 235);
        flex-shrink: 0;
    }

    /* Dark mode */
    .dark .fml-select, .dark .fml-search {
        background: rgba(255,255,255,0.05);
        border-color: rgba(255,255,255,0.1);
        color: white;
    }
    .dark .fml-count { color: rgb(156 163 175); }
    .dark .fml-upload-zone {
        background: rgb(17 24 39);
        border-color: rgb(55 65 81);
    }
    .dark .fml-upload-zone.fml-drag-over {
        border-color: rgb(var(--primary-500));
        background: rgba(var(--primary-500), 0.08);
    }
    .dark .fml-upload-zone-icon { background: rgb(31 41 55); }
    .dark .fml-upload-zone-icon svg { color: rgb(107 114 128); }
    .dark .fml-upload-zone h3 { color: white; }
    .dark .fml-upload-zone p { color: rgb(156 163 175); }
    .dark .fml-btn-select-files {
        background: rgb(31 41 55);
        border-color: rgb(55 65 81);
        color: rgb(209 213 219);
    }
    .dark .fml-btn-select-files:hover {
        background: rgb(55 65 81);
        border-color: rgb(75 85 99);
    }
    .dark .fml-upload-hint { color: rgb(107 114 128); }
    .dark .fml-spinner { border-color: rgb(55 65 81); border-top-color: rgb(var(--primary-500)); }
    .dark .fml-tile { border-color: rgb(55 65 81); background: rgb(31 41 55); }
    .dark .fml-tile:hover { border-color: rgb(var(--primary-500)); }
    .dark .fml-tile-icon { color: rgb(107 114 128); }
    .dark .fml-empty { border-color: rgb(55 65 81); }
    .dark .fml-empty-icon { background: rgb(31 41 55); }
    .dark .fml-empty-icon svg { color: rgb(107 114 128); }
    .dark .fml-empty h3 { color: white; }
    .dark .fml-empty p { color: rgb(156 163 175); }
    .dark .fml-pagination { border-color: rgb(55 65 81); }
    .dark .fml-pagination-info { color: rgb(156 163 175); }
    .dark .fml-page-btn { color: rgb(209 213 219); }
    .dark .fml-page-btn:hover { background: rgba(255,255,255,0.05); }
    .dark .fml-page-btn.active { background: rgb(var(--primary-500)); color: white; }
    .dark .fml-page-btn.disabled { color: rgb(75 85 99); }
    .dark .fml-modal { background: rgb(17 24 39); }
    .dark .fml-modal-header { border-color: rgb(55 65 81); }
    .dark .fml-modal-header h2 { color: white; }
    .dark .fml-modal-preview { background: rgb(31 41 55); }
    .dark .fml-modal-preview-icon { color: rgb(107 114 128); }
    .dark .fml-editor-btn {
        background: rgb(31 41 55);
        border-color: rgb(55 65 81);
        color: rgb(209 213 219);
    }
    .dark .fml-editor-btn:hover {
        background: rgb(55 65 81);
        border-color: rgb(75 85 99);
    }
    .dark .fml-editor-btn-sep { background: rgb(55 65 81); }
    .dark .fml-modal-sidebar { border-color: rgb(55 65 81); }
    .dark .fml-label { color: rgb(209 213 219); }
    .dark .fml-input, .dark .fml-textarea {
        background: rgba(255,255,255,0.05);
        border-color: rgba(255,255,255,0.1);
        color: white;
    }
    .dark .fml-meta { color: rgb(156 163 175); }
    .dark .fml-meta-row { border-color: rgb(55 65 81); }
    .dark .fml-meta-label { color: rgb(209 213 219); }
    .dark .fml-modal-footer { border-color: rgb(55 65 81); }
</style>

<div>
    {{-- Toolbar --}}
    <div class="fml-toolbar">
        <div class="fml-toolbar-left">
            <select wire:model.live="filterType" class="fml-select">
                <option value="">All Types</option>
                <option value="image">Images</option>
                <option value="video">Videos</option>
                <option value="document">Documents</option>
            </select>

            <span class="fml-count">
                {{ $media->total() }} {{ str('item')->plural($media->total()) }}
            </span>
        </div>

        <div class="fml-toolbar-right">
            <input
                wire:model.live.debounce.300ms="gridSearch"
                type="search"
                placeholder="Search media..."
                class="fml-search"
            />
        </div>
    </div>

    {{-- Upload Zone --}}
    @if($this->showUploadZone)
        <div
            class="fml-upload-zone"
            x-data="{ dragging: false }"
            x-on:dragover.prevent="dragging = true"
            x-on:dragleave.prevent="dragging = false"
            x-on:drop.prevent="dragging = false"
            x-bind:class="{ 'fml-drag-over': dragging }"
        >
            <div wire:loading.remove wire:target="uploads">
                <div class="fml-upload-zone-icon">
                    <x-heroicon-o-cloud-arrow-up />
                </div>
                <h3>Drop files to upload</h3>
                <p>or</p>
                <label class="fml-btn-select-files">
                    Select Files
                    <input
                        type="file"
                        wire:model="uploads"
                        multiple
                        accept="{{ $acceptedTypes }}"
                        style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0);"
                    />
                </label>
                <p class="fml-upload-hint">
                    Maximum file size: {{ config('filament-media-library.max_file_size', 10240) / 1024 }} MB
                </p>
            </div>

            <div wire:loading wire:target="uploads" class="fml-upload-progress">
                <div class="fml-spinner"></div>
                <p>Uploading files...</p>
            </div>
        </div>
    @endif

    {{-- Tile Grid --}}
    @if($media->isEmpty())
        <div class="fml-empty">
            <div class="fml-empty-icon">
                <x-heroicon-o-photo />
            </div>
            <h3>No media found</h3>
            <p>
                @if($this->gridSearch || $this->filterType)
                    Try adjusting your search or filter.
                @else
                    Upload your first file to get started.
                @endif
            </p>
        </div>
    @else
        <div class="fml-grid">
            @foreach($media as $item)
                <button
                    type="button"
                    wire:click="openMediaDetail({{ $item->id }})"
                    class="fml-tile"
                >
                    @if($item->isImage() && $item->thumbnail_url)
                        <img
                            src="{{ $item->thumbnail_url }}"
                            alt="{{ $item->alt_text ?? $item->title ?? '' }}"
                            loading="lazy"
                        />
                    @elseif($item->isVideo())
                        <div class="fml-tile-icon">
                            <x-heroicon-o-film />
                            <span>Video</span>
                        </div>
                    @elseif($item->isPdf())
                        <div class="fml-tile-icon">
                            <x-heroicon-o-document-text />
                            <span>PDF</span>
                        </div>
                    @else
                        <div class="fml-tile-icon">
                            <x-heroicon-o-document />
                            <span>{{ strtoupper(pathinfo($item->file_name ?? '', PATHINFO_EXTENSION)) ?: 'FILE' }}</span>
                        </div>
                    @endif

                    <div class="fml-tile-overlay">
                        <p>{{ $item->title ?? $item->file_name ?? 'Untitled' }}</p>
                    </div>
                </button>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($media->hasPages())
            <div class="fml-pagination">
                <span class="fml-pagination-info">
                    Showing {{ $media->firstItem() }}&ndash;{{ $media->lastItem() }} of {{ $media->total() }}
                </span>

                <div class="fml-pagination-buttons">
                    <button
                        wire:click="setGridPage({{ $media->currentPage() - 1 }})"
                        type="button"
                        class="fml-page-btn {{ $media->onFirstPage() ? 'disabled' : '' }}"
                        @if($media->onFirstPage()) disabled @endif
                    >
                        <x-heroicon-m-chevron-left />
                    </button>

                    @foreach($media->getUrlRange(max(1, $media->currentPage() - 2), min($media->lastPage(), $media->currentPage() + 2)) as $page => $url)
                        <button
                            wire:click="setGridPage({{ $page }})"
                            type="button"
                            class="fml-page-btn {{ $page === $media->currentPage() ? 'active' : '' }}"
                        >
                            {{ $page }}
                        </button>
                    @endforeach

                    <button
                        wire:click="setGridPage({{ $media->currentPage() + 1 }})"
                        type="button"
                        class="fml-page-btn {{ !$media->hasMorePages() ? 'disabled' : '' }}"
                        @if(!$media->hasMorePages()) disabled @endif
                    >
                        <x-heroicon-m-chevron-right />
                    </button>
                </div>
            </div>
        @endif
    @endif

    {{-- Detail Modal --}}
    @if($editing)
        <div
            class="fml-modal-backdrop"
            wire:click.self="closeMediaDetail"
            wire:keydown.escape.window="closeMediaDetail"
        >
            <div class="fml-modal" wire:click.stop>
                {{-- Header --}}
                <div class="fml-modal-header">
                    <h2>Attachment Details</h2>
                    <x-filament::icon-button
                        icon="heroicon-m-x-mark"
                        wire:click="closeMediaDetail"
                        label="Close"
                    />
                </div>

                {{-- Body --}}
                <div class="fml-modal-body">
                    {{-- Preview --}}
                    <div class="fml-modal-preview"
                        @if($editing->isImage() && $editing->file_url)
                        x-data="{
                            isEditing: false,
                            cropper: null,
                            saving: false,
                            async loadCropperLib() {
                                if (window.Cropper) return;
                                const css = document.createElement('link');
                                css.rel = 'stylesheet';
                                css.href = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css';
                                document.head.appendChild(css);
                                await new Promise((resolve, reject) => {
                                    const s = document.createElement('script');
                                    s.src = 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js';
                                    s.onload = resolve;
                                    s.onerror = reject;
                                    document.head.appendChild(s);
                                });
                            },
                            async startEdit() {
                                await this.loadCropperLib();
                                this.isEditing = true;
                                this.$nextTick(() => {
                                    this.cropper = new Cropper(this.$refs.editImg, {
                                        viewMode: 1,
                                        autoCropArea: 1,
                                        responsive: true,
                                        background: true,
                                    });
                                });
                            },
                            cancelEdit() {
                                if (this.cropper) { this.cropper.destroy(); this.cropper = null; }
                                this.isEditing = false;
                            },
                            rotate(deg) { if (this.cropper) this.cropper.rotate(deg); },
                            flipH() { if (this.cropper) this.cropper.scaleX(this.cropper.getData().scaleX === -1 ? 1 : -1); },
                            flipV() { if (this.cropper) this.cropper.scaleY(this.cropper.getData().scaleY === -1 ? 1 : -1); },
                            resetCrop() { if (this.cropper) this.cropper.reset(); },
                            async applyEdit() {
                                if (!this.cropper || this.saving) return;
                                this.saving = true;
                                const canvas = this.cropper.getCroppedCanvas();
                                const mime = '{{ $editing->mime_type }}' === 'image/png' ? 'image/png' : 'image/jpeg';
                                const ext = mime === 'image/png' ? 'png' : 'jpg';
                                canvas.toBlob(async (blob) => {
                                    const file = new File([blob], 'edited.' + ext, { type: mime });
                                    await $wire.upload('editedImage', file);
                                    this.saving = false;
                                    this.cancelEdit();
                                }, mime, 0.92);
                            }
                        }"
                        @endif
                    >
                        @if($editing->isImage() && $editing->file_url)
                            {{-- Editor toolbar (visible when editing) --}}
                            <div x-show="isEditing" x-cloak class="fml-editor-wrap">
                                <div class="fml-editor-toolbar">
                                    <button type="button" @click="rotate(-90)" class="fml-editor-btn" title="Rotate Left">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H4.598a.75.75 0 00-.75.75v3.634a.75.75 0 001.5 0v-2.033l.312.311a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm-11.23-3.15a.75.75 0 00.463-.653l-.001-.004a5.502 5.502 0 019.201-2.466l.312.311H12.623a.75.75 0 000 1.5h3.634a.75.75 0 00.75-.75V2.578a.75.75 0 00-1.5 0V4.61l-.312-.311a7 7 0 00-11.712 3.138.75.75 0 00.598 1.438z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <button type="button" @click="rotate(90)" class="fml-editor-btn" title="Rotate Right">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="transform: scaleX(-1)"><path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H4.598a.75.75 0 00-.75.75v3.634a.75.75 0 001.5 0v-2.033l.312.311a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm-11.23-3.15a.75.75 0 00.463-.653l-.001-.004a5.502 5.502 0 019.201-2.466l.312.311H12.623a.75.75 0 000 1.5h3.634a.75.75 0 00.75-.75V2.578a.75.75 0 00-1.5 0V4.61l-.312-.311a7 7 0 00-11.712 3.138.75.75 0 00.598 1.438z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <div class="fml-editor-btn-sep"></div>
                                    <button type="button" @click="flipH()" class="fml-editor-btn" title="Flip Horizontal">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M13.2 2.24a.75.75 0 00.04 1.06l2.1 1.95H6.75a.75.75 0 000 1.5h8.59l-2.1 1.95a.75.75 0 101.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 00-1.06.04zm-6.4 8a.75.75 0 00-1.06-.04l-3.5 3.25a.75.75 0 000 1.1l3.5 3.25a.75.75 0 101.02-1.1l-2.1-1.95h8.59a.75.75 0 000-1.5H4.66l2.1-1.95a.75.75 0 00.04-1.06z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <button type="button" @click="flipV()" class="fml-editor-btn" title="Flip Vertical">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="transform: rotate(90deg)"><path fill-rule="evenodd" d="M13.2 2.24a.75.75 0 00.04 1.06l2.1 1.95H6.75a.75.75 0 000 1.5h8.59l-2.1 1.95a.75.75 0 101.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 00-1.06.04zm-6.4 8a.75.75 0 00-1.06-.04l-3.5 3.25a.75.75 0 000 1.1l3.5 3.25a.75.75 0 101.02-1.1l-2.1-1.95h8.59a.75.75 0 000-1.5H4.66l2.1-1.95a.75.75 0 00.04-1.06z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <div class="fml-editor-btn-sep"></div>
                                    <button type="button" @click="resetCrop()" class="fml-editor-btn" title="Reset">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.793 2.232a.75.75 0 01-.025 1.06L3.622 7.25h10.003a5.375 5.375 0 010 10.75H10.75a.75.75 0 010-1.5h2.875a3.875 3.875 0 000-7.75H3.622l4.146 3.957a.75.75 0 01-1.036 1.085l-5.5-5.25a.75.75 0 010-1.085l5.5-5.25a.75.75 0 011.06.025z" clip-rule="evenodd" /></svg>
                                    </button>
                                </div>

                                <div class="fml-editor-canvas">
                                    <img x-ref="editImg" src="{{ $editing->file_url }}" />
                                </div>

                                <div class="fml-editor-actions">
                                    <x-filament::button @click="cancelEdit()" color="gray">
                                        Cancel
                                    </x-filament::button>
                                    <x-filament::button @click="applyEdit()" color="primary" ::disabled="saving">
                                        <span class="fml-spinner-sm" x-show="saving" x-cloak></span>
                                        <span x-text="saving ? 'Saving...' : 'Apply'"></span>
                                    </x-filament::button>
                                </div>
                            </div>

                            {{-- Static preview (visible when NOT editing) --}}
                            <img x-show="!isEditing" src="{{ $editing->file_url }}" alt="{{ $editing->alt_text ?? '' }}" />

                            {{-- Edit Image overlay button --}}
                            <div x-show="!isEditing" class="fml-edit-image-wrap">
                                <x-filament::button @click="startEdit()" color="gray" icon="heroicon-m-pencil-square" size="sm">
                                    Edit Image
                                </x-filament::button>
                            </div>
                        @elseif($editing->isVideo())
                            <div class="fml-modal-preview-icon">
                                <x-heroicon-o-film />
                                <span>{{ $editing->file_name }}</span>
                            </div>
                        @elseif($editing->isPdf())
                            <div class="fml-modal-preview-icon">
                                <x-heroicon-o-document-text />
                                <span>{{ $editing->file_name }}</span>
                            </div>
                        @else
                            <div class="fml-modal-preview-icon">
                                <x-heroicon-o-document />
                                <span>{{ $editing->file_name }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Sidebar --}}
                    <div class="fml-modal-sidebar">
                        {{-- File info --}}
                        <div class="fml-meta">
                            @if($editing->file_name)
                                <div class="fml-meta-row">
                                    <span class="fml-meta-label">File name</span>
                                    <span>{{ $editing->file_name }}</span>
                                </div>
                            @endif
                            @if($editing->mime_type)
                                <div class="fml-meta-row">
                                    <span class="fml-meta-label">Type</span>
                                    <span>{{ $editing->mime_type }}</span>
                                </div>
                            @endif
                            @if($editing->file_size)
                                <div class="fml-meta-row">
                                    <span class="fml-meta-label">Size</span>
                                    <span>{{ number_format($editing->file_size / 1024, 1) }} KB</span>
                                </div>
                            @endif
                            <div class="fml-meta-row">
                                <span class="fml-meta-label">Uploaded</span>
                                <span>{{ $editing->created_at->format('M j, Y') }}</span>
                            </div>
                        </div>

                        {{-- Editable fields --}}
                        <div class="fml-field">
                            <label class="fml-label" for="fml-edit-title">Title</label>
                            <input
                                type="text"
                                id="fml-edit-title"
                                wire:model="editTitle"
                                class="fml-input"
                            />
                        </div>

                        <div class="fml-field">
                            <label class="fml-label" for="fml-edit-alt">Alt Text</label>
                            <input
                                type="text"
                                id="fml-edit-alt"
                                wire:model="editAltText"
                                class="fml-input"
                                placeholder="Describe this image for accessibility"
                            />
                        </div>

                        <div class="fml-field">
                            <label class="fml-label" for="fml-edit-caption">Caption</label>
                            <textarea
                                id="fml-edit-caption"
                                wire:model="editCaption"
                                class="fml-textarea"
                                rows="2"
                            ></textarea>
                        </div>

                        <div class="fml-field">
                            <label class="fml-label" for="fml-edit-desc">Description</label>
                            <textarea
                                id="fml-edit-desc"
                                wire:model="editDescription"
                                class="fml-textarea"
                                rows="3"
                            ></textarea>
                        </div>

                        @if($editing->file_url)
                            <div class="fml-field">
                                <label class="fml-label">File URL</label>
                                <input
                                    type="text"
                                    value="{{ $editing->file_url }}"
                                    class="fml-input"
                                    readonly
                                    onclick="this.select()"
                                />
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Footer --}}
                <div class="fml-modal-footer">
                    <x-filament::button
                        wire:click="deleteMedia"
                        wire:confirm="Are you sure you want to permanently delete this file?"
                        color="danger"
                        outlined
                        icon="heroicon-m-trash"
                    >
                        Delete Permanently
                    </x-filament::button>

                    <x-filament::button
                        wire:click="saveMediaDetail"
                        color="primary"
                    >
                        Save Changes
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</div>
