@php
    $media = $this->getGridMedia();
    $editing = $this->getEditingMedia();
    $acceptedTypes = collect(config('filament-media-library.accepted_file_types', ['image/*', 'video/*', 'application/pdf']))->implode(',');
@endphp

<div>
    {{-- Toolbar --}}
    <div class="fml-toolbar">
        <div class="fml-toolbar-left">
            <select wire:model.live="filterType" class="fml-select">
                <option value="">{{ __('filament-media-library::media-library.filters.all_types') }}</option>
                <option value="image">{{ __('filament-media-library::media-library.filters.images') }}</option>
                <option value="video">{{ __('filament-media-library::media-library.filters.videos') }}</option>
                <option value="document">{{ __('filament-media-library::media-library.filters.documents') }}</option>
            </select>

            <span class="fml-count">
                {{ $media->total() }} {{ str('item')->plural($media->total()) }}
            </span>
        </div>

        <div class="fml-toolbar-right">
            <input
                wire:model.live.debounce.300ms="gridSearch"
                type="search"
                placeholder="{{ __('filament-media-library::media-library.placeholders.search_media') }}"
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
                <h3>{{ __('filament-media-library::media-library.messages.drop_files') }}</h3>
                <p>{{ __('filament-media-library::media-library.messages.or') }}</p>
                <label class="fml-btn-select-files">
                    {{ __('filament-media-library::media-library.actions.select_files') }}
                    <input
                        type="file"
                        wire:model="uploads"
                        multiple
                        accept="{{ $acceptedTypes }}"
                        style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0);"
                    />
                </label>
                <p class="fml-upload-hint">
                    {{ __('filament-media-library::media-library.messages.max_file_size', ['size' => config('filament-media-library.max_file_size', 10240) / 1024]) }}
                </p>
            </div>

            <div wire:loading wire:target="uploads" class="fml-upload-progress">
                <div class="fml-spinner"></div>
                <p>{{ __('filament-media-library::media-library.messages.uploading_files') }}</p>
            </div>
        </div>
    @endif

    {{-- Tile Grid --}}
    @if($media->isEmpty())
        <div class="fml-empty">
            <div class="fml-empty-icon">
                <x-heroicon-o-photo />
            </div>
            <h3>{{ __('filament-media-library::media-library.headings.no_media_found') }}</h3>
            <p>
                @if($this->gridSearch || $this->filterType)
                    {{ __('filament-media-library::media-library.messages.empty_search') }}
                @else
                    {{ __('filament-media-library::media-library.messages.empty_upload') }}
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
                            <span>{{ __('filament-media-library::media-library.media_types.video') }}</span>
                        </div>
                    @elseif($item->isPdf())
                        <div class="fml-tile-icon">
                            <x-heroicon-o-document-text />
                            <span>{{ __('filament-media-library::media-library.media_types.pdf') }}</span>
                        </div>
                    @else
                        <div class="fml-tile-icon">
                            <x-heroicon-o-document />
                            <span>{{ strtoupper(pathinfo($item->file_name ?? '', PATHINFO_EXTENSION)) ?: __('filament-media-library::media-library.media_types.file') }}</span>
                        </div>
                    @endif

                    <div class="fml-tile-overlay">
                        <p>{{ $item->title ?? $item->file_name ?? __('filament-media-library::media-library.media_types.untitled') }}</p>
                    </div>
                </button>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($media->hasPages())
            <div class="fml-pagination">
                <span class="fml-pagination-info">
                    {!! __('filament-media-library::media-library.messages.showing_range', ['first' => $media->firstItem(), 'last' => $media->lastItem(), 'total' => $media->total()]) !!}
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
                    <h2>{{ __('filament-media-library::media-library.headings.attachment_details') }}</h2>
                    <x-filament::icon-button
                        icon="heroicon-m-x-mark"
                        wire:click="closeMediaDetail"
                        :label="__('filament-media-library::media-library.actions.close')"
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
                            trans: {
                                saving: @js(__('filament-media-library::media-library.messages.saving')),
                                apply: @js(__('filament-media-library::media-library.actions.apply')),
                            },
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
                                    <button type="button" @click="rotate(-90)" class="fml-editor-btn" title="{{ __('filament-media-library::media-library.editor.rotate_left') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H4.598a.75.75 0 00-.75.75v3.634a.75.75 0 001.5 0v-2.033l.312.311a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm-11.23-3.15a.75.75 0 00.463-.653l-.001-.004a5.502 5.502 0 019.201-2.466l.312.311H12.623a.75.75 0 000 1.5h3.634a.75.75 0 00.75-.75V2.578a.75.75 0 00-1.5 0V4.61l-.312-.311a7 7 0 00-11.712 3.138.75.75 0 00.598 1.438z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <button type="button" @click="rotate(90)" class="fml-editor-btn" title="{{ __('filament-media-library::media-library.editor.rotate_right') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="transform: scaleX(-1)"><path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H4.598a.75.75 0 00-.75.75v3.634a.75.75 0 001.5 0v-2.033l.312.311a7 7 0 0011.712-3.138.75.75 0 00-1.449-.39zm-11.23-3.15a.75.75 0 00.463-.653l-.001-.004a5.502 5.502 0 019.201-2.466l.312.311H12.623a.75.75 0 000 1.5h3.634a.75.75 0 00.75-.75V2.578a.75.75 0 00-1.5 0V4.61l-.312-.311a7 7 0 00-11.712 3.138.75.75 0 00.598 1.438z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <div class="fml-editor-btn-sep"></div>
                                    <button type="button" @click="flipH()" class="fml-editor-btn" title="{{ __('filament-media-library::media-library.editor.flip_horizontal') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M13.2 2.24a.75.75 0 00.04 1.06l2.1 1.95H6.75a.75.75 0 000 1.5h8.59l-2.1 1.95a.75.75 0 101.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 00-1.06.04zm-6.4 8a.75.75 0 00-1.06-.04l-3.5 3.25a.75.75 0 000 1.1l3.5 3.25a.75.75 0 101.02-1.1l-2.1-1.95h8.59a.75.75 0 000-1.5H4.66l2.1-1.95a.75.75 0 00.04-1.06z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <button type="button" @click="flipV()" class="fml-editor-btn" title="{{ __('filament-media-library::media-library.editor.flip_vertical') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="transform: rotate(90deg)"><path fill-rule="evenodd" d="M13.2 2.24a.75.75 0 00.04 1.06l2.1 1.95H6.75a.75.75 0 000 1.5h8.59l-2.1 1.95a.75.75 0 101.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 00-1.06.04zm-6.4 8a.75.75 0 00-1.06-.04l-3.5 3.25a.75.75 0 000 1.1l3.5 3.25a.75.75 0 101.02-1.1l-2.1-1.95h8.59a.75.75 0 000-1.5H4.66l2.1-1.95a.75.75 0 00.04-1.06z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <div class="fml-editor-btn-sep"></div>
                                    <button type="button" @click="resetCrop()" class="fml-editor-btn" title="{{ __('filament-media-library::media-library.editor.reset') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.793 2.232a.75.75 0 01-.025 1.06L3.622 7.25h10.003a5.375 5.375 0 010 10.75H10.75a.75.75 0 010-1.5h2.875a3.875 3.875 0 000-7.75H3.622l4.146 3.957a.75.75 0 01-1.036 1.085l-5.5-5.25a.75.75 0 010-1.085l5.5-5.25a.75.75 0 011.06.025z" clip-rule="evenodd" /></svg>
                                    </button>
                                </div>

                                <div class="fml-editor-canvas">
                                    <img x-ref="editImg" src="{{ $editing->file_url }}" />
                                </div>

                                <div class="fml-editor-actions">
                                    <x-filament::button @click="cancelEdit()" color="gray">
                                        {{ __('filament-media-library::media-library.actions.cancel') }}
                                    </x-filament::button>
                                    <x-filament::button @click="applyEdit()" color="primary" ::disabled="saving">
                                        <span class="fml-spinner-sm" x-show="saving" x-cloak></span>
                                        <span x-text="saving ? trans.saving : trans.apply"></span>
                                    </x-filament::button>
                                </div>
                            </div>

                            {{-- Static preview (visible when NOT editing) --}}
                            <img x-show="!isEditing" src="{{ $editing->file_url }}" alt="{{ $editing->alt_text ?? '' }}" />

                            {{-- Edit Image overlay button --}}
                            <div x-show="!isEditing" class="fml-edit-image-wrap">
                                <x-filament::button @click="startEdit()" color="gray" icon="heroicon-m-pencil-square" size="sm">
                                    {{ __('filament-media-library::media-library.actions.edit_image') }}
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
                                    <span class="fml-meta-label">{{ __('filament-media-library::media-library.labels.file_name') }}</span>
                                    <span>{{ $editing->file_name }}</span>
                                </div>
                            @endif
                            @if($editing->mime_type)
                                <div class="fml-meta-row">
                                    <span class="fml-meta-label">{{ __('filament-media-library::media-library.labels.type') }}</span>
                                    <span>{{ $editing->mime_type }}</span>
                                </div>
                            @endif
                            @if($editing->file_size)
                                <div class="fml-meta-row">
                                    <span class="fml-meta-label">{{ __('filament-media-library::media-library.labels.size') }}</span>
                                    <span>{{ number_format($editing->file_size / 1024, 1) }} KB</span>
                                </div>
                            @endif
                            <div class="fml-meta-row">
                                <span class="fml-meta-label">{{ __('filament-media-library::media-library.labels.uploaded') }}</span>
                                <span>{{ $editing->created_at->format('M j, Y') }}</span>
                            </div>
                        </div>

                        {{-- Editable fields --}}
                        <div class="fml-field">
                            <label class="fml-label" for="fml-edit-title">{{ __('filament-media-library::media-library.labels.title') }}</label>
                            <input
                                type="text"
                                id="fml-edit-title"
                                wire:model="editTitle"
                                class="fml-input"
                            />
                        </div>

                        <div class="fml-field">
                            <label class="fml-label" for="fml-edit-alt">{{ __('filament-media-library::media-library.labels.alt_text') }}</label>
                            <input
                                type="text"
                                id="fml-edit-alt"
                                wire:model="editAltText"
                                class="fml-input"
                                placeholder="{{ __('filament-media-library::media-library.placeholders.alt_text_hint') }}"
                            />
                        </div>

                        <div class="fml-field">
                            <label class="fml-label" for="fml-edit-caption">{{ __('filament-media-library::media-library.labels.caption') }}</label>
                            <textarea
                                id="fml-edit-caption"
                                wire:model="editCaption"
                                class="fml-textarea"
                                rows="2"
                            ></textarea>
                        </div>

                        <div class="fml-field">
                            <label class="fml-label" for="fml-edit-desc">{{ __('filament-media-library::media-library.labels.description') }}</label>
                            <textarea
                                id="fml-edit-desc"
                                wire:model="editDescription"
                                class="fml-textarea"
                                rows="3"
                            ></textarea>
                        </div>

                        @if($editing->file_url)
                            <div class="fml-field">
                                <label class="fml-label">{{ __('filament-media-library::media-library.labels.file_url') }}</label>
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
                        wire:confirm="{{ __('filament-media-library::media-library.messages.confirm_delete') }}"
                        color="danger"
                        outlined
                        icon="heroicon-m-trash"
                    >
                        {{ __('filament-media-library::media-library.actions.delete_permanently') }}
                    </x-filament::button>

                    <x-filament::button
                        wire:click="saveMediaDetail"
                        color="primary"
                    >
                        {{ __('filament-media-library::media-library.actions.save_changes') }}
                    </x-filament::button>
                </div>
            </div>
        </div>
    @endif
</div>
