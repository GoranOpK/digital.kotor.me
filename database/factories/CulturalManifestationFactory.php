<?php

namespace Database\Factories;

use App\Models\CulturalManifestation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CulturalManifestation>
 */
class CulturalManifestationFactory extends Factory
{
    protected $model = CulturalManifestation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'naziv' => fake()->unique()->sentence(3),
            'opis' => fake()->optional()->paragraph(),
            'status' => CulturalManifestation::STATUS_DRAFT,
            'organizer_id' => null,
            'cover_media_id' => null,
            'web_stranica' => null,
            'created_by' => User::factory(),
            'last_modified_by' => null,
            'first_submitted_at' => null,
            'published_at' => null,
            'cancelled_at' => null,
            'archived_at' => null,
        ];
    }
}

