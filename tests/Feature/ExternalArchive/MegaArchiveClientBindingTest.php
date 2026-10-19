<?php

namespace Tests\Feature\ExternalArchive;

use App\Contracts\MegaArchiveClient;
use App\Services\ExternalArchive\ExternalFileArchiveService;
use App\Services\ExternalArchive\MegaArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MegaArchiveClientBindingTest extends TestCase
{
    use RefreshDatabase;

    public function test_mega_archive_client_resolves_to_service_singleton(): void
    {
        $first = app()->make(MegaArchiveClient::class);
        $second = app()->make(MegaArchiveClient::class);

        $this->assertInstanceOf(MegaArchiveService::class, $first);
        $this->assertSame($first, $second);
    }

    public function test_external_file_archive_service_resolves_without_binding_exception(): void
    {
        $service = app()->make(ExternalFileArchiveService::class);

        $this->assertInstanceOf(ExternalFileArchiveService::class, $service);
    }
}
