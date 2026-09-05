<?php

namespace Tests\Feature\Identity;

use Tests\TestCase;

class Step2RuntimeIsolationTest extends TestCase
{
    /**
     * @var list<string>
     */
    private const FORBIDDEN_TOKENS = [
        'CanonicalIdentityWriter',
        'CanonicalIdentityReader',
        'IdentityRollout',
        'IdentitySnapshot',
        'LegacyIdentityAdapter',
        'CanonicalIdentityWriteException',
        'CanonicalIdentityReadException',
    ];

    /**
     * @var list<string>
     */
    private const RUNTIME_ROOTS = [
        'app/Http',
        'app/Services',
        'app/Support',
        'routes',
    ];

    public function test_step_2_capability_classes_are_not_wired_into_runtime(): void
    {
        foreach ($this->runtimePhpFiles() as $relative => $path) {
            $contents = (string) file_get_contents($path);
            foreach (self::FORBIDDEN_TOKENS as $token) {
                $this->assertStringNotContainsString(
                    $token,
                    $contents,
                    $relative.' must not reference '.$token
                );
            }
        }
    }

    /**
     * @return array<string, string>
     */
    private function runtimePhpFiles(): array
    {
        $files = [];

        foreach (self::RUNTIME_ROOTS as $root) {
            $absoluteRoot = base_path($root);
            if (! is_dir($absoluteRoot)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($absoluteRoot, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $absolute = $file->getPathname();
                $relative = str_replace('\\', '/', substr($absolute, strlen(base_path()) + 1));
                $files[$relative] = $absolute;
            }
        }

        $this->assertNotEmpty($files);

        return $files;
    }
}
