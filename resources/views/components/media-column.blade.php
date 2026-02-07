@php
    $url = $getThumbnailUrl();
    $size = $getSize();
    $isCircular = $isCircular();
    $isSquare = $isSquare();
@endphp

<div
    style="width: {{ $size }}px; height: {{ $size }}px;"
    @class([
        'overflow-hidden',
        'rounded-full' => $isCircular,
        'rounded-md' => !$isCircular,
    ])
>
    @if($url)
        <img
            src="{{ $url }}"
            alt=""
            @class([
                'object-cover w-full h-full',
            ])
            loading="lazy"
        />
    @else
        <div class="w-full h-full flex items-center justify-center bg-gray-100 dark:bg-gray-800">
            <x-heroicon-o-photo class="w-4 h-4 text-gray-400" />
        </div>
    @endif
</div>
