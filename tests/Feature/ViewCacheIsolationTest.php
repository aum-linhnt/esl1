<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class ViewCacheIsolationTest extends TestCase
{
    public function test_blade_compilation_uses_private_temporary_cache_instead_of_live_views(): void
    {
        $directory = config('view.compiled');
        $this->assertStringStartsWith(sys_get_temp_dir().'/esl1-phpunit-views-', $directory);
        $this->assertNotSame(storage_path('framework/views'), $directory);
        $this->assertSame('Hello learner', trim(Blade::render('Hello {{ $name }}', ['name' => 'learner'])));
        $this->assertNotEmpty(glob($directory.'/*.php'));
        $this->assertSame(0700, fileperms($directory) & 0777);
    }
}
