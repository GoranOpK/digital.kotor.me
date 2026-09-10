<?php

namespace Tests\Feature;

use App\Identity\Backfill\IdentityBackfillProjector;
use App\Identity\CanonicalIdentityReader;
use App\Identity\CanonicalIdentityWriter;
use App\Identity\Census\IdentityCensusFieldStatus;
use App\Identity\Census\IdentityCensusService;
use App\Identity\IdentityCanonicalGraphFingerprint;
use App\Identity\LegacyIdentityAdapter;
use App\Identity\Runtime\CurrentIdentityResolver;
use App\Identity\Runtime\IdentityAccess;
use App\Identity\Shadow\Comparators\DashboardDisplayComparator;
use App\Identity\Shadow\IdentityShadowCanonicalLoader;
use App\Identity\Shadow\IdentityShadowStatus;
use App\Models\PhysicalPersonIdentity;
use App\Models\PlatformIdentity;
use App\Models\User;
use App\Security\JmbEncryptedReadException;
use App\Security\JmbEncryptionService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\Support\MakesCanonicalUsers;
use Tests\Support\MakesIdentitySnapshots;
use Tests\TestCase;

class JmbPlaintextValueReadCutoverTest extends TestCase
{
    use MakesCanonicalUsers;
    use MakesIdentitySnapshots;
    use RefreshDatabase;

    private int $jmbSerial = 500;

