<?php

namespace Tests\Unit;

use App\Services\SystemStackSnapshotService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SystemStackSnapshotTest extends TestCase
{
    #[Test]
    public function capture_includes_core_stack_keys(): void
    {
        $service = app(SystemStackSnapshotService::class);
        $stack = $service->capture();

        $this->assertArrayHasKey('nexa_release', $stack);
        $this->assertArrayHasKey('php', $stack);
        $this->assertArrayHasKey('laravel', $stack);
        $this->assertSame(PHP_VERSION, $stack['php']);
    }

    #[Test]
    public function bump_release_patch_increments_patch_segment(): void
    {
        $service = app(SystemStackSnapshotService::class);

        $this->assertSame('1.0.1', $service->bumpReleasePatch('1.0.0'));
        $this->assertSame('2.4.10', $service->bumpReleasePatch('2.4.9'));
    }

    #[Test]
    public function labeled_stack_returns_human_labels(): void
    {
        $service = app(SystemStackSnapshotService::class);
        $rows = $service->labeledStack(['php' => '8.3.0', 'laravel' => '12.0.0']);

        $php = collect($rows)->firstWhere('key', 'php');
        $this->assertNotNull($php);
        $this->assertSame('PHP', $php['label']);
        $this->assertSame('8.3.0', $php['value']);
    }
}
