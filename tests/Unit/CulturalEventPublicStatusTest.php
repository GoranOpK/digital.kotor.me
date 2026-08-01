<?php

namespace Tests\Unit;

use App\Models\CulturalEvent;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CulturalEventPublicStatusTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function event(array $overrides = []): CulturalEvent
    {
        $event = new CulturalEvent;
        $event->forceFill(array_merge([
            'naslov' => 'Test',
            'opis' => null,
            'datum_od' => '2026-08-10',
            'datum_do' => null,
            'vrijeme' => null,
            'vrijeme_do' => null,
            'lokacija' => 'Kotor',
            'kategorija' => 'Koncerti',
            'status' => 'published',
            'featured' => false,
        ], $overrides));

        return $event;
    }

    public function test_cancelled_has_absolute_priority_over_dates(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', 'Europe/Belgrade'));

        $status = $this->event([
            'status' => 'cancelled',
            'datum_od' => '2026-08-20',
            'vrijeme' => '18:00:00',
        ])->publicStatus();

        $this->assertSame([
            'key' => 'cancelled',
            'label' => 'Otkazan',
            'class' => 'kk-status-cancelled',
        ], $status);
    }

    public function test_all_day_future_is_upcoming(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-09 12:00:00', 'Europe/Belgrade'));

        $status = $this->event(['datum_od' => '2026-08-10'])->publicStatus();

        $this->assertSame('upcoming', $status['key']);
        $this->assertSame('Predstoji', $status['label']);
    }

    public function test_all_day_today_is_ongoing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', 'Europe/Belgrade'));

        $status = $this->event(['datum_od' => '2026-08-10'])->publicStatus();

        $this->assertSame('ongoing', $status['key']);
        $this->assertSame('U toku', $status['label']);
    }

    public function test_all_day_past_is_finished(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-11 00:00:00', 'Europe/Belgrade'));

        $status = $this->event(['datum_od' => '2026-08-10'])->publicStatus();

        $this->assertSame('finished', $status['key']);
        $this->assertSame('Završen', $status['label']);
    }

    public function test_single_day_before_start_time_is_upcoming(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 17:59:00', 'Europe/Belgrade'));

        $status = $this->event([
            'vrijeme' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ])->publicStatus();

        $this->assertSame('upcoming', $status['key']);
    }

    public function test_single_day_between_start_and_end_is_ongoing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 19:00:00', 'Europe/Belgrade'));

        $status = $this->event([
            'vrijeme' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ])->publicStatus();

        $this->assertSame('ongoing', $status['key']);
    }

    public function test_single_day_after_end_time_is_finished(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 20:00:01', 'Europe/Belgrade'));

        $status = $this->event([
            'vrijeme' => '18:00:00',
            'vrijeme_do' => '20:00:00',
        ])->publicStatus();

        $this->assertSame('finished', $status['key']);
    }

    public function test_single_day_without_end_time_ongoing_until_end_of_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 23:30:00', 'Europe/Belgrade'));

        $status = $this->event([
            'vrijeme' => '18:00:00',
            'vrijeme_do' => null,
        ])->publicStatus();

        $this->assertSame('ongoing', $status['key']);

        Carbon::setTestNow(Carbon::parse('2026-08-11 00:00:00', 'Europe/Belgrade'));
        $this->assertSame('finished', $this->event([
            'vrijeme' => '18:00:00',
        ])->publicStatus()['key']);
    }

    public function test_multi_day_before_period_is_upcoming(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-09 23:59:59', 'Europe/Belgrade'));

        $status = $this->event([
            'datum_od' => '2026-08-10',
            'datum_do' => '2026-08-12',
        ])->publicStatus();

        $this->assertSame('upcoming', $status['key']);
    }

    public function test_multi_day_inside_period_is_ongoing(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-11 12:00:00', 'Europe/Belgrade'));

        $status = $this->event([
            'datum_od' => '2026-08-10',
            'datum_do' => '2026-08-12',
        ])->publicStatus();

        $this->assertSame('ongoing', $status['key']);
    }

    public function test_multi_day_after_period_is_finished(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-13 00:00:00', 'Europe/Belgrade'));

        $status = $this->event([
            'datum_od' => '2026-08-10',
            'datum_do' => '2026-08-12',
        ])->publicStatus();

        $this->assertSame('finished', $status['key']);
    }

    public function test_multi_day_with_start_and_end_times(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 09:00:00', 'Europe/Belgrade'));

        $event = $this->event([
            'datum_od' => '2026-08-10',
            'datum_do' => '2026-08-12',
            'vrijeme' => '10:00:00',
            'vrijeme_do' => '18:00:00',
        ]);

        $this->assertSame('upcoming', $event->publicStatus()['key']);

        Carbon::setTestNow(Carbon::parse('2026-08-11 12:00:00', 'Europe/Belgrade'));
        $this->assertSame('ongoing', $event->publicStatus()['key']);

        Carbon::setTestNow(Carbon::parse('2026-08-12 18:00:01', 'Europe/Belgrade'));
        $this->assertSame('finished', $event->publicStatus()['key']);
    }

    public function test_internal_statuses_are_not_returned_as_labels(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', 'Europe/Belgrade'));

        foreach (['draft', 'published', 'archived'] as $internal) {
            $status = $this->event(['status' => $internal])->publicStatus();
            $this->assertNotSame($internal, $status['label']);
            $this->assertNotSame('published', $status['key']);
            $this->assertNotSame('archived', $status['key']);
            $this->assertNotSame('Odgođen', $status['label']);
        }
    }

    #[DataProvider('unsafeDataProvider')]
    public function test_po_cr4a_05_returns_null_for_unsafe_data(array $overrides): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-10 12:00:00', 'Europe/Belgrade'));

        $this->assertNull($this->event($overrides)->publicStatus());
    }

    public static function unsafeDataProvider(): array
    {
        return [
            'datum_do before datum_od' => [[
                'datum_od' => '2026-08-12',
                'datum_do' => '2026-08-10',
            ]],
            'end time before start time' => [[
                'vrijeme' => '20:00:00',
                'vrijeme_do' => '18:00:00',
            ]],
            'end time without start time' => [[
                'vrijeme' => null,
                'vrijeme_do' => '18:00:00',
            ]],
            'invalid time format' => [[
                'vrijeme' => 'not-a-time',
            ]],
            'invalid end time format' => [[
                'vrijeme' => '18:00:00',
                'vrijeme_do' => '25:99:00',
            ]],
            'equal start and end time' => [[
                'vrijeme' => '18:00:00',
                'vrijeme_do' => '18:00:00',
            ]],
        ];
    }
}
