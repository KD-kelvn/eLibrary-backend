<?php

namespace Modules\Authorization\Tests\Unit;

use Modules\Authorization\Database\Seeders\MatrixPermissionSeeder;
use ReflectionMethod;
use Tests\TestCase;

class MatrixPermissionSeederTest extends TestCase
{
    public function test_matrix_includes_settings_library_permissions(): void
    {
        $seeder = new MatrixPermissionSeeder;
        $method = new ReflectionMethod($seeder, 'matrix');
        $method->setAccessible(true);
        /** @var array<string, list<string>> $matrix */
        $matrix = $method->invoke($seeder);

        $this->assertArrayHasKey('settings.library.view', $matrix);
        $this->assertArrayHasKey('settings.library.update', $matrix);
        $this->assertArrayHasKey('settings.library.reset', $matrix);
        $this->assertContains('admin', $matrix['settings.library.update']);
        $this->assertNotContains('librarian', $matrix['settings.library.update']);
        $this->assertContains('librarian', $matrix['settings.library.view']);
        $this->assertContains('faculty', $matrix['recommendations.create']);
        $this->assertContains('student', $matrix['catalogue.view']);
    }
}
