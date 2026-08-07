<?php

namespace Tests\Feature;

use App\Exceptions\CulturalEventDomainException;
use App\Models\CulturalCategory;
use App\Models\CulturalEventEntry;
use App\Models\CulturalOccurrence;
use App\Models\Role;
use App\Models\User;
use App\Services\CulturalEventDomain\EventLifecycle;
use App\Services\CulturalEventDomain\EventWriter;
use App\Services\CulturalEventDomain\OccurrenceWriter;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * BM-PK-15 / BR-117 — max 3 istaknuta; bez auto-clear (kanonski domen).
 */
class CulturalEventEntryFeaturedLimitTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    private EventWriter $writer;

    private EventLifecycle $lifecycle;

    private OccurrenceWriter $occurrenceWriter;

    private CulturalCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->editor = User::factory()->create([
            'role_id' => Role::where('name', 'kk_admin')->firstOrFail()->id,
            'activation_status' => 'active',
        ]);

        $this->writer = app(EventWriter::class);
        $this->lifecycle = app(EventLifecycle::class);
        $this->occurrenceWriter = app(OccurrenceWriter::class);

        $this->category = CulturalCategory::create([
            'naziv' => 'Koncerti',
            'status' => CulturalCategory::STATUS_ACTIVE,
        ]);
    }

    public function test_first_featured(): void
    {
        $entry = $this->makePublishedAktuelni('A');

        $this->writer->updateContent($entry, $this->editor, ['featured' => true]);

        $this->assertTrue($entry->fresh()->featured);
        $this->assertSame(1, CulturalEventEntry::currentFeaturedAktuelniCount());
    }

    public function test_second_featured(): void
    {
        $a = $this->makePublishedAktuelni('A');
        $b = $this->makePublishedAktuelni('B');

        $this->writer->updateContent($a, $this->editor, ['featured' => true]);
        $this->writer->updateContent($b, $this->editor, ['featured' => true]);

        $this->assertTrue($a->fresh()->featured);
        $this->assertTrue($b->fresh()->featured);
        $this->assertSame(2, CulturalEventEntry::currentFeaturedAktuelniCount());
    }

    public function test_third_featured(): void
    {
        $entries = [
            $this->makePublishedAktuelni('A'),
            $this->makePublishedAktuelni('B'),
            $this->makePublishedAktuelni('C'),
        ];

        foreach ($entries as $entry) {
            $this->writer->updateContent($entry, $this->editor, ['featured' => true]);
        }

        foreach ($entries as $entry) {
            $this->assertTrue($entry->fresh()->featured);
        }
        $this->assertSame(3, CulturalEventEntry::currentFeaturedAktuelniCount());
        $this->assertSame(CulturalEventEntry::MAX_FEATURED, 3);
    }

    public function test_fourth_featured_is_rejected_without_clearing_others(): void
    {
        $featured = [
            $this->makePublishedAktuelni('A'),
            $this->makePublishedAktuelni('B'),
            $this->makePublishedAktuelni('C'),
        ];
        foreach ($featured as $entry) {
            $this->writer->updateContent($entry, $this->editor, ['featured' => true]);
        }

        $fourth = $this->makePublishedAktuelni('D');

        try {
            $this->writer->updateContent($fourth, $this->editor, ['featured' => true]);
            $this->fail('Expected featured capacity exception');
        } catch (CulturalEventDomainException $e) {
            $this->assertSame('Najviše tri događaja mogu biti istaknuta istovremeno.', $e->getMessage());
        }

        $this->assertFalse($fourth->fresh()->featured);
        foreach ($featured as $entry) {
            $this->assertTrue($entry->fresh()->featured, 'Sistem ne smije skidati postojeća isticanja.');
        }
        $this->assertSame(3, CulturalEventEntry::currentFeaturedAktuelniCount());
    }

    public function test_draft_cannot_be_featured(): void
    {
        $draft = $this->writer->createDraft($this->editor, [
            'naslov' => 'Nacrt',
            'category_id' => $this->category->id,
        ]);

        $this->expectException(CulturalEventDomainException::class);
        $this->writer->updateContent($draft, $this->editor, ['featured' => true]);
    }

    public function test_pending_cannot_be_featured(): void
    {
        $entry = $this->makeReadyDraft('Pending');
        $this->lifecycle->submitForApproval($entry, $this->editor);
        $entry->refresh();
        $this->assertSame(CulturalEventEntry::STATUS_PENDING_APPROVAL, $entry->status);

        $this->expectException(CulturalEventDomainException::class);
        $this->writer->updateContent($entry, $this->editor, ['featured' => true]);
    }

    public function test_cancelled_is_not_featured(): void
    {
        $entry = $this->makePublishedAktuelni('X');
        $this->writer->updateContent($entry, $this->editor, ['featured' => true]);
        $this->assertTrue($entry->fresh()->featured);

        $this->lifecycle->cancel($entry->fresh(), $this->editor, 'Otkaz');
        $entry->refresh();

        $this->assertTrue($entry->isCancelled());
        $this->assertFalse($entry->featured);

        try {
            $this->writer->updateContent($entry, $this->editor, ['featured' => true]);
            $this->fail('Expected cancelled read-only / featured rejection');
        } catch (CulturalEventDomainException $e) {
            $this->assertStringContainsString('Otkazan', $e->getMessage());
        }

        $this->assertFalse($entry->fresh()->featured);
    }

    private function makePublishedAktuelni(string $naslov): CulturalEventEntry
    {
        $entry = $this->makeReadyDraft($naslov);
        $this->lifecycle->publishDirectly($entry, $this->editor);

        return $entry->fresh();
    }

    private function makeReadyDraft(string $naslov): CulturalEventEntry
    {
        $entry = $this->writer->createDraft($this->editor, [
            'naslov' => $naslov,
            'category_id' => $this->category->id,
        ]);

        $this->occurrenceWriter->create($entry, [
            'datum' => now()->addDays(7)->toDateString(),
            'cjelodnevno' => true,
        ]);

        return $entry->fresh();
    }
}
