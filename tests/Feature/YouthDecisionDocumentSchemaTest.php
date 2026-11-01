<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\User;
use App\Models\YouthDecisionDocument;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

class YouthDecisionDocumentSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_has_the_adopted_columns_and_index(): void
    {
        $this->assertSame('opstinakotor_digital_testing', (string) config('database.connections.mysql.database'));
        $this->assertTrue(Schema::hasTable('youth_decision_documents'));
        $this->assertTrue(Schema::hasColumns('youth_decision_documents', [
            'id',
            'competition_id',
            'generated_by',
            'generated_at',
            'storage_path',
            'content_sha256',
            'ranking_sha256',
        ]));
        $this->assertFalse(Schema::hasColumn('youth_decision_documents', 'updated_at'));
        $this->assertFalse(Schema::hasColumn('youth_decision_documents', 'decision_number'));
        $this->assertFalse(Schema::hasColumn('youth_decision_documents', 'status'));

        $indexes = collect(Schema::getIndexes('youth_decision_documents'));
        $this->assertTrue($indexes->contains(
            fn (array $index) => $index['name'] === 'ydd_storage_path_unique' && $index['unique'] === true
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index) => $index['name'] === 'ydd_competition_generated_at_idx'
                && $index['unique'] === false
                && $index['columns'] === ['competition_id', 'generated_at']
        ));
        $this->assertFalse($indexes->contains(
            fn (array $index) => $index['columns'] === ['competition_id'] && $index['unique'] === true
        ));
    }

    public function test_document_can_be_created_and_related(): void
    {
        $document = $this->createDocument();
        $document->refresh();

        $this->assertNotNull($document->id);
        $this->assertSame(64, strlen($document->content_sha256));
        $this->assertSame(64, strlen($document->ranking_sha256));
        $this->assertNotNull($document->generated_at);
        $this->assertTrue($document->competition->is(
            Competition::query()->findOrFail($document->competition_id)
        ));
        $this->assertTrue($document->generatedBy->is(
            User::query()->findOrFail($document->generated_by)
        ));
    }

    public function test_same_competition_can_have_multiple_documents(): void
    {
        $first = $this->createDocument();

        $second = YouthDecisionDocument::query()->create([
            'competition_id' => $first->competition_id,
            'generated_by' => $first->generated_by,
            'generated_at' => now(),
            'storage_path' => 'youth-decision-documents/'.$first->competition_id.'/second.docx',
            'content_sha256' => str_repeat('b', 64),
            'ranking_sha256' => str_repeat('c', 64),
        ]);

        $this->assertSame($first->competition_id, $second->competition_id);
        $this->assertSame(2, YouthDecisionDocument::query()->where('competition_id', $first->competition_id)->count());
    }

    public function test_storage_path_is_unique(): void
    {
        $document = $this->createDocument();

        try {
            YouthDecisionDocument::query()->create([
                'competition_id' => $document->competition_id,
                'generated_by' => $document->generated_by,
                'generated_at' => now(),
                'storage_path' => $document->storage_path,
                'content_sha256' => str_repeat('d', 64),
                'ranking_sha256' => str_repeat('e', 64),
            ]);
            $this->fail('Ista putanja mora biti odbijena.');
        } catch (QueryException) {
            $this->assertSame(1, YouthDecisionDocument::query()->count());
        }
    }

    public function test_missing_competition_is_rejected(): void
    {
        $user = User::factory()->create();

        try {
            YouthDecisionDocument::query()->create([
                'competition_id' => 999999999,
                'generated_by' => $user->id,
                'generated_at' => now(),
                'storage_path' => 'youth-decision-documents/missing-competition.docx',
                'content_sha256' => str_repeat('a', 64),
                'ranking_sha256' => str_repeat('b', 64),
            ]);
            $this->fail('Nepostojeći konkurs mora biti odbijen.');
        } catch (QueryException) {
            $this->assertSame(0, YouthDecisionDocument::query()->count());
        }
    }

    public function test_missing_user_is_rejected(): void
    {
        $competition = $this->createCompetition();

        try {
            YouthDecisionDocument::query()->create([
                'competition_id' => $competition->id,
                'generated_by' => 999999999,
                'generated_at' => now(),
                'storage_path' => 'youth-decision-documents/missing-user.docx',
                'content_sha256' => str_repeat('a', 64),
                'ranking_sha256' => str_repeat('b', 64),
            ]);
            $this->fail('Nepostojeći korisnik mora biti odbijen.');
        } catch (QueryException) {
            $this->assertSame(0, YouthDecisionDocument::query()->count());
        }
    }

    public function test_required_fields_are_rejected_when_missing(): void
    {
        $competition = $this->createCompetition();
        $user = User::factory()->create();

        try {
            YouthDecisionDocument::query()->create([
                'competition_id' => $competition->id,
                'generated_by' => $user->id,
                'storage_path' => 'youth-decision-documents/missing-time.docx',
                'content_sha256' => str_repeat('a', 64),
                'ranking_sha256' => str_repeat('b', 64),
            ]);
            $this->fail('Vrijeme generisanja mora biti obavezno.');
        } catch (QueryException) {
            $this->assertSame(0, YouthDecisionDocument::query()->count());
        }
    }

    public function test_sha256_must_be_64_hex_characters(): void
    {
        $competition = $this->createCompetition();
        $user = User::factory()->create();

        try {
            YouthDecisionDocument::query()->create([
                'competition_id' => $competition->id,
                'generated_by' => $user->id,
                'generated_at' => now(),
                'storage_path' => 'youth-decision-documents/bad-hash.docx',
                'content_sha256' => str_repeat('g', 64),
                'ranking_sha256' => str_repeat('a', 64),
            ]);
            $this->fail('Neispravan SHA-256 mora biti odbijen.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame(
                'SHA-256 zapis PM odluke mora imati 64 heksadecimalna karaktera.',
                $exception->getMessage()
            );
        }

        $this->assertSame(0, YouthDecisionDocument::query()->count());
    }

    public function test_sha256_is_stored_in_lowercase(): void
    {
        $competition = $this->createCompetition();
        $user = User::factory()->create();

        $document = YouthDecisionDocument::query()->create([
            'competition_id' => $competition->id,
            'generated_by' => $user->id,
            'generated_at' => now(),
            'storage_path' => 'youth-decision-documents/upper.docx',
            'content_sha256' => str_repeat('A', 64),
            'ranking_sha256' => str_repeat('B', 64),
        ]);

        $this->assertSame(str_repeat('a', 64), $document->fresh()->content_sha256);
        $this->assertSame(str_repeat('b', 64), $document->fresh()->ranking_sha256);
    }

    public function test_existing_document_cannot_be_updated(): void
    {
        $document = $this->createDocument();
        $original = $document->storage_path;

        try {
            $document->update(['storage_path' => 'youth-decision-documents/tampered.docx']);
            $this->fail('Izmjena zapisa mora biti odbijena.');
        } catch (LogicException $exception) {
            $this->assertSame('Zapis PM odluke ne može da se mijenja.', $exception->getMessage());
        }

        $this->assertSame($original, $document->fresh()->storage_path);
    }

    public function test_existing_document_cannot_be_deleted(): void
    {
        $document = $this->createDocument();

        try {
            $document->delete();
            $this->fail('Brisanje zapisa mora biti odbijeno.');
        } catch (LogicException $exception) {
            $this->assertSame('Zapis PM odluke ne može da se briše.', $exception->getMessage());
        }

        $this->assertDatabaseHas('youth_decision_documents', ['id' => $document->id]);
    }

    public function test_competition_with_documents_cannot_be_deleted(): void
    {
        $document = $this->createDocument();

        try {
            $document->competition->delete();
            $this->fail('Konkurs sa povezanim dokumentom mora biti zaštićen.');
        } catch (QueryException) {
            $this->assertDatabaseHas('youth_decision_documents', ['id' => $document->id]);
            $this->assertDatabaseHas('competitions', ['id' => $document->competition_id]);
        }
    }

    public function test_user_with_documents_cannot_be_deleted(): void
    {
        $document = $this->createDocument();

        try {
            $document->generatedBy->delete();
            $this->fail('Korisnik sa povezanim dokumentom mora biti zaštićen.');
        } catch (QueryException) {
            $this->assertDatabaseHas('youth_decision_documents', ['id' => $document->id]);
            $this->assertDatabaseHas('users', ['id' => $document->generated_by]);
        }
    }

    private function createDocument(): YouthDecisionDocument
    {
        $competition = $this->createCompetition();
        $user = User::factory()->create();

        return YouthDecisionDocument::query()->create([
            'competition_id' => $competition->id,
            'generated_by' => $user->id,
            'generated_at' => now(),
            'storage_path' => 'youth-decision-documents/'.$competition->id.'/'.str_repeat('a', 26).'.docx',
            'content_sha256' => str_repeat('a', 64),
            'ranking_sha256' => str_repeat('b', 64),
        ]);
    }

    private function createCompetition(): Competition
    {
        return Competition::query()->create([
            'title' => 'PM — evidencija odluke',
            'description' => 'Opis',
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(5)->toDateString(),
            'type' => 'omladinsko',
            'status' => 'published',
            'year' => 2026,
            'deadline_days' => 20,
            'published_at' => now()->subDays(40),
        ]);
    }
}
