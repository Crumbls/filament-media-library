<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary\Events;

use Crumbls\FilamentMediaLibrary\Models\Media;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MediaDeleted
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Media $media,
    ) {}
}
