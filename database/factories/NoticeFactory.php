<?php

namespace Database\Factories;

use App\Models\Notice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notice>
 */
class NoticeFactory extends Factory
{
    protected $model = Notice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'short_description' => fake()->optional()->sentence(8),
            'visible_in_active_panel' => true,
            'publicly_available' => true,
            'source_type' => 'competition_decision',
            'source_id' => fake()->numberBetween(1, 1000),
            'source_object_id' => null,
            'content_delivery' => 'competition_decision_html',
            'published_at' => now(),
        ];
    }

    public function hiddenFromPanel(): static
    {
        return $this->state(fn (array $attributes) => [
            'visible_in_active_panel' => false,
        ]);
    }

    public function withoutDescription(): static
    {
        return $this->state(fn (array $attributes) => [
            'short_description' => null,
        ]);
    }
}
