<?php

namespace App\Services\CulturalMedia;

final class CulturalMediaCleanupReport
{
    public int $dbReferences = 0;

    public int $physicalFiles = 0;

    /** @var list<string> */
    public array $filesystemOrphans = [];

    /** @var list<string> */
    public array $missingFileRows = [];

    /** @var list<string> */
    public array $suspiciousDbPaths = [];

    public bool $applied = false;

    /** @var list<string> */
    public array $deleted = [];

    /** @var list<string> */
    public array $deleteFailures = [];
}
