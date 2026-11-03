<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ArchivedCompetitionArchiveProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    public function test_real_archived_competition_cannot_be_deleted_or_removed(): void
    {
        $superadmin = $this->user('superadmin');
        $competition = $this->competition('omladinsko', 'closed', 'Stvarni konkurs');

        foreach (['superadmin', 'admin', 'konkurs_admin'] as $role) {
            $this->actingAs($this->user($role))
                ->delete(route('admin.competitions.destroy', $competition))
                ->assertForbidden();
        }

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.preview'), [
                'competition_id' => $competition->id,
            ])
            ->assertOk()
            ->assertSee('izričito označen kao testni')
            ->assertDontSee('Ukloni samo ovaj konkurs', false);

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.destroy'), [
                'competition_id' => $competition->id,
                'confirmation' => 'UKLONI '.$competition->id,
            ])
            ->assertStatus(422);

        $this->assertNotNull(Competition::query()->find($competition->id));
        $this->assertFalse((bool) $competition->fresh()->is_test);
    }

    public function test_title_does_not_make_a_competition_test_and_only_superadmin_can_set_the_flag(): void
    {
        $admin = $this->user('admin');
        $superadmin = $this->user('superadmin');

        $this->actingAs($admin)
            ->post(route('admin.competitions.store'), $this->storePayload('Testni konkurs po nazivu', [
                'is_test' => '1',
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');

        $ignored = Competition::query()->where('title', 'Testni konkurs po nazivu')->firstOrFail();
        $this->assertFalse((bool) $ignored->is_test);

        $this->actingAs($superadmin)
            ->post(route('admin.competitions.store'), $this->storePayload('Izričito testni konkurs', [
                'is_test' => '1',
                'up_number' => 'UP-IZRICIT',
            ]))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertTrue((bool) Competition::query()->where('title', 'Izričito testni konkurs')->firstOrFail()->is_test);

        $this->actingAs($superadmin)
            ->post(route('admin.competitions.store'), $this->storePayload('Testni bez kvačice', [
                'up_number' => 'UP-BEZ-KVACICE',
            ]))
            ->assertRedirect();
        $this->assertFalse((bool) Competition::query()->where('title', 'Testni bez kvačice')->firstOrFail()->is_test);
    }

    public function test_flag_cannot_be_changed_after_publication_or_by_another_role(): void
    {
        $superadmin = $this->user('superadmin');
        $admin = $this->user('admin');
        $published = $this->competition('zensko', 'published', 'Objavljeni ŽP');
        $draft = $this->competition('zensko', 'draft', 'Nacrt ŽP');

        $this->actingAs($superadmin)
            ->put(route('admin.competitions.update', $published), $this->updatePayload($published, [
                'is_test' => '1',
            ]))
            ->assertStatus(422);
        $this->assertFalse((bool) $published->fresh()->is_test);
        $this->assertSame('Objavljeni ŽP', $published->fresh()->title);

        $this->actingAs($admin)
            ->put(route('admin.competitions.update', $draft), $this->updatePayload($draft, [
                'is_test' => '1',
            ]))
            ->assertForbidden();
        $this->assertFalse((bool) $draft->fresh()->is_test);

        $this->actingAs($superadmin)
            ->put(route('admin.competitions.update', $draft), $this->updatePayload($draft, [
                'is_test' => '1',
            ]))
            ->assertRedirect();
        $this->assertTrue((bool) $draft->fresh()->is_test);
    }

    public function test_historical_mark_requires_exact_confirmation_and_does_not_delete(): void
    {
        $superadmin = $this->user('superadmin');
        $target = $this->competition('omladinsko', 'closed', 'Test 2024');
        $other = $this->competition('zensko', 'completed', 'Drugi stvarni konkurs');

        foreach (['admin', 'konkurs_admin', 'komisija', 'korisnik'] as $role) {
            $this->actingAs($this->user($role))
                ->post(route('admin.archive-test-mark.store'), [
                    'competition_id' => $target->id,
                    'confirmation' => 'OZNACI '.$target->id,
                ])
                ->assertForbidden();
        }

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-mark.store'), [
                'competition_id' => [$target->id, $other->id],
                'confirmation' => 'OZNACI '.$target->id,
            ])
            ->assertStatus(422);
        config([
            'archived_test_competitions.mark_ids' => (string) $target->id,
        ]);

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-mark.preview'), [
                'competition_id' => $target->id,
            ])
            ->assertOk()
            ->assertSee('Test 2024')
            ->assertSee('OZNACI '.$target->id);
        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-mark.store'), [
                'competition_id' => $target->id,
                'confirmation' => 'OZNACI 999999',
            ])
            ->assertStatus(422);
        $this->assertFalse((bool) $target->fresh()->is_test);

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-mark.store'), [
                'competition_id' => $target->id,
                'confirmation' => 'OZNACI '.$target->id,
            ])
            ->assertRedirect(route('admin.archive-test-mark.show'));

        $this->assertTrue((bool) $target->fresh()->is_test);
        $this->assertFalse((bool) $other->fresh()->is_test);
        $this->assertNotNull(Competition::query()->find($target->id));
        $log = DB::table('logs')
            ->where('user_id', $superadmin->id)
            ->where('action', 'archived_test_competition_marked')
            ->get()
            ->first(fn ($row): bool => str_contains((string) $row->details, '"result":"marked"'));
        $this->assertNotNull($log);
        $this->assertStringContainsString('"competition_id":'.$target->id, (string) $log->details);

        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.destroy'), [
                'competition_id' => $other->id,
                'confirmation' => 'UKLONI '.$other->id,
            ])
            ->assertStatus(422);
        $this->assertNotNull(Competition::query()->find($other->id));
    }

    public function test_closed_canonical_call_stays_outside_the_test_removal_and_draft_delete_remains(): void
    {
        $admin = $this->user('admin');
        $superadmin = $this->user('superadmin');
        $closed = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'completed', 'MMSP završen');
        $draft = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'draft', 'MMSP nacrt');

        $this->actingAs($admin)
            ->delete(route('admin.competitions.destroy', $closed))
            ->assertForbidden();
        $this->actingAs($superadmin)
            ->post(route('admin.archive-test-competition.destroy'), [
                'competition_id' => $closed->id,
                'confirmation' => 'UKLONI '.$closed->id,
            ])
            ->assertStatus(422);
        $this->assertNotNull(Competition::query()->find($closed->id));

        $this->actingAs($admin)
            ->delete(route('admin.competitions.destroy', $draft))
            ->assertRedirect(route('admin.competitions.index'));
        $this->assertNull(Competition::query()->find($draft->id));
    }

    public function test_archive_screens_hide_the_regular_delete_action(): void
    {
        $admin = $this->user('admin');
        $this->competition('omladinsko', 'closed', 'Arhivirani PM');

        $this->actingAs($admin)
            ->get(route('admin.competitions.index', ['tab' => 'archive']))
            ->assertOk()
            ->assertSee('Arhivirani PM')
            ->assertDontSee('Obriši', false);
        $this->actingAs($admin)
            ->get(route('admin.competitions.show', Competition::query()->where('title', 'Arhivirani PM')->firstOrFail()))
            ->assertOk()
            ->assertDontSee('Obriši', false);
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::query()->where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function competition(string $type, string $status, string $title): Competition
    {
        return Competition::create([
            'title' => $title,
            'description' => 'Opis',
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'type' => $type,
            'status' => $status,
            'is_test' => false,
            'year' => (int) now()->year,
            'budget' => 1000,
            'deadline_days' => 20,
            'published_at' => $status === 'draft' ? null : now()->subDays(10),
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function storePayload(string $title, array $overrides = []): array
    {
        return array_merge([
            'title' => $title,
            'description' => 'Opis',
            'type' => 'zensko',
            'up_number' => 'UP-'.substr(md5($title), 0, 8),
            'year' => (int) now()->year,
            'budget' => '1000.00',
            'start_date' => now()->toDateString(),
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function updatePayload(Competition $competition, array $overrides = []): array
    {
        return array_merge([
            'title' => $competition->title,
            'description' => $competition->description,
            'type' => $competition->type,
            'up_number' => 'UP-IZMJENA',
            'year' => $competition->year,
            'budget' => $competition->budget,
            'start_date' => $competition->start_date?->toDateString(),
            'status' => $competition->status,
        ], $overrides);
    }
}
