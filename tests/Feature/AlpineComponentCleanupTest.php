<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * UI-CLEANUP-HF1 — unused Breeze Alpine scaffolding removed.
 */
class AlpineComponentCleanupTest extends TestCase
{
    public function test_unused_alpine_breeze_components_are_removed(): void
    {
        $this->assertFileDoesNotExist(resource_path('views/components/modal.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/components/dropdown.blade.php'));
        $this->assertFileDoesNotExist(resource_path('views/components/dropdown-link.blade.php'));
    }

    public function test_active_views_have_no_alpine_directives(): void
    {
        $paths = [
            resource_path('views/layouts/navigation.blade.php'),
            resource_path('views/profile/edit.blade.php'),
            resource_path('views/profile/partials/delete-user-form.blade.php'),
            resource_path('views/profile/partials/update-profile-information-form.blade.php'),
            resource_path('views/profile/partials/update-password-form.blade.php'),
        ];

        foreach ($paths as $path) {
            $source = file_get_contents($path);
            $this->assertNotFalse($source, $path);
            $this->assertStringNotContainsString('x-data', $source, $path);
            $this->assertStringNotContainsString('x-show', $source, $path);
            $this->assertStringNotContainsString('x-transition', $source, $path);
            $this->assertStringNotContainsString('x-on:', $source, $path);
            $this->assertStringNotContainsString('$dispatch', $source, $path);
            $this->assertStringNotContainsString('Alpine', $source, $path);
        }
    }
}
