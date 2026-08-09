<?php

namespace Tests\Feature;

use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalLocation;
use App\Models\CulturalOccurrence;
use App\Models\CulturalOrganizer;
use App\Models\CulturalTag;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalCalendar\CulturalPublicEventQuery;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 6A-05 — kanonski q / date / week / month filteri (TS-009 §3.2–3.3).
 */
class CulturalPublicSearchFiltersTest extends TestCase
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

        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_q_matches_naslov(): void
    {
        $match = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Ljetnji koncert');
        $other = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Izložba');

        $ids = $this->publicQuery->filterByQ('koncert')->pluck('id')->all();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_q_matches_opis(): void
    {
        $match = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Događaj A', [
            'opis' => 'Večer poezije u starom gradu',
        ]);
        $other = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Događaj B', [
            'opis' => 'Filmska projekcija',
        ]);

        $ids = $this->publicQuery->filterByQ('poezije')->pluck('id')->all();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_q_matches_catalog_location(): void
    {
        $location = $this->makeLocation('Crkva Sv. Luke');
        $match = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'A');
        $other = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'B');
        $this->makeOccurrence($match, [
            'datum' => '2026-08-20',
            'location_id' => $location->id,
        ]);
        $this->makeOccurrence($other, [
            'datum' => '2026-08-20',
            'location_manual_name' => 'Drugo mjesto',
        ]);

        $ids = $this->publicQuery->filterByQ('Luke')->pluck('id')->all();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_q_matches_manual_location(): void
    {
        $match = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'A');
        $other = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'B');
        $this->makeOccurrence($match, [
            'datum' => '2026-08-20',
            'location_manual_name' => 'Trg od Oružja',
        ]);
        $this->makeOccurrence($other, [
            'datum' => '2026-08-20',
            'location_manual_name' => 'Bastion',
        ]);

        $ids = $this->publicQuery->filterByQ('Oružja')->pluck('id')->all();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_q_matches_manual_location_with_padding_via_trim(): void
    {
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Padded');
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-20',
            'location_manual_name' => '  Kulturni centar  ',
        ]);

        $ids = $this->publicQuery->filterByQ('Kulturni')->pluck('id')->all();

        $this->assertContains($entry->id, $ids);
    }

    public function test_q_no_match_returns_empty(): void
    {
        $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Koncert');

        $this->assertSame([], $this->publicQuery->filterByQ('xyz-nepostojece')->pluck('id')->all());
    }

    public function test_blank_q_is_ignored(): void
    {
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Vidljiv');

        $this->assertEqualsCanonicalizing(
            $this->publicQuery->base()->pluck('id')->all(),
            $this->publicQuery->filterByQ('   ')->pluck('id')->all()
        );
        $this->assertContains($entry->id, $this->publicQuery->filterByQ(null)->pluck('id')->all());
    }

    public function test_q_cannot_bypass_public_visibility(): void
    {
        $draft = $this->makeEntry(CulturalEventEntry::STATUS_DRAFT, 'Tajni koncert');
        $published = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Javni koncert');

        $ids = $this->publicQuery->filterByQ('koncert')->pluck('id')->all();

        $this->assertContains($published->id, $ids);
        $this->assertNotContains($draft->id, $ids);
    }

    public function test_q_does_not_search_cancellation_reason(): void
    {
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_CANCELLED, 'Otkazan događaj', [
            'cancellation_reason' => 'UNIQUE_CANCEL_TOKEN_XYZ',
        ]);

        $ids = $this->publicQuery->filterByQ('UNIQUE_CANCEL_TOKEN_XYZ')->pluck('id')->all();

        $this->assertNotContains($entry->id, $ids);
    }

    public function test_q_does_not_search_organizer_or_tag(): void
    {
        $creationRequest = \App\Models\CulturalOrganizerCreationRequest::create([
            'submitter_user_id' => $this->creator->id,
            'proposed_moderator_user_id' => $this->creator->id,
            'proposed_moderator_is_submitter' => true,
            'proposed_naziv' => 'UNIQUE_ORG_TOKEN_ABC',
            'status' => \App\Models\CulturalOrganizerCreationRequest::STATUS_APPROVED,
            'decision_user_id' => $this->creator->id,
            'decision_at' => now(),
        ]);
        $organizer = CulturalOrganizer::create([
            'naziv' => 'UNIQUE_ORG_TOKEN_ABC',
            'status' => CulturalOrganizer::STATUS_ACTIVE,
            'approved_creation_request_id' => $creationRequest->id,
        ]);
        $tag = CulturalTag::create([
            'naziv' => 'UNIQUE_TAG_TOKEN_DEF',
            'status' => CulturalTag::STATUS_ACTIVE,
        ]);

        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Običan naslov', [
            'organizer_id' => $organizer->id,
        ]);
        $entry->tags()->attach($tag->id);

        $this->assertNotContains(
            $entry->id,
            $this->publicQuery->filterByQ('UNIQUE_ORG_TOKEN_ABC')->pluck('id')->all()
        );
        $this->assertNotContains(
            $entry->id,
            $this->publicQuery->filterByQ('UNIQUE_TAG_TOKEN_DEF')->pluck('id')->all()
        );
    }

    public function test_date_matches_occurrence_on_requested_day(): void
    {
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Dan');
        $this->makeOccurrence($entry, ['datum' => '2026-08-15']);

        $ids = $this->publicQuery->filterByDate('2026-08-15')->pluck('id')->all();

        $this->assertContains($entry->id, $ids);
    }

    public function test_date_excludes_day_before_and_after(): void
    {
        $before = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Prije');
        $after = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Poslije');
        $this->makeOccurrence($before, ['datum' => '2026-08-14']);
        $this->makeOccurrence($after, ['datum' => '2026-08-16']);

        $ids = $this->publicQuery->filterByDate('2026-08-15')->pluck('id')->all();

        $this->assertNotContains($before->id, $ids);
        $this->assertNotContains($after->id, $ids);
    }

    public function test_date_returns_entry_once_for_multiple_matching_occurrences(): void
    {
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Više');
        $this->makeOccurrence($entry, ['datum' => '2026-08-15']);
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-15',
            'vrijeme_od' => '20:00',
            'cjelodnevno' => false,
        ]);

        $ids = $this->publicQuery->filterByDate('2026-08-15')->pluck('id')->all();

        $this->assertSame([$entry->id], $ids);
    }

    public function test_date_filter_cannot_bypass_public_visibility(): void
    {
        $draft = $this->makeEntry(CulturalEventEntry::STATUS_DRAFT, 'DraftDay');
        $published = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'PubDay');
        $this->makeOccurrence($draft, ['datum' => '2026-08-15']);
        $this->makeOccurrence($published, ['datum' => '2026-08-15']);

        $ids = $this->publicQuery->filterByDate('2026-08-15')->pluck('id')->all();

        $this->assertContains($published->id, $ids);
        $this->assertNotContains($draft->id, $ids);
    }

    public function test_date_includes_non_planned_occurrence_statuses(): void
    {
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'FinishedDay');
        $this->makeOccurrence($entry, [
            'datum' => '2026-08-15',
            'status' => CulturalOccurrence::STATUS_FINISHED,
        ]);

        $this->assertContains(
            $entry->id,
            $this->publicQuery->filterByDate('2026-08-15')->pluck('id')->all()
        );
    }

    public function test_invalid_date_is_ignored(): void
    {
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Any');

        $this->assertEqualsCanonicalizing(
            $this->publicQuery->base()->pluck('id')->all(),
            $this->publicQuery->filterByDate('not-a-date')->pluck('id')->all()
        );
        $this->assertContains($entry->id, $this->publicQuery->filterByDate('2026-13-40')->pluck('id')->all());
    }

    public function test_week_includes_start_middle_and_end(): void
    {
        $start = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Start');
        $mid = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Mid');
        $end = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'End');
        $this->makeOccurrence($start, ['datum' => '2026-08-10']);
        $this->makeOccurrence($mid, ['datum' => '2026-08-13']);
        $this->makeOccurrence($end, ['datum' => '2026-08-16']);

        $ids = $this->publicQuery
            ->filterByWeek('2026-08-10', '2026-08-16')
            ->pluck('id')
            ->all();

        $this->assertContains($start->id, $ids);
        $this->assertContains($mid->id, $ids);
        $this->assertContains($end->id, $ids);
    }

    public function test_week_excludes_day_before_and_after(): void
    {
        $before = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Before');
        $after = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'After');
        $this->makeOccurrence($before, ['datum' => '2026-08-09']);
        $this->makeOccurrence($after, ['datum' => '2026-08-17']);

        $ids = $this->publicQuery
            ->filterByWeek('2026-08-10', '2026-08-16')
            ->pluck('id')
            ->all();

        $this->assertNotContains($before->id, $ids);
        $this->assertNotContains($after->id, $ids);
    }

    public function test_week_does_not_duplicate_entry(): void
    {
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'WeekDup');
        $this->makeOccurrence($entry, ['datum' => '2026-08-11']);
        $this->makeOccurrence($entry, ['datum' => '2026-08-14']);

        $ids = $this->publicQuery
            ->filterByWeek('2026-08-10', '2026-08-16')
            ->pluck('id')
            ->all();

        $this->assertSame([$entry->id], $ids);
    }

    public function test_invalid_week_is_ignored(): void
    {
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'WeekAny');

        $this->assertEqualsCanonicalizing(
            $this->publicQuery->base()->pluck('id')->all(),
            $this->publicQuery->filterByWeek('bad', '2026-08-16')->pluck('id')->all()
        );
        $this->assertContains(
            $entry->id,
            $this->publicQuery->filterByWeek('2026-08-10', null)->pluck('id')->all()
        );
    }

    public function test_month_includes_first_middle_and_last_day(): void
    {
        $first = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'First');
        $mid = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'MidM');
        $last = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Last');
        $this->makeOccurrence($first, ['datum' => '2026-08-01']);
        $this->makeOccurrence($mid, ['datum' => '2026-08-15']);
        $this->makeOccurrence($last, ['datum' => '2026-08-31']);

        $ids = $this->publicQuery->filterByMonth('2026-08')->pluck('id')->all();

        $this->assertContains($first->id, $ids);
        $this->assertContains($mid->id, $ids);
        $this->assertContains($last->id, $ids);
    }

    public function test_month_excludes_adjacent_months(): void
    {
        $prev = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Prev');
        $next = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Next');
        $this->makeOccurrence($prev, ['datum' => '2026-07-31']);
        $this->makeOccurrence($next, ['datum' => '2026-09-01']);

        $ids = $this->publicQuery->filterByMonth('2026-08')->pluck('id')->all();

        $this->assertNotContains($prev->id, $ids);
        $this->assertNotContains($next->id, $ids);
    }

    public function test_month_does_not_duplicate_entry(): void
    {
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'MonthDup');
        $this->makeOccurrence($entry, ['datum' => '2026-08-02']);
        $this->makeOccurrence($entry, ['datum' => '2026-08-28']);

        $ids = $this->publicQuery->filterByMonth('2026-08')->pluck('id')->all();

        $this->assertSame([$entry->id], $ids);
    }

    public function test_invalid_month_is_ignored(): void
    {
        $entry = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'MonthAny');

        $this->assertEqualsCanonicalizing(
            $this->publicQuery->base()->pluck('id')->all(),
            $this->publicQuery->filterByMonth('2026-13')->pluck('id')->all()
        );
        $this->assertContains($entry->id, $this->publicQuery->filterByMonth('august')->pluck('id')->all());
    }

    public function test_chain_q_and_category(): void
    {
        $catA = $this->makeCategory('Koncert');
        $catB = $this->makeCategory('Izložba');
        $match = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Ljetnji koncert', [
            'category_id' => $catA->id,
        ]);
        $wrongCat = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Ljetnji koncert 2', [
            'category_id' => $catB->id,
        ]);

        $query = $this->publicQuery->filterByQ('Ljetnji');
        $ids = $this->publicQuery->filterByCategoryName('Koncert', $query)->pluck('id')->all();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($wrongCat->id, $ids);
    }

    public function test_chain_category_and_location(): void
    {
        $cat = $this->makeCategory('Muzika');
        $match = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Match', [
            'category_id' => $cat->id,
        ]);
        $other = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Other', [
            'category_id' => $cat->id,
        ]);
        $this->makeOccurrence($match, [
            'datum' => '2026-08-20',
            'location_manual_name' => 'Palata',
        ]);
        $this->makeOccurrence($other, [
            'datum' => '2026-08-20',
            'location_manual_name' => 'Trg',
        ]);

        $query = $this->publicQuery->filterByCategoryName('Muzika');
        $ids = $this->publicQuery->filterByLocationDisplayName('Palata', $query)->pluck('id')->all();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_chain_q_and_date(): void
    {
        $match = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Festival A');
        $wrongDay = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Festival B');
        $this->makeOccurrence($match, ['datum' => '2026-08-15']);
        $this->makeOccurrence($wrongDay, ['datum' => '2026-08-16']);

        $query = $this->publicQuery->filterByQ('Festival');
        $ids = $this->publicQuery->filterByDate('2026-08-15', $query)->pluck('id')->all();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($wrongDay->id, $ids);
    }

    public function test_chain_month_and_category(): void
    {
        $cat = $this->makeCategory('Teatar');
        $match = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Aug', [
            'category_id' => $cat->id,
        ]);
        $wrongMonth = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Sep', [
            'category_id' => $cat->id,
        ]);
        $this->makeOccurrence($match, ['datum' => '2026-08-05']);
        $this->makeOccurrence($wrongMonth, ['datum' => '2026-09-05']);

        $query = $this->publicQuery->filterByMonth('2026-08');
        $ids = $this->publicQuery->filterByCategoryName('Teatar', $query)->pluck('id')->all();

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($wrongMonth->id, $ids);
    }

    public function test_chain_all_filters_with_ordering(): void
    {
        $cat = $this->makeCategory('Program');
        $earlier = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Alpha night', [
            'category_id' => $cat->id,
            'opis' => 'Specijalni program',
        ]);
        $later = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Alpha day', [
            'category_id' => $cat->id,
            'opis' => 'Specijalni program',
        ]);
        $excluded = $this->makeEntry(CulturalEventEntry::STATUS_PUBLISHED, 'Alpha other', [
            'category_id' => $cat->id,
            'opis' => 'Specijalni program',
        ]);

        $this->makeOccurrence($earlier, [
            'datum' => '2026-08-12',
            'location_manual_name' => 'Forum',
        ]);
        $this->makeOccurrence($later, [
            'datum' => '2026-08-20',
            'location_manual_name' => 'Forum',
        ]);
        $this->makeOccurrence($excluded, [
            'datum' => '2026-08-15',
            'location_manual_name' => 'Drugo',
        ]);

        $query = $this->publicQuery->filterByQ('Specijalni');
        $query = $this->publicQuery->filterByCategoryName('Program', $query);
        $query = $this->publicQuery->filterByLocationDisplayName('Forum', $query);
        $query = $this->publicQuery->filterByMonth('2026-08', $query);
        $ids = $this->publicQuery
            ->orderedByNextRelevantOccurrence(null, $query)
            ->pluck('id')
            ->all();

        $this->assertSame([$earlier->id, $later->id], $ids);
        $this->assertNotContains($excluded->id, $ids);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function makeEntry(string $status, string $naslov, array $extra = []): CulturalEventEntry
    {
        return CulturalEventEntry::create(array_merge([
            'naslov' => $naslov,
            'status' => $status,
            'created_by' => $this->creator->id,
        ], $extra));
    }

    private function makeCategory(string $naziv): CulturalCategory
    {
        return CulturalCategory::create([
            'naziv' => $naziv,
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
    }

    private function makeLocation(string $naziv): CulturalLocation
    {
        return CulturalLocation::create([
            'naziv' => $naziv,
            'status' => CulturalLocation::STATUS_ACTIVE,
        ]);
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
}
