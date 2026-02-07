<?php

declare(strict_types=1);

namespace Crumbls\FilamentMediaLibrary\Database\Factories;

use Crumbls\FilamentMediaLibrary\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'alt_text' => fake()->sentence(),
            'caption' => fake()->optional()->sentence(),
            'description' => fake()->optional()->paragraph(),
            'disk' => 'public',
            'uploaded_by' => null,
        ];
    }

    public function uploadedBy(int $userId): static
    {
        return $this->state(['uploaded_by' => $userId]);
    }
}
