<?php

namespace Tests\Feature;

use App\Models\CulturalMedia;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CulturalMediaCrudRemovalTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);
    }

    public function test_media_crud_route_names_are_not_registered(): void
    {
        foreach ([
            'cultural-media.index',
            'cultural-media.create',
            'cultural-media.store',
            'cultural-media.edit',
            'cultural-media.update',
            'cultural-media.destroy',
            'cultural-media.activate',
            'cultural-media.deactivate',
        ] as $name) {
            $this->assertFalse(Route::has($name), $name.' must not be registered');
        }
    }

    public function test_legacy_mediji_urls_are_not_found(): void
    {
        $this->actingAs($this->editor)->get('/kalendar-kulture/mediji')->assertNotFound();
        $this->actingAs($this->editor)->get('/kalendar-kulture/mediji/create')->assertNotFound();
        $this->actingAs($this->editor)->post('/kalendar-kulture/mediji', [])->assertNotFound();
        $this->actingAs($this->editor)->get('/kalendar-kulture/mediji/1/edit')->assertNotFound();
        $this->actingAs($this->editor)->put('/kalendar-kulture/mediji/1', [])->assertNotFound();
        $this->actingAs($this->editor)->delete('/kalendar-kulture/mediji/1')->assertNotFound();
    }

    public function test_editorial_nav_does_not_contain_mediji(): void
    {
        $html = $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('>Mediji<', $html);
        $this->assertStringNotContainsString('/kalendar-kulture/mediji', $html);
        $this->assertStringNotContainsString('Katalog medija', $html);
    }

    public function test_event_and_manifestation_cover_forms_remain(): void
    {
        $this->actingAs($this->editor)
            ->get(route('cultural-event-entries.create'))
            ->assertOk()
            ->assertSee('name="cover_file"', false)
            ->assertDontSee('name="cover_media_id"', false)
            ->assertDontSee('Katalog medija', false);

        $this->actingAs($this->editor)
            ->get(route('cultural-manifestations.create'))
            ->assertOk()
            ->assertSee('name="cover_file"', false)
            ->assertDontSee('name="cover_media_id"', false)
            ->assertDontSee('Katalog medija', false);
    }

    public function test_cultural_media_technical_model_still_persists(): void
    {
        $media = CulturalMedia::create([
            'naziv' => 'Tech',
            'namjena' => CulturalMedia::PURPOSE_EVENT_COVER,
            'status' => CulturalMedia::STATUS_ACTIVE,
            'alt_tekst' => 'Alt',
            'originalni_naziv' => 'a.jpg',
            'interni_naziv' => 'a.jpg',
            'mime' => 'image/jpeg',
            'format' => 'jpeg',
            'velicina' => 10,
            'storage_path' => 'cultural-media/a.jpg',
        ]);

        $this->assertDatabaseHas('cultural_media', ['id' => $media->id]);
        $this->assertStringContainsString('storage/cultural-media/a.jpg', $media->publicUrl());
    }

    public function test_cleanup_command_is_not_scheduled(): void
    {
        $events = collect(app()->make(\Illuminate\Console\Scheduling\Schedule::class)->events());
        $this->assertFalse(
            $events->contains(fn ($event) => str_contains((string) $event->command, 'cultural-media:cleanup'))
        );
    }
}
