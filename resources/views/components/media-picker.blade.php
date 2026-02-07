@php
    $isMultiple = $isMultiple();
    $statePath = $getStatePath();
    $selected = $getSelectedMedia();
    $maxItems = $getMaxItems();
    $acceptedTypes = collect(config('filament-media-library.accepted_file_types', ['image/*', 'video/*', 'application/pdf']))->implode(',');
    $maxFileSize = config('filament-media-library.max_file_size', 10240);
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <style>
        .fml-picker-backdrop {
            position: fixed;
            inset: 0;
            z-index: 50;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .fml-picker-modal {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 25px 50px -12px rgb(0 0 0 / 0.25);
            width: 100%;
            max-width: 72rem;
            max-height: calc(100vh - 2rem);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .fml-picker-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid rgb(229 231 235);
            flex-shrink: 0;
        }
        .fml-picker-header h2 {
            font-size: 1.125rem;
            font-weight: 600;
            color: rgb(17 24 39);
            margin: 0;
        }
        .fml-picker-close {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border: none;
            background: transparent;
            color: rgb(156 163 175);
            cursor: pointer;
            border-radius: 0.375rem;
            transition: color 150ms, background-color 150ms;
        }
        .fml-picker-close:hover { color: rgb(55 65 81); background: rgb(243 244 246); }
        .fml-picker-close svg { width: 1.25rem; height: 1.25rem; }

        .fml-picker-tabs {
            display: flex;
            gap: 0;
            border-bottom: 1px solid rgb(229 231 235);
            padding: 0 1.5rem;
            flex-shrink: 0;
        }
        .fml-picker-tab {
            padding: 0.75rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: rgb(107 114 128);
            border: none;
            background: transparent;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -1px;
            transition: color 150ms, border-color 150ms;
        }
        .fml-picker-tab:hover { color: rgb(55 65 81); }
        .fml-picker-tab.active {
            color: rgb(var(--primary-600));
            border-bottom-color: rgb(var(--primary-600));
        }

        .fml-picker-body {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* Upload tab */
        .fml-picker-upload-zone {
            position: relative;
            margin: 2rem;
            padding: 4rem 2rem;
            border: 2px dashed rgb(209 213 219);
            border-radius: 0.75rem;
            background: rgb(249 250 251);
            text-align: center;
            transition: border-color 200ms, background-color 200ms;
        }
        .fml-picker-upload-zone.fml-drag-over {
            border-color: rgb(var(--primary-500));
            background: rgba(var(--primary-500), 0.05);
        }
        .fml-picker-upload-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 4rem;
            height: 4rem;
            border-radius: 9999px;
            background: rgb(243 244 246);
            margin: 0 auto 1rem;
        }
        .fml-picker-upload-icon svg { width: 2rem; height: 2rem; color: rgb(156 163 175); }
        .fml-picker-upload-zone h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: rgb(17 24 39);
            margin: 0 0 0.375rem;
        }
        .fml-picker-upload-zone p {
            font-size: 0.875rem;
            color: rgb(107 114 128);
            margin: 0 0 1rem;
        }
        .fml-picker-upload-zone .fml-upload-hint {
            font-size: 0.75rem;
            color: rgb(156 163 175);
            margin-top: 1rem;
        }
        .fml-picker-select-btn {
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
        .fml-picker-select-btn:hover { background: rgb(243 244 246); border-color: rgb(156 163 175); }

        /* Upload progress */
        .fml-picker-upload-progress {
            padding: 1rem 1.5rem;
        }
        .fml-picker-upload-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgb(243 244 246);
        }
        .fml-picker-upload-item:last-child { border-bottom: none; }
        .fml-picker-upload-name {
            flex: 1;
            font-size: 0.875rem;
            color: rgb(55 65 81);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .fml-picker-upload-status {
            font-size: 0.75rem;
            font-weight: 500;
        }
        .fml-picker-upload-status.success { color: rgb(22 163 74); }
        .fml-picker-upload-status.error { color: rgb(220 38 38); }
        .fml-picker-upload-status.uploading { color: rgb(var(--primary-500)); }

        /* Library tab layout */
        .fml-picker-library {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        .fml-picker-library-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .fml-picker-library-sidebar {
            width: 20rem;
            flex-shrink: 0;
            border-left: 1px solid rgb(229 231 235);
            overflow-y: auto;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
        }

        .fml-picker-toolbar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            border-bottom: 1px solid rgb(229 231 235);
            flex-shrink: 0;
        }
        .fml-picker-filter {
            font-size: 0.875rem;
            padding: 0.375rem 0.5rem;
            border: 1px solid rgb(209 213 219);
            border-radius: 0.5rem;
            background: white;
            color: rgb(17 24 39);
            outline: none;
        }
        .fml-picker-filter:focus {
            border-color: rgb(var(--primary-500));
            box-shadow: 0 0 0 1px rgb(var(--primary-500));
        }
        .fml-picker-search {
            flex: 1;
            max-width: 16rem;
            font-size: 0.875rem;
            padding: 0.375rem 0.75rem;
            border: 1px solid rgb(209 213 219);
            border-radius: 0.5rem;
            background: white;
            color: rgb(17 24 39);
            outline: none;
        }
        .fml-picker-search:focus {
            border-color: rgb(var(--primary-500));
            box-shadow: 0 0 0 1px rgb(var(--primary-500));
        }

        .fml-picker-grid-wrap {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 1.5rem;
        }
        .fml-picker-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.75rem;
        }
        @media (min-width: 768px)  { .fml-picker-grid { grid-template-columns: repeat(5, 1fr); } }
        @media (min-width: 1024px) { .fml-picker-grid { grid-template-columns: repeat(6, 1fr); } }

        .fml-picker-tile {
            position: relative;
            display: block;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            border-radius: 0.5rem;
            border: 2px solid rgb(229 231 235);
            background: rgb(243 244 246);
            cursor: pointer;
            padding: 0;
            text-align: left;
            width: 100%;
            transition: border-color 150ms, box-shadow 150ms;
        }
        .fml-picker-tile:hover { border-color: rgb(156 163 175); }
        .fml-picker-tile.selected {
            border-color: rgb(var(--primary-500));
            box-shadow: 0 0 0 1px rgb(var(--primary-500));
        }
        .fml-picker-tile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .fml-picker-tile-icon {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
            width: 100%;
            height: 100%;
            color: rgb(156 163 175);
        }
        .fml-picker-tile-icon svg { width: 1.5rem; height: 1.5rem; }
        .fml-picker-tile-icon span {
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .fml-picker-tile-check {
            position: absolute;
            top: 0.375rem;
            right: 0.375rem;
            width: 1.25rem;
            height: 1.25rem;
            background: rgb(var(--primary-500));
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 1px 3px rgb(0 0 0 / 0.2);
        }
        .fml-picker-tile-check svg { width: 0.75rem; height: 0.75rem; color: white; }

        .fml-picker-load-more {
            display: flex;
            justify-content: center;
            padding: 1rem 0 0.5rem;
        }
        .fml-picker-load-more button {
            font-size: 0.875rem;
            font-weight: 500;
            color: rgb(var(--primary-600));
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0.375rem 1rem;
            border-radius: 0.375rem;
            transition: background-color 150ms;
        }
        .fml-picker-load-more button:hover { background: rgba(var(--primary-500), 0.08); }
        .fml-picker-load-more button:disabled { color: rgb(156 163 175); cursor: default; }

        /* Sidebar detail */
        .fml-picker-detail-preview {
            width: 100%;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            border-radius: 0.5rem;
            background: rgb(243 244 246);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .fml-picker-detail-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .fml-picker-detail-preview-icon {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            color: rgb(156 163 175);
        }
        .fml-picker-detail-preview-icon svg { width: 3rem; height: 3rem; }
        .fml-picker-detail-preview-icon span { font-size: 0.875rem; font-weight: 500; }

        .fml-picker-meta {
            font-size: 0.75rem;
            color: rgb(107 114 128);
            margin-bottom: 1rem;
            line-height: 1.6;
        }
        .fml-picker-meta-row {
            display: flex;
            justify-content: space-between;
            padding: 0.25rem 0;
            border-bottom: 1px solid rgb(243 244 246);
        }
        .fml-picker-meta-label { font-weight: 600; color: rgb(55 65 81); }

        .fml-picker-field { margin-bottom: 0.75rem; }
        .fml-picker-field:last-child { margin-bottom: 0; }
        .fml-picker-label {
            display: block;
            font-size: 0.6875rem;
            font-weight: 600;
            color: rgb(55 65 81);
            margin-bottom: 0.25rem;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        .fml-picker-input,
        .fml-picker-textarea {
            width: 100%;
            font-size: 0.8125rem;
            padding: 0.375rem 0.5rem;
            border: 1px solid rgb(209 213 219);
            border-radius: 0.375rem;
            background: white;
            color: rgb(17 24 39);
            outline: none;
            transition: border-color 150ms;
            box-sizing: border-box;
        }
        .fml-picker-input:focus,
        .fml-picker-textarea:focus {
            border-color: rgb(var(--primary-500));
            box-shadow: 0 0 0 1px rgb(var(--primary-500));
        }
        .fml-picker-textarea { resize: vertical; }

        .fml-picker-detail-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.75rem;
        }

        /* Footer */
        .fml-picker-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.5rem;
            border-top: 1px solid rgb(229 231 235);
            flex-shrink: 0;
        }
        .fml-picker-footer-count {
            font-size: 0.875rem;
            color: rgb(107 114 128);
        }
        .fml-picker-footer-actions {
            display: flex;
            gap: 0.5rem;
        }

        /* Spinner */
        .fml-picker-spinner {
            display: inline-block;
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid rgb(229 231 235);
            border-top-color: rgb(var(--primary-500));
            border-radius: 50%;
            animation: fml-picker-spin 0.6s linear infinite;
        }
        @keyframes fml-picker-spin {
            to { transform: rotate(360deg); }
        }
        .fml-picker-spinner-sm {
            width: 0.875rem;
            height: 0.875rem;
            border-width: 2px;
        }
        .fml-picker-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
        }

        /* Empty state */
        .fml-picker-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            text-align: center;
        }
        .fml-picker-empty svg { width: 3rem; height: 3rem; color: rgb(209 213 219); margin-bottom: 0.75rem; }
        .fml-picker-empty p { font-size: 0.875rem; color: rgb(107 114 128); margin: 0; }

        /* Single image preview (WordPress featured image style) */
        .fml-picker-single-preview {
            position: relative;
            max-width: 16rem;
            border-radius: 0.5rem;
            overflow: hidden;
            border: 1px solid rgb(229 231 235);
        }
        .fml-picker-single-preview img {
            display: block;
            width: 100%;
            height: auto;
        }
        .fml-picker-single-preview-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1.5rem 2rem;
            background: rgb(243 244 246);
            font-size: 0.8125rem;
            font-weight: 500;
            color: rgb(107 114 128);
        }
        .fml-picker-single-preview-icon svg { width: 1.5rem; height: 1.5rem; flex-shrink: 0; }
        .fml-picker-remove-link {
            display: inline-block;
            font-size: 0.8125rem;
            color: rgb(220 38 38);
            background: none;
            border: none;
            padding: 0;
            cursor: pointer;
            text-decoration: underline;
            text-underline-offset: 2px;
            margin-top: 0.375rem;
        }
        .fml-picker-remove-link:hover { color: rgb(185 28 28); }

        /* Multi-select thumbnail row */
        .fml-picker-multi-previews {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .fml-picker-multi-item {
            position: relative;
            width: 5rem;
            height: 5rem;
            border-radius: 0.5rem;
            overflow: hidden;
            border: 1px solid rgb(229 231 235);
        }
        .fml-picker-multi-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .fml-picker-multi-item-icon {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgb(243 244 246);
            font-size: 0.625rem;
            font-weight: 600;
            text-transform: uppercase;
            color: rgb(107 114 128);
        }
        .fml-picker-multi-remove {
            position: absolute;
            top: 0;
            right: 0;
            width: 1.25rem;
            height: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgb(220 38 38);
            color: white;
            border: none;
            border-radius: 0 0 0 0.25rem;
            cursor: pointer;
            padding: 0;
            opacity: 0;
            transition: opacity 150ms;
        }
        .fml-picker-multi-item:hover .fml-picker-multi-remove { opacity: 1; }
        .fml-picker-multi-remove svg { width: 0.75rem; height: 0.75rem; }

        .fml-picker-sidebar-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex: 1;
            text-align: center;
            color: rgb(156 163 175);
            padding: 2rem 1rem;
        }
        .fml-picker-sidebar-empty svg { width: 2.5rem; height: 2.5rem; margin-bottom: 0.75rem; }
        .fml-picker-sidebar-empty p { font-size: 0.8125rem; margin: 0; }

        .fml-picker-notification {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 60;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
            transition: opacity 300ms, transform 300ms;
        }
        .fml-picker-notification.success {
            background: rgb(220 252 231);
            color: rgb(22 101 52);
            border: 1px solid rgb(134 239 172);
        }
        .fml-picker-notification.error {
            background: rgb(254 226 226);
            color: rgb(153 27 27);
            border: 1px solid rgb(252 165 165);
        }

        /* Dark mode */
        .dark .fml-picker-modal { background: rgb(17 24 39); }
        .dark .fml-picker-header { border-color: rgb(55 65 81); }
        .dark .fml-picker-header h2 { color: white; }
        .dark .fml-picker-close { color: rgb(107 114 128); }
        .dark .fml-picker-close:hover { color: white; background: rgb(31 41 55); }
        .dark .fml-picker-tabs { border-color: rgb(55 65 81); }
        .dark .fml-picker-tab { color: rgb(156 163 175); }
        .dark .fml-picker-tab:hover { color: rgb(209 213 219); }
        .dark .fml-picker-tab.active { color: rgb(var(--primary-400)); border-bottom-color: rgb(var(--primary-400)); }
        .dark .fml-picker-upload-zone { background: rgb(17 24 39); border-color: rgb(55 65 81); }
        .dark .fml-picker-upload-zone.fml-drag-over { border-color: rgb(var(--primary-500)); background: rgba(var(--primary-500), 0.08); }
        .dark .fml-picker-upload-icon { background: rgb(31 41 55); }
        .dark .fml-picker-upload-icon svg { color: rgb(107 114 128); }
        .dark .fml-picker-upload-zone h3 { color: white; }
        .dark .fml-picker-upload-zone p { color: rgb(156 163 175); }
        .dark .fml-picker-select-btn { background: rgb(31 41 55); border-color: rgb(55 65 81); color: rgb(209 213 219); }
        .dark .fml-picker-select-btn:hover { background: rgb(55 65 81); border-color: rgb(75 85 99); }
        .dark .fml-picker-toolbar { border-color: rgb(55 65 81); }
        .dark .fml-picker-filter,
        .dark .fml-picker-search { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: white; }
        .dark .fml-picker-tile { border-color: rgb(55 65 81); background: rgb(31 41 55); }
        .dark .fml-picker-tile:hover { border-color: rgb(75 85 99); }
        .dark .fml-picker-tile.selected { border-color: rgb(var(--primary-500)); }
        .dark .fml-picker-tile-icon { color: rgb(107 114 128); }
        .dark .fml-picker-library-sidebar { border-color: rgb(55 65 81); }
        .dark .fml-picker-detail-preview { background: rgb(31 41 55); }
        .dark .fml-picker-detail-preview-icon { color: rgb(107 114 128); }
        .dark .fml-picker-meta { color: rgb(156 163 175); }
        .dark .fml-picker-meta-row { border-color: rgb(55 65 81); }
        .dark .fml-picker-meta-label { color: rgb(209 213 219); }
        .dark .fml-picker-label { color: rgb(209 213 219); }
        .dark .fml-picker-input,
        .dark .fml-picker-textarea { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: white; }
        .dark .fml-picker-footer { border-color: rgb(55 65 81); }
        .dark .fml-picker-footer-count { color: rgb(156 163 175); }
        .dark .fml-picker-empty svg { color: rgb(75 85 99); }
        .dark .fml-picker-empty p { color: rgb(156 163 175); }
        .dark .fml-picker-spinner { border-color: rgb(55 65 81); border-top-color: rgb(var(--primary-500)); }
        .dark .fml-picker-single-preview { border-color: rgb(55 65 81); }
        .dark .fml-picker-single-preview-icon { background: rgb(31 41 55); color: rgb(156 163 175); }
        .dark .fml-picker-remove-link { color: rgb(248 113 113); }
        .dark .fml-picker-remove-link:hover { color: rgb(252 165 165); }
        .dark .fml-picker-multi-item { border-color: rgb(55 65 81); }
        .dark .fml-picker-multi-item-icon { background: rgb(31 41 55); color: rgb(156 163 175); }
        .dark .fml-picker-sidebar-empty { color: rgb(107 114 128); }
        .dark .fml-picker-upload-name { color: rgb(209 213 219); }
        .dark .fml-picker-upload-item { border-color: rgb(55 65 81); }
    </style>

    <div
        x-data="{
            state: $wire.$entangle('{{ $statePath }}'),
            showModal: false,
            activeTab: 'library',
            search: '',
            filterType: '',
            mediaItems: [],
            loading: false,
            page: 1,
            hasMore: false,
            total: 0,
            selected: @js(collect($selected)->pluck('id')->toArray()),
            selectedItems: @js($selected),
            isMultiple: @js($isMultiple),
            maxItems: @js($maxItems),
            activeItem: null,
            detailLoading: false,
            editFields: { title: '', alt_text: '', caption: '', description: '' },
            detailSaving: false,
            uploading: false,
            uploadQueue: [],
            notification: null,
            notificationTimeout: null,
            baseUrl: '{{ route('filament-media-library.media-picker') }}',
            uploadUrl: '{{ route('filament-media-library.media-picker.upload') }}',
            csrfToken: '{{ csrf_token() }}',

            async loadMedia(reset = false) {
                if (reset) {
                    this.page = 1;
                    this.mediaItems = [];
                }
                this.loading = true;

                const params = new URLSearchParams({
                    search: this.search,
                    page: this.page,
                    per_page: 24,
                });
                if (this.filterType) {
                    params.set('type', this.filterType);
                }

                try {
                    const response = await fetch(this.baseUrl + '?' + params);
                    const result = await response.json();
                    if (result && result.data) {
                        if (reset) {
                            this.mediaItems = result.data;
                        } else {
                            this.mediaItems = [...this.mediaItems, ...result.data];
                        }
                        this.hasMore = result.has_more;
                        this.total = result.total;
                    }
                } catch (e) {
                    this.showNotification('Failed to load media', 'error');
                }

                this.loading = false;
            },

            openModal() {
                this.showModal = true;
                this.activeTab = 'library';
                this.activeItem = null;
                this.loadMedia(true);
            },

            closeModal() {
                this.showModal = false;
                this.activeItem = null;
            },

            switchTab(tab) {
                this.activeTab = tab;
                if (tab === 'library') {
                    this.loadMedia(true);
                }
            },

            toggleSelect(id) {
                if (this.isMultiple) {
                    const index = this.selected.indexOf(id);
                    if (index > -1) {
                        this.selected.splice(index, 1);
                    } else {
                        if (this.maxItems > 0 && this.selected.length >= this.maxItems) {
                            return;
                        }
                        this.selected.push(id);
                    }
                } else {
                    if (this.selected.includes(id)) {
                        this.selected = [];
                    } else {
                        this.selected = [id];
                    }
                }

                this.loadDetail(id);
            },

            isSelected(id) {
                return this.selected.includes(id);
            },

            async loadDetail(id) {
                this.detailLoading = true;
                try {
                    const response = await fetch(this.baseUrl + '/' + id);
                    const result = await response.json();
                    if (result && result.data) {
                        this.activeItem = result.data;
                        this.editFields = {
                            title: result.data.title || '',
                            alt_text: result.data.alt_text || '',
                            caption: result.data.caption || '',
                            description: result.data.description || '',
                        };
                    }
                } catch (e) {
                    this.showNotification('Failed to load details', 'error');
                }
                this.detailLoading = false;
            },

            async saveDetail() {
                if (!this.activeItem) return;
                this.detailSaving = true;
                try {
                    const response = await fetch(this.baseUrl + '/' + this.activeItem.id, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify(this.editFields),
                    });
                    const result = await response.json();
                    if (result && result.data) {
                        this.activeItem = result.data;
                        const idx = this.mediaItems.findIndex(m => m.id === result.data.id);
                        if (idx > -1) {
                            this.mediaItems[idx] = {
                                ...this.mediaItems[idx],
                                title: result.data.title || result.data.file_name,
                            };
                        }
                        this.showNotification('Details saved', 'success');
                    }
                } catch (e) {
                    this.showNotification('Failed to save details', 'error');
                }
                this.detailSaving = false;
            },

            async deleteItem() {
                if (!this.activeItem) return;
                if (!confirm('Are you sure you want to permanently delete this file?')) return;

                try {
                    await fetch(this.baseUrl + '/' + this.activeItem.id, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                    });

                    const deletedId = this.activeItem.id;
                    this.mediaItems = this.mediaItems.filter(m => m.id !== deletedId);
                    this.selected = this.selected.filter(s => s !== deletedId);
                    this.selectedItems = this.selectedItems.filter(s => s.id !== deletedId);
                    this.activeItem = null;
                    this.total = Math.max(0, this.total - 1);
                    this.showNotification('File deleted', 'success');
                } catch (e) {
                    this.showNotification('Failed to delete file', 'error');
                }
            },

            async uploadFiles(files) {
                if (!files || files.length === 0) return;

                this.uploading = true;
                this.uploadQueue = Array.from(files).map(f => ({
                    name: f.name,
                    status: 'uploading',
                }));

                const formData = new FormData();
                for (let i = 0; i < files.length; i++) {
                    formData.append('files[]', files[i]);
                }

                try {
                    const response = await fetch(this.uploadUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json',
                        },
                        body: formData,
                    });
                    const result = await response.json();

                    if (result.uploaded) {
                        result.uploaded.forEach(item => {
                            const qi = this.uploadQueue.find(q => q.status === 'uploading');
                            if (qi) qi.status = 'success';

                            this.selected.push(item.id);
                        });
                    }
                    if (result.rejected) {
                        result.rejected.forEach(item => {
                            const qi = this.uploadQueue.find(q => q.name === item.name);
                            if (qi) {
                                qi.status = 'error';
                                qi.reason = item.reason;
                            }
                        });
                    }

                    this.uploadQueue.forEach(q => {
                        if (q.status === 'uploading') q.status = 'success';
                    });

                    if (result.uploaded && result.uploaded.length > 0) {
                        this.showNotification(result.uploaded.length + ' file(s) uploaded', 'success');
                        setTimeout(() => {
                            this.activeTab = 'library';
                            this.loadMedia(true);
                        }, 800);
                    }
                } catch (e) {
                    this.uploadQueue.forEach(q => {
                        if (q.status === 'uploading') {
                            q.status = 'error';
                            q.reason = 'Upload failed';
                        }
                    });
                    this.showNotification('Upload failed', 'error');
                }

                this.uploading = false;
            },

            handleDrop(event) {
                event.preventDefault();
                const files = event.dataTransfer.files;
                this.uploadFiles(files);
            },

            confirm() {
                this.selectedItems = this.selected.map(id => {
                    const item = this.mediaItems.find(m => m.id === id);
                    if (item) {
                        return {
                            id: item.id,
                            title: item.title || '',
                            thumbnail_url: item.thumbnail_url || '',
                            file_name: item.file_name || '',
                            mime_type: item.mime_type || '',
                        };
                    }
                    const existing = this.selectedItems.find(s => s.id === id);
                    return existing || { id, title: '', thumbnail_url: '', file_name: '', mime_type: '' };
                });

                if (this.isMultiple) {
                    this.state = [...this.selected];
                } else {
                    this.state = this.selected.length ? this.selected[0] : null;
                }
                this.closeModal();
            },

            removeItem(id) {
                this.selected = this.selected.filter(s => s !== id);
                this.selectedItems = this.selectedItems.filter(s => s.id !== id);
                if (this.isMultiple) {
                    this.state = [...this.selected];
                } else {
                    this.state = null;
                }
            },

            loadMore() {
                this.page++;
                this.loadMedia(false);
            },

            showNotification(message, type) {
                if (this.notificationTimeout) clearTimeout(this.notificationTimeout);
                this.notification = { message, type };
                this.notificationTimeout = setTimeout(() => {
                    this.notification = null;
                }, 3000);
            },

            formatSize(bytes) {
                if (!bytes) return 'Unknown';
                if (bytes < 1024) return bytes + ' B';
                if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
                return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
            },

            formatDate(iso) {
                if (!iso) return 'Unknown';
                const d = new Date(iso);
                return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            },
        }"
        class="space-y-2"
    >
        {{-- Single-select: WordPress featured image style preview (Alpine-reactive) --}}
        <template x-if="!isMultiple && selectedItems.length === 1">
            <div>
                <div class="fml-picker-single-preview">
                    <template x-if="selectedItems[0].thumbnail_url">
                        <img :src="selectedItems[0].thumbnail_url" :alt="selectedItems[0].title || ''">
                    </template>
                    <template x-if="!selectedItems[0].thumbnail_url">
                        <div class="fml-picker-single-preview-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                            <span x-text="selectedItems[0].title || selectedItems[0].file_name || 'File'"></span>
                        </div>
                    </template>
                </div>
                <button type="button" x-on:click="removeItem(selectedItems[0].id)" class="fml-picker-remove-link">
                    Remove
                </button>
            </div>
        </template>

        {{-- Multi-select: thumbnail row with remove buttons (Alpine-reactive) --}}
        <template x-if="isMultiple && selectedItems.length > 0">
            <div class="fml-picker-multi-previews">
                <template x-for="item in selectedItems" :key="item.id">
                    <div class="fml-picker-multi-item">
                        <template x-if="item.thumbnail_url">
                            <img :src="item.thumbnail_url" :alt="item.title || ''">
                        </template>
                        <template x-if="!item.thumbnail_url">
                            <div class="fml-picker-multi-item-icon" x-text="(item.file_name || '').split('.').pop().toUpperCase() || 'FILE'"></div>
                        </template>
                        <button
                            type="button"
                            x-on:click="removeItem(item.id)"
                            class="fml-picker-multi-remove"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                        </button>
                    </div>
                </template>
            </div>
        </template>

        {{-- Open picker button --}}
        <x-filament::button
            x-on:click="openModal()"
            color="gray"
            icon="heroicon-m-photo"
            size="md"
        >
            {{ $isMultiple ? 'Select Media' : 'Choose Media' }}
        </x-filament::button>

        {{-- Modal --}}
        <template x-teleport="body">
            <div
                x-show="showModal"
                x-cloak
                x-transition.opacity
                class="fml-picker-backdrop"
                x-on:keydown.escape.window="closeModal()"
            >
                <div
                    x-on:click.outside="closeModal()"
                    class="fml-picker-modal"
                    @click.stop
                >
                    {{-- Header --}}
                    <div class="fml-picker-header">
                        <h2>Media Library</h2>
                        <button type="button" x-on:click="closeModal()" class="fml-picker-close">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                        </button>
                    </div>

                    {{-- Tabs --}}
                    <div class="fml-picker-tabs">
                        <button
                            type="button"
                            x-on:click="switchTab('upload')"
                            :class="{ 'active': activeTab === 'upload' }"
                            class="fml-picker-tab"
                        >Upload Files</button>
                        <button
                            type="button"
                            x-on:click="switchTab('library')"
                            :class="{ 'active': activeTab === 'library' }"
                            class="fml-picker-tab"
                        >Media Library</button>
                    </div>

                    {{-- Body --}}
                    <div class="fml-picker-body">
                        {{-- Upload Tab --}}
                        <div x-show="activeTab === 'upload'" style="display: flex; flex-direction: column; flex: 1; overflow-y: auto;">
                            <div
                                class="fml-picker-upload-zone"
                                x-data="{ dragging: false }"
                                x-on:dragover.prevent="dragging = true"
                                x-on:dragleave.prevent="dragging = false"
                                x-on:drop.prevent="dragging = false; $dispatch('picker-drop', { files: $event.dataTransfer.files })"
                                x-bind:class="{ 'fml-drag-over': dragging }"
                                x-on:picker-drop.window="uploadFiles($event.detail.files)"
                            >
                                <template x-if="!uploading">
                                    <div>
                                        <div class="fml-picker-upload-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" /></svg>
                                        </div>
                                        <h3>Drop files to upload</h3>
                                        <p>or</p>
                                        <label class="fml-picker-select-btn">
                                            Select Files
                                            <input
                                                type="file"
                                                multiple
                                                accept="{{ $acceptedTypes }}"
                                                x-on:change="uploadFiles($event.target.files); $event.target.value = ''"
                                                style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0);"
                                            />
                                        </label>
                                        <p class="fml-upload-hint">Maximum file size: {{ $maxFileSize / 1024 }} MB</p>
                                    </div>
                                </template>
                                <template x-if="uploading">
                                    <div style="display: flex; flex-direction: column; align-items: center; gap: 0.75rem;">
                                        <div class="fml-picker-spinner"></div>
                                        <p style="font-size: 0.875rem; font-weight: 500; color: rgb(107 114 128); margin: 0;">Uploading files...</p>
                                    </div>
                                </template>
                            </div>

                            {{-- Upload results --}}
                            <template x-if="uploadQueue.length > 0">
                                <div class="fml-picker-upload-progress">
                                    <template x-for="(item, i) in uploadQueue" :key="i">
                                        <div class="fml-picker-upload-item">
                                            <span class="fml-picker-upload-name" x-text="item.name"></span>
                                            <span
                                                class="fml-picker-upload-status"
                                                :class="item.status"
                                                x-text="item.status === 'success' ? 'Uploaded' : item.status === 'error' ? (item.reason || 'Failed') : 'Uploading...'"
                                            ></span>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        {{-- Library Tab --}}
                        <div x-show="activeTab === 'library'" class="fml-picker-library" style="display: none;" x-bind:style="activeTab === 'library' ? 'display: flex;' : 'display: none;'">
                            {{-- Main grid area --}}
                            <div class="fml-picker-library-main">
                                {{-- Toolbar --}}
                                <div class="fml-picker-toolbar">
                                    <select
                                        class="fml-picker-filter"
                                        x-model="filterType"
                                        x-on:change="loadMedia(true)"
                                    >
                                        <option value="">All Types</option>
                                        <option value="image">Images</option>
                                        <option value="video">Videos</option>
                                        <option value="document">Documents</option>
                                    </select>
                                    <input
                                        type="search"
                                        class="fml-picker-search"
                                        placeholder="Search media..."
                                        x-model.debounce.300ms="search"
                                        x-on:input="loadMedia(true)"
                                    />
                                    <span style="font-size: 0.8125rem; color: rgb(107 114 128); white-space: nowrap; margin-left: auto;" x-text="total + ' items'"></span>
                                </div>

                                {{-- Grid --}}
                                <div class="fml-picker-grid-wrap">
                                    <template x-if="loading && mediaItems.length === 0">
                                        <div class="fml-picker-loading">
                                            <div class="fml-picker-spinner"></div>
                                        </div>
                                    </template>

                                    <template x-if="!loading && mediaItems.length === 0">
                                        <div class="fml-picker-empty">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0022.5 18.75V5.25A2.25 2.25 0 0020.25 3H3.75A2.25 2.25 0 001.5 5.25v13.5A2.25 2.25 0 003.75 21z" /></svg>
                                            <p>No media found</p>
                                        </div>
                                    </template>

                                    <div class="fml-picker-grid" x-show="mediaItems.length > 0">
                                        <template x-for="item in mediaItems" :key="item.id">
                                            <button
                                                type="button"
                                                x-on:click="toggleSelect(item.id)"
                                                :class="{ 'selected': isSelected(item.id) }"
                                                class="fml-picker-tile"
                                            >
                                                <template x-if="item.thumbnail_url">
                                                    <img :src="item.thumbnail_url" :alt="item.title || ''" loading="lazy">
                                                </template>
                                                <template x-if="!item.thumbnail_url">
                                                    <div class="fml-picker-tile-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                                        <span x-text="(item.file_name || '').split('.').pop().toUpperCase()"></span>
                                                    </div>
                                                </template>
                                                <template x-if="isSelected(item.id)">
                                                    <div class="fml-picker-tile-check">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                                    </div>
                                                </template>
                                            </button>
                                        </template>
                                    </div>

                                    <div x-show="hasMore" class="fml-picker-load-more">
                                        <button type="button" x-on:click="loadMore()" :disabled="loading">
                                            <span x-show="!loading">Load more</span>
                                            <span x-show="loading">Loading...</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- Detail Sidebar --}}
                            <div class="fml-picker-library-sidebar">
                                <template x-if="!activeItem && !detailLoading">
                                    <div class="fml-picker-sidebar-empty">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                                        <p>Select an item to view details</p>
                                    </div>
                                </template>

                                <template x-if="detailLoading">
                                    <div class="fml-picker-loading">
                                        <div class="fml-picker-spinner"></div>
                                    </div>
                                </template>

                                <template x-if="activeItem && !detailLoading">
                                    <div>
                                        {{-- Preview --}}
                                        <div class="fml-picker-detail-preview">
                                            <template x-if="activeItem.is_image && activeItem.file_url">
                                                <img :src="activeItem.file_url" :alt="activeItem.alt_text || ''">
                                            </template>
                                            <template x-if="activeItem.is_video">
                                                <div class="fml-picker-detail-preview-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h1.5C5.496 19.5 6 18.996 6 18.375m-3.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-1.5A1.875 1.875 0 0118 18.375M20.625 4.5H3.375m17.25 0c.621 0 1.125.504 1.125 1.125M20.625 4.5h-1.5C18.504 4.5 18 5.004 18 5.625m3.75 0v1.5c0 .621-.504 1.125-1.125 1.125M3.375 4.5c-.621 0-1.125.504-1.125 1.125M3.375 4.5h1.5C5.496 4.5 6 5.004 6 5.625m-3.75 0v1.5c0 .621.504 1.125 1.125 1.125m0 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m1.5-3.75C5.496 8.25 6 7.746 6 7.125v-1.5M4.875 8.25C5.496 8.25 6 8.754 6 9.375v1.5m0-5.25v5.25m0-5.25C6 5.004 6.504 4.5 7.125 4.5h9.75c.621 0 1.125.504 1.125 1.125m1.125 2.625h1.5m-1.5 0A1.125 1.125 0 0118 7.125v-1.5m1.125 2.625c-.621 0-1.125.504-1.125 1.125v1.5m2.625-2.625c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125M18 5.625v5.25M7.125 12h9.75m-9.75 0A1.125 1.125 0 016 10.875M7.125 12C6.504 12 6 12.504 6 13.125m0-2.25C6 11.496 5.496 12 4.875 12M18 10.875c0 .621-.504 1.125-1.125 1.125M18 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125m-12 5.25v-5.25m0 5.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125m-12 0v-1.5c0-.621-.504-1.125-1.125-1.125M18 18.375v-5.25m0 5.25v-1.5c0-.621.504-1.125 1.125-1.125M18 13.125v1.5c0 .621.504 1.125 1.125 1.125M18 13.125c0-.621.504-1.125 1.125-1.125M6 13.125v1.5c0 .621-.504 1.125-1.125 1.125M6 13.125C6 12.504 5.496 12 4.875 12m-1.5 0h1.5m-1.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M19.125 12h1.5m0 0c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h1.5m14.25 0h1.5" /></svg>
                                                    <span x-text="activeItem.file_name"></span>
                                                </div>
                                            </template>
                                            <template x-if="activeItem.is_pdf">
                                                <div class="fml-picker-detail-preview-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                                    <span x-text="activeItem.file_name"></span>
                                                </div>
                                            </template>
                                            <template x-if="!activeItem.is_image && !activeItem.is_video && !activeItem.is_pdf">
                                                <div class="fml-picker-detail-preview-icon">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                                    <span x-text="activeItem.file_name"></span>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- File info --}}
                                        <div class="fml-picker-meta">
                                            <div class="fml-picker-meta-row" x-show="activeItem.file_name">
                                                <span class="fml-picker-meta-label">File</span>
                                                <span x-text="activeItem.file_name"></span>
                                            </div>
                                            <div class="fml-picker-meta-row" x-show="activeItem.mime_type">
                                                <span class="fml-picker-meta-label">Type</span>
                                                <span x-text="activeItem.mime_type"></span>
                                            </div>
                                            <div class="fml-picker-meta-row" x-show="activeItem.file_size">
                                                <span class="fml-picker-meta-label">Size</span>
                                                <span x-text="formatSize(activeItem.file_size)"></span>
                                            </div>
                                            <div class="fml-picker-meta-row" x-show="activeItem.created_at">
                                                <span class="fml-picker-meta-label">Date</span>
                                                <span x-text="formatDate(activeItem.created_at)"></span>
                                            </div>
                                        </div>

                                        {{-- Editable fields --}}
                                        <div class="fml-picker-field">
                                            <label class="fml-picker-label">Title</label>
                                            <input type="text" class="fml-picker-input" x-model="editFields.title" />
                                        </div>
                                        <div class="fml-picker-field">
                                            <label class="fml-picker-label">Alt Text</label>
                                            <input type="text" class="fml-picker-input" x-model="editFields.alt_text" placeholder="Describe this image for accessibility" />
                                        </div>
                                        <div class="fml-picker-field">
                                            <label class="fml-picker-label">Caption</label>
                                            <textarea class="fml-picker-textarea" rows="2" x-model="editFields.caption"></textarea>
                                        </div>
                                        <div class="fml-picker-field">
                                            <label class="fml-picker-label">Description</label>
                                            <textarea class="fml-picker-textarea" rows="2" x-model="editFields.description"></textarea>
                                        </div>

                                        {{-- Actions --}}
                                        <div class="fml-picker-detail-actions">
                                            <x-filament::button x-on:click="saveDetail()" color="primary" size="sm" ::disabled="detailSaving">
                                                <span x-show="!detailSaving">Save</span>
                                                <span x-show="detailSaving">Saving...</span>
                                            </x-filament::button>
                                            <x-filament::button x-on:click="deleteItem()" color="danger" size="sm" outlined>
                                                Delete
                                            </x-filament::button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="fml-picker-footer">
                        <span class="fml-picker-footer-count" x-text="selected.length + ' selected'"></span>
                        <div class="fml-picker-footer-actions">
                            <x-filament::button x-on:click="closeModal()" color="gray">
                                Cancel
                            </x-filament::button>
                            <x-filament::button x-on:click="confirm()" color="primary">
                                Confirm Selection
                            </x-filament::button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        {{-- Notification --}}
        <template x-teleport="body">
            <div
                x-show="notification"
                x-cloak
                x-transition.opacity
                class="fml-picker-notification"
                :class="notification ? notification.type : ''"
                x-text="notification ? notification.message : ''"
            ></div>
        </template>
    </div>
</x-dynamic-component>
