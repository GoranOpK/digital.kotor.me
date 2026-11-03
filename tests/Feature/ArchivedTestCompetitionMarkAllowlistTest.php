<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\ApprovedHistoricalTestCompetitionIds;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArchivedTestCompetitionMarkAllowlistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        config(['archived_test_competitions.mark_ids' => '']);
    }

    public function test_only_concrete_ids_are_accepted_from_the_allowlist(): void
    {
        $parser = new ApprovedHistoricalTestCompetitionIds;

        config(['archived_test_competitions.mark_ids' => '']);
        $this->assertSame([], $parser->ids());

        config(['archived_test_competitions.mark_ids' => '*']);
        $this->assertSame([], $parser->ids());
        $this->assertFalse($parser->allows(1));

        config(['archived_test_competitions.mark_ids' => 'svi, all, 1-3, 01']);
        $this->assertSame([], $parser->ids());

        config(['archived_test_competitions.mark_ids' => '15, *, 15, 27']);
        $this->assertSame([15, 27], $parser->ids());
        $this->assertTrue($parser->allows(15));
        $this->assertFalse($parser->allows(16));
    }

    public function test_empty_allowlist_rejects_direct_marking_and_leaves_the_competition_unchanged(): void
    {
        $superadmin = $this->user('superadmin');
        $competition = $this->competition('omladinsko', 'closed', 'Stvarni arhivirani konkurs');
        $before = $this->snapshot($competition);

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-mark.preview'), [
                'competition_id' => $competition->id,
            ])
            ->assertOk()
            ->assertSee('nije na spisku odobrenom za naknadno označavanje')
            ->assertDontSee('Označi samo ovaj konkurs', false);

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-mark.store'), [
                'competition_id' => $competition->id,
                'confirmation' => 'OZNACI '.$competition->id,
            ])
            ->assertStatus(422);

        $this->assertSame($before, $this->snapshot($competition));
    }

    public function test_unapproved_id_is_rejected_when_another_id_is_approved(): void
    {
        $superadmin = $this->user('superadmin');
        $target = $this->competition('zensko', 'completed', 'Neodobreni konkurs');
        $approved = $this->competition('omladinsko', 'closed', 'Odobreni konkurs');
        config(['archived_test_competitions.mark_ids' => (string) $approved->id]);
        $before = $this->snapshot($target);

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-mark.store'), [
                'competition_id' => $target->id,
                'confirmation' => 'OZNACI '.$target->id,
            ])
            ->assertStatus(422);

        $this->assertSame($before, $this->snapshot($target));
        $this->assertFalse((bool) $approved->fresh()->is_test);
    }

    public function test_approved_id_can_be_marked_and_other_roles_stay_forbidden(): void
    {
        $superadmin = $this->user('superadmin');
        $target = $this->competition('omladinsko', 'published', 'Odobren za oznaku');
        $other = $this->competition('zensko', 'closed', 'Nije odobren');
        config(['archived_test_competitions.mark_ids' => (string) $target->id]);

        $this->actingAs($this->user('admin'))
            ->post(route('admin.archive-test-mark.store'), [
                'competition_id' => $target->id,
                'confirmation' => 'OZNACI '.$target->id,
            ])
            ->assertForbidden();
        $this->assertFalse((bool) $target->fresh()->is_test);

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-mark.store'), [
                'competition_id' => $target->id,
                'confirmation' => 'OZNACI '.$target->id,
            ])
            ->assertRedirect(route('admin.archive-test-mark.show'));

        $this->assertTrue((bool) $target->fresh()->is_test);
        $this->assertFalse((bool) $other->fresh()->is_test);
        $this->assertSame('Odobren za oznaku', $target->fresh()->title);
    }

    public function test_competition_marked_at_creation_can_be_removed_while_the_allowlist_is_empty(): void
    {
        $superadmin = $this->user('superadmin');
        $testCompetition = $this->competition('omladinsko', 'closed', 'Već označeni test', true);
        $realCompetition = $this->competition('zensko', 'closed', 'Stvarni konkurs');

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.destroy'), [
                'competition_id' => $testCompetition->id,
                'confirmation' => 'UKLONI '.$testCompetition->id,
            ])
            ->assertRedirect(route('admin.archive-test-competition.show'));

        $this->assertNull(Competition::query()->find($testCompetition->id));
        $this->assertNotNull(Competition::query()->find($realCompetition->id));
        $this->assertFalse((bool) $realCompetition->fresh()->is_test);
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function competition(string $type, string $status, string $title, bool $isTest = false): Competition
    {
        return Competition::create([
            'title' => $title,
            'description' => 'Opis',
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'type' => $type,
            'status' => $status,
            'is_test' => $isTest,
            'year' => (int) now()->year,
            'budget' => 1000,
            'deadline_days' => 20,
            'published_at' => $status === 'draft' ? null : now()->subDays(10),
        ]);
    }

    /**
     * @return array{title: string, type: string, status: string, is_test: bool, updated_at: string}
     */
    private function snapshot(Competition $competition): array
    {
        $fresh = $competition->fresh();

        return [
            'title' => (string) $fresh->title,
            'type' => (string) $fresh->type,
            'status' => (string) $fresh->status,
            'is_test' => (bool) $fresh->is_test,
            'updated_at' => $fresh->updated_at?->toJSON() ?? '',
        ];
    }
}
