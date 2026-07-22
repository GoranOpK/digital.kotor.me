<?php

namespace Tests\Unit;

use Tests\TestCase;

class QueueWorkerScriptTest extends TestCase
{
    public function test_queue_worker_script_has_supported_long_running_options_and_lock(): void
    {
        $path = base_path('queue-worker.php');
        $this->assertFileExists($path);

        $contents = file_get_contents($path);
        $this->assertIsString($contents);

        $this->assertStringContainsString("'--sleep' => 1", $contents);
        $this->assertStringContainsString("'--tries' => 3", $contents);
        $this->assertStringContainsString("'--timeout' => 300", $contents);
        $this->assertStringContainsString("'--max-time' => 55", $contents);
        $this->assertStringNotContainsString('stop-when-empty', $contents);
        $this->assertStringContainsString('flock(', $contents);
        $this->assertStringContainsString('LOCK_EX | LOCK_NB', $contents);
        $this->assertStringContainsString('storage/framework', $contents);
        $this->assertStringContainsString('queue-worker.lock', $contents);
        $this->assertStringContainsString('could not open lock file', $contents);
        $this->assertMatchesRegularExpression('/could not open lock file[\s\S]*?exit\(1\)/', $contents);
        $this->assertMatchesRegularExpression('/LOCK_EX \| LOCK_NB\)[\s\S]*?exit\(0\)/', $contents);
    }
}
