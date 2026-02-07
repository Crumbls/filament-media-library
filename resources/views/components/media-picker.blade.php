@php
    $isMultiple = $getIsMultiple();
    $statePath = $getStatePath();
    $selected = $getSelectedMedia();
    $maxItems = $getMaxItems();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{
            state: $wire.$entangle('{{ $statePath }}'),
            showModal: false,
            search: '',
            mediaItems: [],
            loading: false,
            page: 1,
            hasMore: false,
            selected: @js(collect($selected)->pluck('id')->toArray()),
            isMultiple: @js($isMultiple),
            maxItems: @js($maxItems),

            async loadMedia(reset = false) {
                if (reset) {
                    this.page = 1;
                    this.mediaItems = [];
                }
                this.loading = true;

                const result = await $wire.call('getMediaPickerItems', this.search, this.page, 24);

                if (result && result.data) {
                    if (reset) {
                        this.mediaItems = result.data;
                    } else {
                        this.mediaItems = [...this.mediaItems, ...result.data];
                    }
                    this.hasMore = result.has_more;
                }

                this.loading = false;
            },

            openModal() {
                this.showModal = true;
                this.loadMedia(true);
            },

            closeModal() {
                this.showModal = false;
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
                    this.selected = [id];
                }
            },

            isSelected(id) {
                return this.selected.includes(id);
            },

            confirm() {
                if (this.isMultiple) {
                    this.state = [...this.selected];
                } else {
                    this.state = this.selected.length ? this.selected[0] : null;
                }
                this.closeModal();
            },

            removeItem(id) {
                this.selected = this.selected.filter(s => s !== id);
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
        }"
        class="space-y-2"
    >
        {{-- Selected media preview --}}
        <div class="flex flex-wrap gap-2">
            @foreach($selected as $item)
                <div class="relative group border rounded-lg overflow-hidden" style="width: 80px; height: 80px;">
                    @if($item['thumbnail_url'])
                        <img src="{{ $item['thumbnail_url'] }}" alt="{{ $item['title'] ?? '' }}" class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full flex items-center justify-center bg-gray-100 dark:bg-gray-800 text-xs text-gray-500">
                            {{ strtoupper(pathinfo($item['file_name'] ?? '', PATHINFO_EXTENSION)) }}
                        </div>
                    @endif
                    <button
                        type="button"
                        x-on:click="removeItem({{ $item['id'] }})"
                        class="absolute top-0 right-0 bg-danger-500 text-white rounded-bl p-0.5 opacity-0 group-hover:opacity-100 transition"
                    >
                        <x-heroicon-m-x-mark class="w-3 h-3" />
                    </button>
                </div>
            @endforeach
        </div>

        {{-- Open picker button --}}
        <button
            type="button"
            x-on:click="openModal()"
            class="fi-btn fi-btn-size-md relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-color-gray gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20"
        >
            <x-heroicon-m-photo class="w-5 h-5" />
            <span>{{ $isMultiple ? 'Select Media' : 'Choose Media' }}</span>
        </button>

        {{-- Modal --}}
        <div
            x-show="showModal"
            x-cloak
            x-transition
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
            x-on:keydown.escape.window="closeModal()"
        >
            <div
                x-on:click.outside="closeModal()"
                class="bg-white dark:bg-gray-900 rounded-xl shadow-xl max-w-4xl w-full max-h-[80vh] flex flex-col"
            >
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Media Library</h2>
                    <button type="button" x-on:click="closeModal()" class="text-gray-400 hover:text-gray-500">
                        <x-heroicon-m-x-mark class="w-5 h-5" />
                    </button>
                </div>

                {{-- Search --}}
                <div class="px-6 py-3 border-b dark:border-gray-700">
                    <input
                        type="search"
                        x-model.debounce.300ms="search"
                        x-on:input="loadMedia(true)"
                        placeholder="Search media..."
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white text-sm focus:ring-primary-500 focus:border-primary-500"
                    />
                </div>

                {{-- Grid --}}
                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <div x-show="loading && mediaItems.length === 0" class="flex justify-center py-8">
                        <x-filament::loading-indicator class="w-8 h-8" />
                    </div>

                    <div class="grid grid-cols-4 sm:grid-cols-6 gap-3">
                        <template x-for="item in mediaItems" :key="item.id">
                            <button
                                type="button"
                                x-on:click="toggleSelect(item.id)"
                                :class="{
                                    'ring-2 ring-primary-500 ring-offset-2 dark:ring-offset-gray-900': isSelected(item.id),
                                    'hover:ring-2 hover:ring-gray-300 dark:hover:ring-gray-600': !isSelected(item.id),
                                }"
                                class="relative aspect-square rounded-lg overflow-hidden border dark:border-gray-700 transition focus:outline-none focus:ring-2 focus:ring-primary-500"
                            >
                                <template x-if="item.thumbnail_url">
                                    <img :src="item.thumbnail_url" :alt="item.title || ''" class="w-full h-full object-cover">
                                </template>
                                <template x-if="!item.thumbnail_url">
                                    <div class="w-full h-full flex items-center justify-center bg-gray-100 dark:bg-gray-800 text-xs text-gray-500">
                                        <span x-text="(item.file_name || '').split('.').pop().toUpperCase()"></span>
                                    </div>
                                </template>
                                <div
                                    x-show="isSelected(item.id)"
                                    class="absolute top-1 right-1 bg-primary-500 rounded-full p-0.5"
                                >
                                    <x-heroicon-m-check class="w-3 h-3 text-white" />
                                </div>
                            </button>
                        </template>
                    </div>

                    <div x-show="hasMore" class="flex justify-center py-4">
                        <button
                            type="button"
                            x-on:click="loadMore()"
                            class="text-sm text-primary-600 hover:text-primary-500 font-medium"
                            :disabled="loading"
                        >
                            <span x-show="!loading">Load more</span>
                            <span x-show="loading">Loading...</span>
                        </button>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between px-6 py-4 border-t dark:border-gray-700">
                    <span class="text-sm text-gray-500" x-text="selected.length + ' selected'"></span>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            x-on:click="closeModal()"
                            class="fi-btn fi-btn-size-md fi-btn-color-gray rounded-lg px-3 py-2 text-sm font-semibold shadow-sm bg-white text-gray-950 hover:bg-gray-50 dark:bg-white/5 dark:text-white dark:hover:bg-white/10 ring-1 ring-gray-950/10 dark:ring-white/20"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            x-on:click="confirm()"
                            class="fi-btn fi-btn-size-md fi-btn-color-primary rounded-lg px-3 py-2 text-sm font-semibold shadow-sm bg-primary-600 text-white hover:bg-primary-500"
                        >
                            Confirm Selection
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