    /**
     * @var list<string>
     */
    private array $loggedMessages = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        config([
            'identity.canonical_read' => true,
            'identity.canonical_write' => true,
            'identity.identity_write_freeze' => false,
        ]);
        $this->app->forgetInstance(JmbEncryptionService::class);
        $this->loggedMessages = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event): void {
            $this->loggedMessages[] = $event->message.' '.json_encode($event->context, JSON_UNESCAPED_UNICODE);
        });
    }

    public function test_profile_and_canonical_jmb_resolve_encrypted_first(): void
    {
        $logical = $this->nextJmb();
        $desynced = $this->nextJmb();
        $user = $this->makeKorisnik(['jmb' => null, 'email' => 'value-read-profile@example.test']);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => ['jmb' => $logical],
        ]));
        $fl = PhysicalPersonIdentity::query()->firstOrFail();
        DB::table('physical_person_identities')->where('id', $fl->id)->update(['jmb' => $desynced]);

        $view = app(CurrentIdentityResolver::class)->viewFor($user->fresh());
        $this->assertSame(IdentityAccess::CURRENT, $view->access);
        $this->assertSame($logical, $view->jmb);
        $this->assertSame($logical, (new CanonicalIdentityReader)->forUser($user->fresh())->physicalPerson?->jmb);

        $this->actingAs($user->fresh())
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee($logical, false)
            ->assertDontSee($desynced, false);
        $this->assertLogsDoNotLeak([$logical, $desynced]);
    }

    public function test_legacy_user_jmb_resolves_encrypted_first(): void
    {
        $logical = $this->nextJmb();
        $desynced = $this->nextJmb();
        $user = $this->makeKorisnik(['jmb' => $logical, 'email' => 'value-read-legacy@example.test']);
        DB::table('users')->where('id', $user->id)->update(['jmb' => $desynced]);

        config(['identity.canonical_read' => false]);
        $view = app(CurrentIdentityResolver::class)->viewFor($user->fresh());
        $this->assertSame($logical, $view->jmb);
        $this->assertSame($logical, (new LegacyIdentityAdapter)->forUser($user->fresh())?->legacyFacts?->jmb);
    }

    public function test_decrypt_failure_does_not_fall_back_to_plaintext(): void
    {
        $logical = $this->nextJmb();
        $user = $this->makeKorisnik(['jmb' => $logical, 'email' => 'value-read-fail@example.test']);
        DB::table('users')->where('id', $user->id)->update(['jmb_encrypted' => 'jmb:v1:tampered']);

        config(['identity.canonical_read' => false]);
        $view = app(CurrentIdentityResolver::class)->viewFor($user->fresh());
        $this->assertSame(IdentityAccess::INVALID, $view->access);
        $this->assertNull($view->jmb);

        try {
            (new LegacyIdentityAdapter)->forUser($user->fresh());
            $this->fail('Legacy adapter must fail closed on decrypt failure.');
        } catch (JmbEncryptedReadException $e) {
            $this->assertStringNotContainsString($logical, $e->getMessage());
        }

        $classified = (new IdentityCensusService)->classify($user->fresh());
        $this->assertSame(IdentityCensusFieldStatus::PRESENT_VALID, $classified->fieldStatuses['jmb']);
        $this->assertLogsDoNotLeak([$logical]);
    }

    public function test_plaintext_fallback_only_when_encrypted_absent(): void
    {
        $plaintext = $this->nextJmb();
        $user = $this->makeKorisnik(['jmb' => $plaintext, 'email' => 'value-read-fallback@example.test']);
        DB::table('users')->where('id', $user->id)->update(['jmb_encrypted' => null]);

        config(['identity.canonical_read' => false]);
        $view = app(CurrentIdentityResolver::class)->viewFor($user->fresh());
        $this->assertSame($plaintext, $view->jmb);
        $this->assertSame($plaintext, (new LegacyIdentityAdapter)->forUser($user->fresh())?->legacyFacts?->jmb);
    }

    public function test_projector_and_fingerprint_use_logical_jmb_not_desynced_plaintext(): void
    {
        $logical = $this->nextJmb();
        $desynced = $this->nextJmb();
        $user = $this->makeKorisnik([
            'jmb' => $logical,
            'email' => 'value-read-projector@example.test',
            'user_type' => \App\Support\UserType::PHYSICAL_PERSON,
            'residential_status' => 'resident',
        ]);
        $classified = (new IdentityCensusService)->classify($user);
        $this->assertTrue($classified->fullyBackfillable);

        DB::table('users')->where('id', $user->id)->update(['jmb' => $desynced]);
        $projected = (new IdentityBackfillProjector)->project($user->fresh(), $classified);
        $this->assertSame($logical, $projected?->physicalPerson?->jmb);
        $this->assertNotSame($desynced, $projected?->physicalPerson?->jmb);

        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => ['jmb' => $logical],
        ]));
        $platform = PlatformIdentity::query()->where('user_id', $user->id)->firstOrFail();
        $fl = PhysicalPersonIdentity::query()->where('platform_identity_id', $platform->id)->firstOrFail();
        DB::table('physical_person_identities')->where('id', $fl->id)->update(['jmb' => $desynced]);

        $persisted = IdentityCanonicalGraphFingerprint::fromPersistedPhysicalPersonGraph(
            $platform->fresh(),
            $fl->fresh()
        );
        $this->assertSame($logical, $persisted['jmb']);
        $this->assertNotSame($desynced, $persisted['jmb']);
    }

    public function test_shadow_leftover_jmb_ignores_desynced_plaintext_and_matches_canonical(): void
    {
        $logical = $this->nextJmb();
        $desynced = $this->nextJmb();
        $user = $this->makeKorisnik([
            'jmb' => $logical,
            'email' => 'value-read-shadow@example.test',
        ]);
        $snapshot = (new IdentityBackfillProjector)->project(
            $user,
            (new IdentityCensusService)->classify($user)
        );
        $this->assertNotNull($snapshot);

        DB::table('users')->where('id', $user->id)->update(['jmb' => $desynced]);
        $result = (new DashboardDisplayComparator)->compare($user->fresh(), $snapshot);
        $this->assertSame(IdentityShadowStatus::MATCH, $result->status);
    }

    public function test_shadow_loader_reads_canonical_jmb_encrypted_first(): void
    {
        $logical = $this->nextJmb();
        $desynced = $this->nextJmb();
        $user = $this->makeKorisnik(['jmb' => null, 'email' => 'value-read-loader@example.test']);
        (new CanonicalIdentityWriter)->createForUser($user, $this->flSnapshot($user, [
            'person' => ['jmb' => $logical],
        ]));
        $fl = PhysicalPersonIdentity::query()->firstOrFail();
        DB::table('physical_person_identities')->where('id', $fl->id)->update(['jmb' => $desynced]);

        $graphs = (new IdentityShadowCanonicalLoader)->loadGraphsForUserIds(
            (string) config('database.default'),
            [(int) $user->id]
        );
        $snapshot = (new IdentityShadowCanonicalLoader)->snapshotFromValidStep4Graph($graphs[(int) $user->id]);
        $this->assertSame($logical, $snapshot->physicalPerson?->jmb);
        $this->assertNotSame($desynced, $snapshot->physicalPerson?->jmb);
    }

    public function test_census_still_measures_leftover_plaintext_jmb_column(): void
    {
        $logical = $this->nextJmb();
        $leftover = $this->nextJmb();
        $user = $this->makeKorisnik(['jmb' => $logical, 'email' => 'value-read-census@example.test']);
        DB::table('users')->where('id', $user->id)->update(['jmb' => $leftover]);

        $classified = (new IdentityCensusService)->classify($user->fresh());
        $this->assertSame(IdentityCensusFieldStatus::PRESENT_VALID, $classified->fieldStatuses['jmb']);
        $this->assertSame($leftover, $user->fresh()->jmb);
        $this->assertNotSame($leftover, (new LegacyIdentityAdapter)->forUser($user->fresh())?->legacyFacts?->jmb);
    }

    public function test_users_jmb_write_and_unique_constraint_remain(): void
    {
        $jmb = $this->nextJmb();
        $user = User::factory()->create(['email' => 'value-read-write@example.test', 'jmb' => $jmb]);
        $this->assertSame($jmb, DB::table('users')->where('id', $user->id)->value('jmb'));
        $this->assertNotNull(DB::table('users')->where('id', $user->id)->value('jmb_encrypted'));
        $this->assertNotNull(DB::table('users')->where('id', $user->id)->value('jmb_lookup'));
        $this->assertContains('jmb', $this->uniqueColumns('users'));
    }

    private function nextJmb(): string
    {
        $this->jmbSerial++;

        return $this->validJmb($this->jmbSerial);
    }

    /**
     * @param  list<string>  $secrets
     */
    private function assertLogsDoNotLeak(array $secrets): void
    {
        $combined = implode("\n", $this->loggedMessages);
        foreach ($secrets as $secret) {
            $this->assertStringNotContainsString($secret, $combined);
        }
        $this->assertStringNotContainsString((string) config('jmb.encryption.key'), $combined);
        $this->assertStringNotContainsString((string) config('jmb.lookup.key'), $combined);
    }

    /**
     * @return list<string>
     */
    private function uniqueColumns(string $table): array
    {
        $indexes = DB::select('SHOW INDEX FROM `'.$table.'` WHERE Non_unique = 0');
        $columns = [];
        foreach ($indexes as $index) {
            if (($index->Key_name ?? '') === 'PRIMARY') {
                continue;
            }
            $columns[] = (string) $index->Column_name;
        }

        return array_values(array_unique($columns));
    }
}
