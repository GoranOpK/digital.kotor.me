<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationEliminatoryCheck;
use App\Models\Commission;
use App\Models\CommissionMember;
use App\Models\Competition;
use App\Models\EvaluationScore;
use App\Models\Role;
use App\Models\User;
use App\Models\YouthDecisionDocument;
use App\Services\Competitions\YouthDecisionDocumentStore;
use App\Services\Competitions\YouthDecisionDocxWriter;
use App\Services\Competitions\YouthDecisionProposalBuilder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class YouthDecisionRedownloadTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<int> */
    private array $cleanupCompetitionIds = [];

    /** @var list<string> */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('opstinakotor_digital_testing', (string) config('database.connections.mysql.database'));
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        $sentinel = storage_path('app/private/pm-redownload-sentinel.txt');
        if (is_file($sentinel)) {
            @unlink($sentinel);
        }

        if ($this->app) {
            foreach ($this->cleanupCompetitionIds as $id) {
                Storage::disk('local')->deleteDirectory('youth-decision-documents/'.$id);
            }
        }

        parent::tearDown();
    }

    public function test_active_chairman_downloads_the_original_bytes_and_keeps_the_record(): void
    {
        $ctx = $this->youthCompetition();
        $bytes = "PK\x03\x04original-odluka";
        $document = $this->storeBytes($ctx, $bytes);
        $before = $document->only([
            'id',
            'competition_id',
            'generated_by',
            'storage_path',
            'content_sha256',
            'ranking_sha256',
        ]);

        $response = $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision.download', $ctx['competition']));

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            (string) $response->headers->get('content-type')
        );
        $this->assertStringContainsString(
            'Odluka-o-raspodjeli-sredstava-mladi-'.$ctx['competition']->year.'-'.$ctx['competition']->id.'.docx',
            (string) $response->headers->get('content-disposition')
        );
        $download = $response->baseResponse;
        $this->assertInstanceOf(BinaryFileResponse::class, $download);
        $this->assertFalse((new \ReflectionProperty($download, 'deleteFileAfterSend'))->getValue($download));
        $path = $download->getFile()->getPathname();
        $this->assertSame($bytes, file_get_contents($path));
        $this->assertFileExists(Storage::disk('local')->path($document->storage_path));
        $this->assertSame($bytes, Storage::disk('local')->get($document->storage_path));
        $this->assertSame($before, $document->fresh()->only(array_keys($before)));
        $this->assertSame(1, YouthDecisionDocument::query()->count());
    }

    public function test_member_is_forbidden_and_does_not_see_the_button(): void
    {
        $ctx = $this->youthCompetition();
        $document = $this->storeBytes($ctx, "PK\x03\x04za-clana");

        $this->actingAs($ctx['member'])
            ->get(route('evaluation.youth-decision.download', $ctx['competition']))
            ->assertForbidden();

        $memberPage = $this->actingAs($ctx['member'])
            ->get(route('evaluation.youth-decision', $ctx['competition']))
            ->assertOk()
            ->getContent();
        $this->assertStringNotContainsString('data-testid="youth-decision-download"', $memberPage);
        $this->assertStringNotContainsString('Preuzmi odluku', $memberPage);

        $chairmanPage = $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision', $ctx['competition']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('data-testid="youth-decision-download"', $chairmanPage);
        $this->assertStringContainsString('Preuzmi odluku', $chairmanPage);
        $this->assertStringNotContainsString($document->content_sha256, $chairmanPage);
        $this->assertStringNotContainsString($document->storage_path, $chairmanPage);
    }

    public function test_button_is_hidden_when_no_document_exists(): void
    {
        $ctx = $this->youthCompetition();

        $html = $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision', $ctx['competition']))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('data-testid="youth-decision-download"', $html);
        $this->assertStringNotContainsString('Preuzmi odluku', $html);
    }

    public function test_former_chairman_is_forbidden(): void
    {
        $ctx = $this->youthCompetition();
        $this->storeBytes($ctx, "PK\x03\x04bivsi");
        $ctx['chairmanMember']->update(['status' => 'inactive']);

        $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision.download', $ctx['competition']))
            ->assertForbidden();
        $this->assertSame(1, YouthDecisionDocument::query()->count());
    }

    public function test_superadmin_without_chairmanship_is_forbidden(): void
    {
        $ctx = $this->youthCompetition();
        $this->storeBytes($ctx, "PK\x03\x04superadmin");
        $superadmin = User::factory()->create([
            'role_id' => Role::query()->where('name', 'superadmin')->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($superadmin)
            ->get(route('evaluation.youth-decision.download', $ctx['competition']))
            ->assertForbidden();
    }

    public function test_chairman_of_another_competition_is_forbidden(): void
    {
        $first = $this->youthCompetition();
        $second = $this->youthCompetition();
        $this->storeBytes($first, "PK\x03\x04prvi-konkurs");

        $this->actingAs($second['chairman'])
            ->get(route('evaluation.youth-decision.download', $first['competition']))
            ->assertForbidden();
        $this->actingAs($first['chairman'])
            ->get(route('evaluation.youth-decision.download', $second['competition']))
            ->assertForbidden();
    }

    public function test_other_profile_is_forbidden(): void
    {
        $ctx = $this->youthCompetition();
        $zensko = Competition::query()->create([
            'title' => 'Zensko preuzimanje',
            'description' => 'Opis',
            'start_date' => now()->subDays(40)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'zensko',
            'status' => 'published',
            'year' => 2026,
            'deadline_days' => 20,
            'published_at' => now()->subDays(40),
            'commission_id' => $ctx['competition']->commission_id,
        ]);

        $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision.download', $zensko))
            ->assertForbidden();
    }

    public function test_missing_document_is_not_found(): void
    {
        $ctx = $this->youthCompetition();

        $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision.download', $ctx['competition']))
            ->assertNotFound();
        $this->assertSame(0, YouthDecisionDocument::query()->count());
    }

    public function test_missing_file_is_not_found_and_the_row_remains(): void
    {
        $ctx = $this->youthCompetition();
        $document = $this->storeBytes($ctx, "PK\x03\x04nestaje");
        Storage::disk('local')->delete($document->storage_path);

        $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision.download', $ctx['competition']))
            ->assertNotFound();
        $this->assertNotNull($document->fresh());
        $this->assertSame(0, YouthDecisionDocument::query()->whereKey($document->id)->whereNull('storage_path')->count());
    }

    public function test_tampered_file_is_rejected_without_revealing_the_path(): void
    {
        $ctx = $this->youthCompetition();
        $document = $this->storeBytes($ctx, "PK\x03\x04ispravan");
        $tampered = "PK\x03\x04izmijenjen";
        file_put_contents(Storage::disk('local')->path($document->storage_path), $tampered);
        $hashBefore = $document->content_sha256;

        $response = $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision.download', $ctx['competition']));

        $response->assertStatus(409);
        $this->assertSame(YouthDecisionDocumentStore::HASH_MISMATCH, $response->exception?->getMessage());
        $this->assertNull($response->headers->get('content-disposition'));
        $this->assertStringNotContainsString($document->storage_path, (string) $response->exception?->getMessage());
        $this->assertSame($tampered, Storage::disk('local')->get($document->storage_path));
        $this->assertSame($hashBefore, $document->fresh()->content_sha256);
    }

    public function test_disallowed_path_is_rejected(): void
    {
        $ctx = $this->youthCompetition();
        $sentinel = storage_path('app/private/pm-redownload-sentinel.txt');
        file_put_contents($sentinel, 'tajna-van-pm-direktorijuma');
        $this->rememberDocument($ctx, 'youth-decision-documents/'.$ctx['competition']->id.'/../pm-redownload-sentinel.txt', 'tajna-van-pm-direktorijuma', now());

        $response = $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision.download', $ctx['competition']));

        $response->assertNotFound();
        $this->assertStringNotContainsString('tajna-van-pm-direktorijuma', $response->getContent());
        $this->assertSame('tajna-van-pm-direktorijuma', file_get_contents($sentinel));
        $this->assertSame(1, YouthDecisionDocument::query()->count());
    }

    public function test_latest_document_is_downloaded_and_earlier_versions_remain(): void
    {
        $ctx = $this->youthCompetition();
        $earlierBytes = "PK\x03\x04ranija";
        $laterBytes = "PK\x03\x04novija";
        $lowerIdLaterTime = $this->rememberDocument(
            $ctx,
            $this->writtenPath($ctx['competition']->id, '01ARZ3NDEKTSV4RRFFQ69G5FAV', $earlierBytes),
            $earlierBytes,
            '2026-10-02 10:00:00'
        );
        $higherIdEarlierTime = $this->rememberDocument(
            $ctx,
            $this->writtenPath($ctx['competition']->id, '01ARZ3NDEKTSV4RRFFQ69G5FAY', $laterBytes),
            $laterBytes,
            '2026-10-01 10:00:00'
        );

        $response = $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision.download', $ctx['competition']));

        $response->assertOk();
        $this->assertSame($earlierBytes, file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->assertSame($earlierBytes, Storage::disk('local')->get($lowerIdLaterTime->storage_path));
        $this->assertSame($laterBytes, Storage::disk('local')->get($higherIdEarlierTime->storage_path));
        $this->assertSame(2, YouthDecisionDocument::query()->count());
        $this->assertTrue($lowerIdLaterTime->id < $higherIdEarlierTime->id);
    }

    public function test_same_timestamp_uses_the_higher_id(): void
    {
        $ctx = $this->youthCompetition();
        $firstBytes = "PK\x03\x04isti-cas-1";
        $secondBytes = "PK\x03\x04isti-cas-2";
        $first = $this->rememberDocument(
            $ctx,
            $this->writtenPath($ctx['competition']->id, '01ARZ3NDEKTSV4RRFFQ69G5FAV', $firstBytes),
            $firstBytes,
            '2026-10-03 10:00:00'
        );
        $second = $this->rememberDocument(
            $ctx,
            $this->writtenPath($ctx['competition']->id, '01ARZ3NDEKTSV4RRFFQ69G5FAY', $secondBytes),
            $secondBytes,
            '2026-10-03 10:00:00'
        );

        $response = $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision.download', $ctx['competition']));

        $response->assertOk();
        $this->assertSame($secondBytes, file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->assertSame($firstBytes, Storage::disk('local')->get($first->storage_path));
        $this->assertTrue($second->id > $first->id);
    }

    public function test_guest_cannot_download(): void
    {
        $ctx = $this->youthCompetition();
        $this->storeBytes($ctx, "PK\x03\x04gost");

        $this->get(route('evaluation.youth-decision.download', $ctx['competition']))
            ->assertRedirect(route('login'));
    }

    public function test_redownload_does_not_invoke_the_word_writer(): void
    {
        $ctx = $this->youthCompetition();
        $bytes = "PK\x03\x04bez-writera";
        $this->storeBytes($ctx, $bytes);
        $this->app->bind(YouthDecisionDocxWriter::class, function (): void {
            throw new \RuntimeException('writer-resolved');
        });
        $this->app->bind(YouthDecisionProposalBuilder::class, function (): void {
            throw new \RuntimeException('builder-resolved');
        });

        $response = $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision.download', $ctx['competition']));

        $response->assertOk();
        $this->assertSame($bytes, file_get_contents($response->baseResponse->getFile()->getPathname()));
        $this->assertSame(1, YouthDecisionDocument::query()->count());
    }

    public function test_generation_still_downloads_and_redownload_returns_the_same_bytes(): void
    {
        $ctx = $this->historicallyFinalYouthCompetition();

        $generated = $this->actingAs($ctx['chairman'])
            ->post(route('evaluation.youth-decision.generate', $ctx['competition']), [
                'first_session_date' => '2026-02-02',
                'second_session_date' => '2026-03-03',
                'third_session_date' => '2026-04-04',
                'oral_presentation_date' => '2026-05-05',
            ]);

        $generated->assertOk();
        $firstBytes = file_get_contents($generated->baseResponse->getFile()->getPathname());
        $this->assertIsString($firstBytes);
        $this->assertStringStartsWith("PK\x03\x04", $firstBytes);
        $document = YouthDecisionDocument::query()->firstOrFail();

        $this->app->bind(YouthDecisionDocxWriter::class, function (): void {
            throw new \RuntimeException('writer-resolved');
        });
        $again = $this->actingAs($ctx['chairman'])
            ->get(route('evaluation.youth-decision.download', $ctx['competition']));

        $again->assertOk();
        $this->assertSame($firstBytes, file_get_contents($again->baseResponse->getFile()->getPathname()));
        $this->assertSame($firstBytes, Storage::disk('local')->get($document->storage_path));
        $this->assertSame(1, YouthDecisionDocument::query()->count());
        $this->assertSame('published', $ctx['competition']->fresh()->status);
    }

    /**
     * @param  array{competition: Competition, chairman: User}  $ctx
     */
    private function storeBytes(array $ctx, string $bytes): YouthDecisionDocument
    {
        $path = tempnam(sys_get_temp_dir(), 'pm-redownload-');
        if ($path === false) {
            $this->fail('Testni ulazni fajl nije kreiran.');
        }
        file_put_contents($path, $bytes);
        $this->tempFiles[] = $path;

        return app(YouthDecisionDocumentStore::class)->store($ctx['competition'], $ctx['chairman'], $path);
    }

    /**
     * @param  array{competition: Competition, chairman: User}  $ctx
     */
    private function rememberDocument(array $ctx, string $relativePath, string $bytes, mixed $generatedAt): YouthDecisionDocument
    {
        return YouthDecisionDocument::query()->create([
            'competition_id' => $ctx['competition']->id,
            'generated_by' => $ctx['chairman']->id,
            'generated_at' => $generatedAt,
            'storage_path' => $relativePath,
            'content_sha256' => hash('sha256', $bytes),
            'ranking_sha256' => hash('sha256', '[]'),
        ]);
    }

    private function writtenPath(int $competitionId, string $ulid, string $bytes): string
    {
        $relative = 'youth-decision-documents/'.$competitionId.'/'.$ulid.'.docx';
        $absolute = Storage::disk('local')->path($relative);
        $directory = dirname($absolute);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            $this->fail('Testni direktorijum odluke nije kreiran.');
        }
        if (file_put_contents($absolute, $bytes) === false) {
            $this->fail('Testni dokument nije upisan.');
        }

        return $relative;
    }

    /**
     * @return array{competition: Competition, chairman: User, member: User, chairmanMember: CommissionMember}
     */
    private function historicallyFinalYouthCompetition(): array
    {
        $ctx = $this->youthCompetition();
        $application = Application::query()->create([
            'competition_id' => $ctx['competition']->id,
            'user_id' => User::factory()->create()->id,
            'business_plan_name' => 'Plan ispod praga',
            'applicant_type' => 'fizicko_lice',
            'physical_person_name' => 'Ana Anić',
            'status' => 'evaluated',
            'submitted_at' => now()->subDays(20),
            'evaluated_at' => now()->subDay(),
        ]);
        ApplicationEliminatoryCheck::query()->create([
            'application_id' => $application->id,
            'criterion_1' => true,
            'criterion_2' => true,
            'criterion_3' => true,
            'confirmed_at' => now()->subDays(10),
            'confirmed_by_user_id' => $ctx['chairman']->id,
            'confirmed_by_commission_member_id' => $ctx['chairmanMember']->id,
            'confirmed_by_name' => $ctx['chairman']->name,
        ]);
        foreach ([0, 1, 2] as $index) {
            $payload = [
                'application_id' => $application->id,
                'commission_member_id' => $ctx['members'][$index]->id,
                'canonical_seat_no' => $index + 1,
                'completed_at' => now()->subDay(),
            ];
            for ($criterion = 1; $criterion <= 10; $criterion++) {
                $payload['criterion_'.$criterion] = 1;
            }
            EvaluationScore::query()->create($payload);
        }
        $application->forceFill([
            'bonuses_confirmed_at' => now()->subDay(),
            'final_score' => '10.00',
        ])->save();

        return $ctx;
    }

    /**
     * @return array{competition: Competition, chairman: User, member: User, chairmanMember: CommissionMember, members: list<CommissionMember>}
     */
    private function youthCompetition(): array
    {
        $commission = Commission::query()->create([
            'name' => 'PM preuzimanje '.uniqid(),
            'year' => 2026,
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYear()->toDateString(),
            'status' => 'active',
            'profile_type' => 'omladinsko',
        ]);
        $roleId = Role::query()->where('name', 'komisija')->firstOrFail()->id;
        $chairman = null;
        $memberUser = null;
        $chairmanMember = null;
        $members = [];
        foreach ([1 => 'predsjednik', 2 => 'clan', 3 => 'clan'] as $seat => $position) {
            $user = User::factory()->create([
                'role_id' => $roleId,
                'activation_status' => 'active',
                'email_verified_at' => now(),
            ]);
            $member = CommissionMember::query()->create([
                'commission_id' => $commission->id,
                'user_id' => $user->id,
                'name' => $user->name,
                'position' => $position,
                'canonical_seat_no' => $seat,
                'status' => 'active',
            ]);
            $members[] = $member;
            if ($position === 'predsjednik') {
                $chairman = $user;
                $chairmanMember = $member;
            }
            if ($memberUser === null && $position === 'clan') {
                $memberUser = $user;
            }
        }
        $competition = Competition::query()->create([
            'title' => 'PM preuzimanje '.uniqid(),
            'description' => 'Opis',
            'start_date' => now()->subDays(40)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => 2026,
            'budget' => '100000.00',
            'deadline_days' => 20,
            'published_at' => now()->subDays(40),
            'commission_id' => $commission->id,
        ]);
        $this->cleanupCompetitionIds[] = (int) $competition->id;

        return [
            'competition' => $competition,
            'chairman' => $chairman,
            'member' => $memberUser,
            'chairmanMember' => $chairmanMember,
            'members' => $members,
        ];
    }
}
