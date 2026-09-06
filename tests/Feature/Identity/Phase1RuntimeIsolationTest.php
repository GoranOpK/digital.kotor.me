<?php

namespace Tests\Feature\Identity;

use Tests\TestCase;

class Phase1RuntimeIsolationTest extends TestCase
{
    public function test_canonical_authority_flags_remain_off_by_default(): void
    {
        $this->assertSame(false, config('identity.canonical_read'));
        $this->assertSame(false, config('identity.canonical_write'));
    }
}
