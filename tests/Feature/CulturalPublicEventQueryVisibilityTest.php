<?php

namespace Tests\Feature;

use App\Models\CulturalEventEntry;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalCalendar\CulturalPublicEventQuery;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 6A-02 / TM-JP-01…04 (statusna vidljivost) — CulturalPublicEventQuery + publiclyVisible.
 * Bez vremenske / OCC / filter / controller logike.
 */
class CulturalPublicEventQueryVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $creator;

    private CulturalPublicEventQuery $publicQuery;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->creator = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
        ]);
        $this->publicQuery = app(CulturalPublicEventQuery::class);
    }

    public function test_published_entry_is_included_in_public_query(): void
    {
        $published = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Objavljen A');

        $ids = $this->publicQuery->base()->pluck('id')->all();

        $this->assertContains($published->id, $ids);
        $this->assertTrue($published->isPubliclyVisible());
    }

    public function test_cancelled_entry_is_included_in_public_query(): void
    {
        $cancelled = $this->makeEntry(CulturalEventEntry::STATUS_CANCELLED, 'Otkazan A');

        $ids = $this->publicQuery->base()->pluck('id')->all();

        $this->assertContains($cancelled->id, $ids);
        $this->assertTrue($cancelled->isPubliclyVisible());
    }

    public function test_draft_is_excluded_from_public_query(): void
    {
        $draft = $this->makeEntry(CulturalEventEntry::STATUS_DRAFT, 'Nacrt A');

        $ids = $this->publicQuery->base()->pluck('id')->all();

        $this->assertNotContains($draft->id, $ids);
        $this->assertFalse($draft->isPubliclyVisible());
    }

    public function test_pending_approval_is_excluded_from_public_query(): void
    {
        $pending = $this->makeEntry(CulturalEventEntry::STATUS_PENDING_APPROVAL, 'Pending A');

        $ids = $this->publicQuery->base()->pluck('id')->all();

        $this->assertNotContains($pending->id, $ids);
        $this->assertFalse($pending->isPubliclyVisible());
    }

    public function test_archived_is_excluded_from_public_query(): void
    {
        $archived = $this->makeEntry(CulturalEventEntry::STATUS_ARCHIVED, 'Arhiviran A');

        $ids = $this->publicQuery->base()->pluck('id')->all();

        $this->assertNotContains($archived->id, $ids);
        $this->assertFalse($archived->isPubliclyVisible());
    }

    public function test_mixed_set_returns_only_published_and_cancelled(): void
    {
        $published = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Pub');
        $cancelled = $this->makeEntry(CulturalEventEntry::STATUS_CANCELLED, 'Can');
        $draft = $this->makeEntry(CulturalEventEntry::STATUS_DRAFT, 'Dr');
        $pending = $this->makeEntry(CulturalEventEntry::STATUS_PENDING_APPROVAL, 'Pe');
        $archived = $this->makeEntry(CulturalEventEntry::STATUS_ARCHIVED, 'Ar');

        $ids = $this->publicQuery->entries()->orderBy('id')->pluck('id')->all();

        $this->assertSame([$published->id, $cancelled->id], $ids);
        $this->assertNotContains($draft->id, $ids);
        $this->assertNotContains($pending->id, $ids);
        $this->assertNotContains($archived->id, $ids);
    }

    public function test_public_query_always_applies_visibility_scope(): void
    {
        $this->makeEntry(CulturalEventEntry::STATUS_DRAFT, 'Hidden');
        $visible = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Visible');

        $viaBase = $this->publicQuery->base()->get();
        $viaEntries = $this->publicQuery->entries()->get();
        $viaModelScope = CulturalEventEntry::query()->publiclyVisible()->get();

        $this->assertCount(1, $viaBase);
        $this->assertTrue($viaBase->first()->is($visible));
        $this->assertEqualsCanonicalizing(
            $viaBase->pluck('id')->all(),
            $viaEntries->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            $viaBase->pluck('id')->all(),
            $viaModelScope->pluck('id')->all()
        );
    }

    public function test_public_query_does_not_mutate_entry_data(): void
    {
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Stable Title');
        $entry->forceFill([
            'opis' => 'Stable opis',
            'featured' => true,
            'cancellation_reason' => 'interno',
        ])->save();

        $before = $entry->fresh()->only([
            'naslov',
            'opis',
            'status',
            'featured',
            'cancellation_reason',
        ]);

        $this->publicQuery->base()->get();
        $found = $this->publicQuery->base()->whereKey($entry->id)->first();

        $this->assertNotNull($found);
        $this->assertSame($before, $found->only([
            'naslov',
            'opis',
            'status',
            'featured',
            'cancellation_reason',
        ]));
        $this->assertSame('interno', $found->cancellation_reason);
    }

    public function test_publicly_visible_statuses_ssot_matches_model_constants(): void
    {
        $this->assertSame(
            [
                CulturalEventEntry::STATUS_PUBLISHED,
                CulturalEventEntry::STATUS_CANCELLED,
            ],
            CulturalEventEntry::PUBLICLY_VISIBLE_STATUSES
        );
        $this->assertSame('published', CulturalEventEntry::STATUS_PUBLISHED);
        $this->assertSame('cancelled', CulturalEventEntry::STATUS_CANCELLED);
        $this->assertSame('draft', CulturalEventEntry::STATUS_DRAFT);
        $this->assertSame('pending_approval', CulturalEventEntry::STATUS_PENDING_APPROVAL);
        $this->assertSame('archived', CulturalEventEntry::STATUS_ARCHIVED);
    }

    private function makeEntry(string $status, string $naslov): CulturalEventEntry
    {
        return CulturalEventEntry::create([
            'naslov' => $naslov,
            'status' => $status,
            'created_by' => $this->creator->id,
        ]);
    }
}
