<?php

namespace Tests\Feature;

use App\Models\Notice;
use App\Models\Role;
use App\Models\User;
use App\Services\Notices\StandalonePdfNoticePublisher;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class StandalonePdfNoticeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
    }

    public function test_konkurs_admin_can_publish_standalone_pdf_notice(): void
    {
        $admin = $this->userWithRole('konkurs_admin');
        $title = 'Javni konkurs za raspodjelu bespovratnih sredstava namjenjenih za podršku preduzetništvu mladih u 2026.godini';

        $this->actingAs($admin)
            ->post(route('admin.notices.store'), [
                'title' => $title,
                'public_display_date' => '2026-11-17',
                'pdf' => UploadedFile::fake()->create('omladinsko.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.notices.create'));

        $notice = Notice::query()->where('title', $title)->firstOrFail();
        $this->assertSame(StandalonePdfNoticePublisher::CONTENT_DELIVERY, $notice->content_delivery);
        $this->assertTrue($notice->visible_in_active_panel);
        $this->assertTrue($notice->publicly_available);
        $this->assertSame('2026-11-17', $notice->public_display_date?->toDateString());
        Storage::disk('local')->assertExists(StandalonePdfNoticePublisher::storagePath($notice));
    }

    public function test_guest_sees_standalone_pdf_notice_on_home_and_can_open_the_file(): void
    {
        $title = 'Javni konkurs za raspodjelu bespovratnih sredstava namjenjenih za podršku preduzetništvu mladih u 2026.godini';
        $notice = app(StandalonePdfNoticePublisher::class)->publish(
            $title,
            '2026-11-17',
            UploadedFile::fake()->create('omladinsko.pdf', 200, 'application/pdf'),
        );

        $item = $this->homePanelItemHtml($title);
        $publicUrl = route('notices.public-content', $notice);

        $this->assertStringContainsString('Datum objave', $item);
        $this->assertStringContainsString('datetime="2026-11-17"', $item);
        $this->assertStringContainsString('>17.11.2026</time>', $item);
        $this->assertSame(1, substr_count($item, 'target="_blank"'));
        $this->assertSame(1, substr_count($item, 'href="'.$publicUrl.'"'));

        $response = $this->get($publicUrl);
        $response->assertOk();
        $this->assertInstanceOf(BinaryFileResponse::class, $response->baseResponse);
    }

    public function test_guest_cannot_open_admin_notice_form(): void
    {
        $this->get(route('admin.notices.create'))->assertForbidden();
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function homePanelItemHtml(string $title): string
    {
        $html = $this->get(route('home'))->assertOk()->getContent();
        $this->assertTrue(
            (bool) preg_match('/<section class="obavjestenja-panel"[\s\S]*?<\/section>/u', $html, $panel),
            'Obavještenja panel was not found on home.'
        );

        $quoted = preg_quote($title, '/');
        $this->assertTrue(
            (bool) preg_match(
                '/<li\b[^>]*>((?:(?!<\/li>).)*'.$quoted.'(?:(?!<\/li>).)*)<\/li>/us',
                $panel[0],
                $matches
            ),
            'Home panel item for ['.$title.'] was not found.'
        );

        return $matches[0];
    }
}
