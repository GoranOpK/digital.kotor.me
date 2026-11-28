<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Competition;
use App\Models\Role;
use App\Models\User;
use App\Services\Competitions\Profile\CompetitionProfileResolver;
use App\Support\CompetitionProgramCatalog;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MmspUserGuidePdfTest extends TestCase
{
    use RefreshDatabase;

    private const MMSP_SHA256 = '7cf80ca7b041d8cd175dd43bab7563d22e4b42a35980d71c685353dbba5fdd52';

    private const MMSP_BYTES = 295956;

    private const YOUTH_SHA256 = '37e38a52e39f3233e5fdb3a7cca5d3cd759537e607433669bcbea24e4a5006b1';

    private const WOMEN_SHA256 = '619c9d9021dea918cef6369f6fba26dcc2ae1f517060faed374f00fd741a7426';

    private const MODERATOR_SHA256 = '41e365983c09003feed2d52bb64cd5b37b6073a1d8c26f44a1b31b69f3292095';

    protected function setUp(): void
    {
        parent::setUp();
        CompetitionProgramCatalog::clearTestOverrides();
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        CompetitionProgramCatalog::clearTestOverrides();
        parent::tearDown();
    }

    public function test_approved_mmsp_pdf_is_served_unchanged_with_the_same_access_as_youth(): void
    {
        $path = public_path('pdf/uputstvo-mmsp-preduzetnistvo.pdf');
        $this->assertFileExists($path);
        $this->assertSame(self::MMSP_BYTES, filesize($path));
        $this->assertSame(self::MMSP_SHA256, hash_file('sha256', $path));

        $route = route('competitions.mmsp-guide.pdf');
        $this->get($route)->assertRedirect(route('login'));

        $unverified = $this->userWithRole('korisnik');
        $unverified->forceFill(['email_verified_at' => null])->save();
        $this->actingAs($unverified)->get($route)->assertRedirect(route('verification.notice'));

        $response = $this->actingAs($this->userWithRole('korisnik'))->get($route)->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="uputstvo-mmsp-preduzetnistvo.pdf"');
        $file = $response->baseResponse->getFile();
        $this->assertSame(realpath($path), $file->getRealPath());
        $this->assertSame(self::MMSP_SHA256, hash_file('sha256', $file->getRealPath()));
        $this->assertSame(self::MMSP_BYTES, filesize($file->getRealPath()));

        $routes = Route::getRoutes();
        $this->assertSame(
            $routes->getByName('competitions.youth-guide.pdf')->gatherMiddleware(),
            $routes->getByName('competitions.mmsp-guide.pdf')->gatherMiddleware()
        );
    }

    public function test_mmsp_guide_button_is_only_on_the_mmsp_list_and_matches_the_youth_button(): void
    {
        $user = $this->userWithRole('korisnik');
        $mmsp = $this->competition(CompetitionProfileResolver::TYPE_CANONICAL, 'MMSP javni poziv');
        $this->assertSame(0, Application::query()->where('user_id', $user->id)->count());

        $hub = $this->actingAs($user)->get(route('competitions.index'))->assertOk()->getContent();
        $this->assertStringNotContainsString('Preuzmi uputstvo (PDF)', $hub);
        $this->assertStringNotContainsString(route('competitions.mmsp-guide.pdf'), $hub);
        $this->assertStringNotContainsString(route('competitions.youth-guide.pdf'), $hub);
        $this->assertStringNotContainsString(route('competitions.guide.pdf'), $hub);
        $this->assertStringContainsString('page-header is-hub', $hub);

        $mmspHtml = $this->actingAs($user)
            ->get(route('competitions.index', ['type' => CompetitionProfileResolver::TYPE_CANONICAL]))
            ->assertOk()
            ->assertSee($mmsp->title)
            ->getContent();
        $mmspButton = $this->guideButton($mmspHtml, 'competitions.mmsp-guide.pdf');

        $youthHtml = $this->actingAs($user)
            ->get(route('competitions.index', ['type' => 'omladinsko']))
            ->assertOk()
            ->getContent();
        $youthButton = $this->guideButton($youthHtml, 'competitions.youth-guide.pdf');
        $this->assertStringNotContainsString(route('competitions.mmsp-guide.pdf'), $youthHtml);
        $this->assertStringNotContainsString(route('competitions.guide.pdf'), $youthHtml);

        $womenHtml = $this->actingAs($user)
            ->get(route('competitions.index', ['type' => 'zensko']))
            ->assertOk()
            ->getContent();
        $womenButton = $this->guideButton($womenHtml, 'competitions.guide.pdf');
        $this->assertStringNotContainsString(route('competitions.mmsp-guide.pdf'), $womenHtml);
        $this->assertStringNotContainsString(route('competitions.youth-guide.pdf'), $womenHtml);

        $this->assertSame($youthButton['class'], $mmspButton['class']);
        $this->assertSame($youthButton['target'], $mmspButton['target']);
        $this->assertSame('_blank', $mmspButton['target']);
        $this->assertSame($youthButton['rel'], $mmspButton['rel']);
        $this->assertStringContainsString('noopener', $mmspButton['rel']);
        $this->assertStringContainsString('noreferrer', $mmspButton['rel']);
        $this->assertSame($youthButton['text'], $mmspButton['text']);
        $this->assertSame('Preuzmi uputstvo (PDF)', $mmspButton['text']);
        $this->assertSame($womenButton['class'], $mmspButton['class']);
        $this->assertSame($womenButton['text'], $mmspButton['text']);

        $show = $this->actingAs($user)->get(route('competitions.show', $mmsp))->assertOk()
            ->assertSee($mmsp->title)
            ->getContent();
        $this->assertStringNotContainsString(route('competitions.mmsp-guide.pdf'), $show);
        $this->assertStringNotContainsString('Preuzmi uputstvo (PDF)', $show);
        $this->assertSame(0, Application::query()->where('user_id', $user->id)->count());
    }

    public function test_existing_youth_and_women_pdfs_stay_on_their_routes(): void
    {
        $user = $this->userWithRole('korisnik');

        $youth = $this->actingAs($user)->get(route('competitions.youth-guide.pdf'))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="uputstvo-omladinsko-preduzetnistvo.pdf"');
        $this->assertSame(self::YOUTH_SHA256, hash_file('sha256', $youth->baseResponse->getFile()->getRealPath()));

        $women = $this->actingAs($user)->get(route('competitions.guide.pdf'))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="uputstvo-zensko-preduzetnistvo.pdf"');
        $this->assertSame(self::WOMEN_SHA256, hash_file('sha256', $women->baseResponse->getFile()->getRealPath()));

        $this->assertSame(self::MODERATOR_SHA256, hash_file('sha256', public_path('pdf/Moderator uputstvo.pdf')));
        $this->assertSame(self::MMSP_SHA256, hash_file('sha256', public_path('pdf/uputstvo-mmsp-preduzetnistvo.pdf')));
    }

    /**
     * @return array{class: string, target: string, rel: string, text: string}
     */
    private function guideButton(string $html, string $routeName): array
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($dom);
        $href = '[@href="'.route($routeName).'"]';
        $header = '//div[contains(concat(" ",normalize-space(@class)," ")," page-header ")]';
        $button = $xpath->query($header.'/following-sibling::*[1][@class="program-guide-actions"]/a'.$href.'[contains(concat(" ",normalize-space(@class)," ")," page-header-guide-btn ")]');
        $this->assertSame(1, $button->length);
        $node = $button->item(0);

        return [
            'class' => $node->getAttribute('class'),
            'target' => $node->getAttribute('target'),
            'rel' => $node->getAttribute('rel'),
            'text' => trim($node->textContent),
        ];
    }

    private function userWithRole(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'activation_status' => 'active',
            'email_verified_at' => now(),
        ]);
    }

    private function competition(string $type, string $title): Competition
    {
        return Competition::create([
            'title' => $title,
            'description' => 'Opis javnog poziva',
            'type' => $type,
            'status' => 'published',
            'year' => (int) now()->year,
            'budget' => 20000,
            'deadline_days' => 20,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(19)->toDateString(),
            'published_at' => now()->subDay(),
        ]);
    }
}
