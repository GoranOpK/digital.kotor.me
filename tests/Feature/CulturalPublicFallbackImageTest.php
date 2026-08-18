<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalManifestation;
use App\Models\CulturalMedia;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use App\Support\CulturalCalendarDefaultImages;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CulturalPublicFallbackImageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        $this->user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
        ]);
        $this->creator = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-16 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_event_with_cover_media_uses_uploaded_public_url(): void
    {
        $category = $this->category('Koncerti');
        $cover = $this->makeMedia(CulturalMedia::PURPOSE_EVENT_COVER, 'cultural-media/event-cover.jpg');
        $entry = $this->makePublishedEntry('EVENT_WITH_COVER', $category, [
            'cover_media_id' => $cover->id,
        ]);

        $this->assertSame($cover->publicUrl(), $entry->fresh()->load('coverMedia')->imageUrl());
        $this->assertStringContainsString('storage/cultural-media/event-cover.jpg', $entry->imageUrl());
        $this->assertStringNotContainsString('kalendar-kulture/categories/', $entry->imageUrl());
        $this->assertNotSame(CulturalCalendarDefaultImages::fallbackUrl(), $entry->imageUrl());
    }

    public function test_event_without_cover_and_ready_category_uses_category_asset(): void
    {
        $category = $this->category('Koncerti');
        $entry = $this->makePublishedEntry('EVENT_READY_FALLBACK', $category);

        $expected = CulturalCalendarDefaultImages::urlForCategory('Koncerti');
        $this->assertSame($expected, $entry->fresh()->load('category')->imageUrl());
        $this->assertStringContainsString('img/kalendar-kulture/categories/koncerti.jpg', $entry->imageUrl());
    }

    public function test_event_without_cover_and_missing_category_uses_global_event_fallback(): void
    {
        $category = $this->category('Sajmovi');
        $entry = $this->makePublishedEntry('EVENT_MISSING_FALLBACK', $category);

        $this->assertSame(
            CulturalCalendarDefaultImages::fallbackUrl(),
            $entry->fresh()->load('category')->imageUrl()
        );
        $this->assertStringContainsString('kalendar-kulture-default-event.png', $entry->imageUrl());
        $this->assertStringNotContainsString('/categories/', $entry->imageUrl());
    }

    public function test_event_without_cover_and_literary_category_uses_assigned_asset(): void
    {
        $category = $this->category('Književni programi');
        $entry = $this->makePublishedEntry('EVENT_LITERARY_FALLBACK', $category);

        $expected = CulturalCalendarDefaultImages::urlForCategory('Književni programi');
        $this->assertSame($expected, $entry->fresh()->load('category')->imageUrl());
        $this->assertStringContainsString('knjizevne-veceri.jpg', $entry->imageUrl());
    }

    public function test_event_without_cover_and_without_category_uses_global_event_fallback(): void
    {
        $entry = $this->makePublishedEntry('EVENT_NO_CATEGORY', null);

        $this->assertNull($entry->category_id);
        $this->assertSame(
            CulturalCalendarDefaultImages::fallbackUrl(),
            $entry->fresh()->load('category')->imageUrl()
        );
    }

    public function test_event_image_url_does_not_use_category_default_media(): void
    {
        $category = $this->category('Koncerti');
        $this->makeMedia(CulturalMedia::PURPOSE_CATEGORY_DEFAULT, 'cultural-media/category-default.jpg');
        $entry = $this->makePublishedEntry('EVENT_NOT_CATEGORY_DEFAULT', $category);

        $url = $entry->fresh()->load(['category', 'coverMedia'])->imageUrl();
        $this->assertStringNotContainsString('category-default.jpg', $url);
        $this->assertSame(CulturalCalendarDefaultImages::urlForCategory('Koncerti'), $url);
    }

    public function test_manifestation_with_cover_media_uses_uploaded_public_url(): void
    {
        $cover = $this->makeMedia(CulturalMedia::PURPOSE_MANIFESTATION_COVER, 'cultural-media/mf-cover.jpg');
        $mf = $this->makePublishedManifestation('MF_WITH_COVER', [
            'cover_media_id' => $cover->id,
        ]);

        $this->assertSame($cover->publicUrl(), $mf->fresh()->load('coverMedia')->imageUrl());
        $this->assertStringContainsString('storage/cultural-media/mf-cover.jpg', $mf->imageUrl());
    }

    public function test_manifestation_without_cover_uses_mf_fallback_resolver_not_category_asset(): void
    {
        $this->category('Koncerti');
        $mf = $this->makePublishedManifestation('MF_NO_COVER');

        $url = $mf->fresh()->load('coverMedia')->imageUrl();
        $this->assertSame(CulturalCalendarDefaultImages::manifestationFallbackUrl(), $url);
        $this->assertNotSame(CulturalCalendarDefaultImages::urlForCategory('Koncerti'), $url);
        $this->assertStringNotContainsString('/categories/', $url);
        $this->assertStringContainsString(
            'kalendar-kulture-default-event.png',
            $url,
            'MED-I4A temporary compatibility: MF fallback still points at Event global PNG until MED-I4B.'
        );
    }

    public function test_public_event_card_and_detail_keep_object_fit_cover(): void
    {
        $category = $this->category('Koncerti');
        $entry = $this->makePublishedEntry('OBJECT_FIT_EVENT', $category);
        $this->makeOccurrence($entry, ['datum' => '2026-08-20']);

        $list = $this->actingAs($this->user)->get(route('cultural-calendar.events'));
        $list->assertOk();
        $list->assertSee(asset('img/kalendar-kulture/categories/koncerti.jpg'), false);
        $list->assertSee('object-cover', false);

        $detail = $this->actingAs($this->user)->get(route('cultural-calendar.show', $entry));
        $detail->assertOk();
        $detail->assertSee(asset('img/kalendar-kulture/categories/koncerti.jpg'), false);
        $detail->assertSee('object-fit: cover', false);
    }

    public function test_public_manifestation_card_and_detail_keep_object_fit_cover(): void
    {
        $mf = $this->makePublishedManifestation('OBJECT_FIT_MF');
        $category = $this->category('Koncerti');
        $entry = $this->makePublishedEntry('OBJECT_FIT_MF_EVENT', $category, [
            'manifestation_id' => $mf->id,
        ]);
        $this->makeOccurrence($entry, ['datum' => '2026-08-20']);

        $list = $this->actingAs($this->user)->get(route('cultural-calendar.manifestations'));
        $list->assertOk();
        $list->assertSee(CulturalCalendarDefaultImages::manifestationFallbackUrl(), false);
        $list->assertSee('object-cover', false);

        $detail = $this->actingAs($this->user)->get(route('cultural-calendar.manifestation', $mf));
        $detail->assertOk();
        $detail->assertSee(CulturalCalendarDefaultImages::manifestationFallbackUrl(), false);
        $detail->assertSee('object-fit: cover', false);
    }

    private function category(string $naziv): CulturalCategory
    {
        return CulturalCategory::query()->firstOrCreate(
            ['naziv' => $naziv],
            ['status' => CulturalCategory::STATUS_ACTIVE]
        );
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makePublishedEntry(
        string $naslov,
        ?CulturalCategory $category,
        array $extra = []
    ): CulturalEventEntry {
        return CulturalEventEntry::create(array_merge([
            'naslov' => $naslov,
            'status' => CulturalEventEntry::STATUS_PUBLISHED,
            'category_id' => $category?->id,
            'created_by' => $this->creator->id,
            'featured' => false,
        ], $extra));
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makePublishedManifestation(string $naziv, array $extra = []): CulturalManifestation
    {
        return CulturalManifestation::create(array_merge([
            'naziv' => $naziv,
            'opis' => null,
            'status' => CulturalManifestation::STATUS_PUBLISHED,
            'created_by' => $this->creator->id,
            'published_at' => now(),
        ], $extra));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeOccurrence(CulturalEventEntry $entry, array $attributes): CulturalOccurrence
    {
        return CulturalOccurrence::create(array_merge([
            'event_entry_id' => $entry->id,
            'cjelodnevno' => true,
            'status' => CulturalOccurrence::STATUS_PLANNED,
        ], $attributes));
    }

    private function makeMedia(string $namjena, string $storagePath): CulturalMedia
    {
        return CulturalMedia::create([
            'naziv' => 'Test cover',
            'namjena' => $namjena,
            'status' => CulturalMedia::STATUS_ACTIVE,
            'alt_tekst' => 'Alt',
            'originalni_naziv' => basename($storagePath),
            'interni_naziv' => basename($storagePath),
            'mime' => 'image/jpeg',
            'format' => 'jpeg',
            'velicina' => 100,
            'storage_path' => $storagePath,
            'creator_id' => $this->creator->id,
        ]);
    }
}
