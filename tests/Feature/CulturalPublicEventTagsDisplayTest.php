<?php

namespace Tests\Feature;

use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\CulturalTag;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalCalendar\CulturalPublicEventQuery;
use App\Support\CulturalPublicReadSource;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * 6A-CLOSE-03 — Oznake na canonical javnom detalju Događaja.
 */
class CulturalPublicEventTagsDisplayTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seed(RoleSeeder::class);

        config(['cultural_calendar.public_read_source' => CulturalPublicReadSource::CANONICAL]);

        $this->user = User::factory()->create([
            'role_id' => Role::where('name', 'korisnik')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_published_detail_shows_single_tag(): void
    {
        $entry = $this->makePublished('Tagged Single');
        $tag = $this->makeTag('Barok');
        $entry->tags()->attach($tag->id);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertOk()
            ->assertSee('Oznake:', false)
            ->assertSee('class="kk-show-tag"', false)
            ->assertSee('Barok', false);
    }

    public function test_published_detail_shows_multiple_tags(): void
    {
        $entry = $this->makePublished('Tagged Multi');
        $a = $this->makeTag('Barok');
        $b = $this->makeTag('Djeca');
        $c = $this->makeTag('Besplatno');
        $entry->tags()->attach([$c->id, $a->id, $b->id]);

        $html = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertOk()
            ->assertSee('Oznake:', false)
            ->assertSee('Barok', false)
            ->assertSee('Besplatno', false)
            ->assertSee('Djeca', false)
            ->getContent();

        $this->assertSame(3, substr_count($html, 'class="kk-show-tag"'));
        $this->assertStringNotContainsString('Barok, Besplatno', $html);
        $this->assertStringNotContainsString('Besplatno, Djeca', $html);

        $posBarok = strpos($html, '><span class="kk-show-tag">Barok</span>');
        if ($posBarok === false) {
            $posBarok = strpos($html, '<span class="kk-show-tag">Barok</span>');
        }
        $posBesplatno = strpos($html, '<span class="kk-show-tag">Besplatno</span>');
        $posDjeca = strpos($html, '<span class="kk-show-tag">Djeca</span>');

        $this->assertNotFalse($posBarok);
        $this->assertNotFalse($posBesplatno);
        $this->assertNotFalse($posDjeca);
        $this->assertTrue($posBarok < $posBesplatno && $posBesplatno < $posDjeca);
    }

    public function test_published_detail_without_tags_hides_section(): void
    {
        $entry = $this->makePublished('No Tags');

        $html = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertOk()
            ->assertSee('No Tags', false)
            ->getContent();

        $this->assertStringNotContainsString('Oznake:', $html);
        $this->assertStringNotContainsString('<span class="kk-show-tag">', $html);
        $this->assertStringNotContainsString('class="kk-show-tags"', $html);
    }

    public function test_tag_name_is_escaped_on_detail(): void
    {
        $entry = $this->makePublished('Escape Tags');
        $tag = $this->makeTag('<script>alert(1)</script>');
        $entry->tags()->attach($tag->id);

        $html = $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertOk()
            ->assertSee('Oznake:', false)
            ->assertSee('class="kk-show-tag"', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->getContent();

        $this->assertStringContainsString(
            '<span class="kk-show-tag">&lt;script&gt;alert(1)&lt;/script&gt;</span>',
            $html
        );
    }

    public function test_draft_and_pending_remain_404(): void
    {
        $draft = $this->makeEntry('Draft Tags', CulturalEventEntry::STATUS_DRAFT);
        $pending = $this->makeEntry('Pending Tags', CulturalEventEntry::STATUS_PENDING_APPROVAL);
        $tag = $this->makeTag('Hidden');
        $draft->tags()->attach($tag->id);
        $pending->tags()->attach($tag->id);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $draft->id))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $pending->id))
            ->assertNotFound();
    }

    public function test_cancelled_public_detail_shows_tags(): void
    {
        $entry = $this->makeEntry('Cancelled Tags', CulturalEventEntry::STATUS_CANCELLED);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-20',
            'status' => CulturalOccurrence::STATUS_CANCELLED,
        ]);
        $tag = $this->makeTag('Festival');
        $entry->tags()->attach($tag->id);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertOk()
            ->assertSee('Oznake:', false)
            ->assertSee('Festival', false);
    }

    public function test_archived_public_detail_shows_tags(): void
    {
        $entry = $this->makeEntry('Archived Tags', CulturalEventEntry::STATUS_ARCHIVED, [
            'archived_from_status' => CulturalEventEntry::STATUS_PUBLISHED,
        ]);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-01',
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);
        $tag = $this->makeTag('Istorija');
        $entry->tags()->attach($tag->id);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertOk()
            ->assertSee('Oznake:', false)
            ->assertSee('Istorija', false);
    }

    public function test_public_show_eager_loads_tags(): void
    {
        $entry = $this->makePublished('Eager Tags');
        $tag = $this->makeTag('Eager');
        $entry->tags()->attach($tag->id);

        $loaded = app(CulturalPublicEventQuery::class)->findPublicEntryForShow($entry->id);

        $this->assertTrue($loaded->relationLoaded('tags'));
        $this->assertSame(['Eager'], $loaded->tags->pluck('naziv')->all());
    }

    public function test_homepage_card_does_not_show_oznake_label(): void
    {
        $entry = $this->makePublished('Home Tags');
        $tag = $this->makeTag('HomeOnlyTagXYZ');
        $entry->tags()->attach($tag->id);

        $html = $this->actingAs($this->user)
            ->get(route('cultural-calendar.index'))
            ->assertOk()
            ->assertSee('Home Tags', false)
            ->getContent();

        $this->assertStringNotContainsString('Oznake:', $html);
        // Tag name may appear only if duplicated in title; label section must not.
        $this->assertStringNotContainsString('<strong>Oznake:</strong>', $html);
        $this->assertStringNotContainsString('<span class="kk-show-tag">', $html);
    }

    public function test_search_card_does_not_show_oznake_label(): void
    {
        $entry = $this->makePublished('Search Tags');
        $tag = $this->makeTag('SearchOnlyTagXYZ');
        $entry->tags()->attach($tag->id);

        $html = $this->actingAs($this->user)
            ->get(route('cultural-calendar.events'))
            ->assertOk()
            ->assertSee('Search Tags', false)
            ->getContent();

        $this->assertStringNotContainsString('<strong>Oznake:</strong>', $html);
        $this->assertStringNotContainsString('<span class="kk-show-tag">', $html);
    }

    public function test_inactive_linked_tag_still_shown_on_detail(): void
    {
        $entry = $this->makePublished('Inactive Linked');
        $tag = $this->makeTag('StaraOznaka', CulturalTag::STATUS_INACTIVE);
        $entry->tags()->attach($tag->id);

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertOk()
            ->assertSee('Oznake:', false)
            ->assertSee('StaraOznaka', false);
    }

    public function test_show_detail_does_not_n_plus_one_tags(): void
    {
        $entry = $this->makePublished('NPlusOne Tags');
        $tags = [
            $this->makeTag('Alpha'),
            $this->makeTag('Beta'),
            $this->makeTag('Gamma'),
        ];
        $entry->tags()->attach(collect($tags)->pluck('id')->all());

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->user)
            ->get(route('cultural-calendar.show', $entry->id))
            ->assertOk()
            ->assertSee('Alpha', false)
            ->assertSee('Beta', false)
            ->assertSee('Gamma', false);

        $tagQueries = collect(DB::getQueryLog())->filter(function (array $query): bool {
            $sql = strtolower($query['query']);

            return str_contains($sql, 'cultural_tags')
                || str_contains($sql, 'cultural_event_entry_tag');
        });

        // Eager load: typically 1 pivot/tags query, not one per tag in Blade.
        $this->assertLessThanOrEqual(3, $tagQueries->count());
    }

    private function makePublished(string $naslov): CulturalEventEntry
    {
        $entry = $this->makeEntry($naslov, CulturalEventEntry::STATUS_PUBLISHED);
        $this->makeOccurrence($entry, ['datum' => '2026-08-20']);

        return $entry;
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makeEntry(string $naslov, string $status, array $extra = []): CulturalEventEntry
    {
        return CulturalEventEntry::create(array_merge([
            'naslov' => $naslov,
            'opis' => 'Opis',
            'status' => $status,
            'created_by' => $this->user->id,
            'featured' => false,
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

    private function makeTag(string $naziv, string $status = CulturalTag::STATUS_ACTIVE): CulturalTag
    {
        return CulturalTag::create([
            'naziv' => $naziv,
            'status' => $status,
        ]);
    }
}
