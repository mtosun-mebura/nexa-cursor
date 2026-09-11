<?php

namespace Tests\Unit;

use App\Services\SystemUpgradeService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SystemUpgradeTestRunnerTest extends TestCase
{
    #[Test]
    public function it_detects_unknown_cli_options_from_phpunit_and_symfony_output(): void
    {
        $service = app(SystemUpgradeService::class);

        $this->assertSame(
            '--without-tty',
            $service->unknownCliOptionFromOutput(
                "PHPUnit 11.5.56 by Sebastian Bergmann and contributors.\n\nUnknown option \"--without-tty\"\n"
            )
        );
        $this->assertSame(
            '--colors',
            $service->unknownCliOptionFromOutput('The "--colors" option does not exist.')
        );
        $this->assertSame(
            '--compact',
            $service->unknownCliOptionFromOutput("unrecognized option '--compact'")
        );
        $this->assertNull($service->unknownCliOptionFromOutput('Failed asserting that true is false.'));
    }

    #[Test]
    public function it_strips_unknown_flags_including_equals_form(): void
    {
        $service = app(SystemUpgradeService::class);

        $this->assertSame(
            ['php', 'phpunit', '--colors=never'],
            $service->commandWithoutOption(['php', 'phpunit', '--without-tty', '--colors=never'], '--without-tty')
        );
        $this->assertSame(
            ['php', 'phpunit'],
            $service->commandWithoutOption(['php', 'phpunit', '--colors=never'], '--colors')
        );
    }

    #[Test]
    public function unknown_phpunit_option_is_dropped_and_the_command_is_retried(): void
    {
        $script = sys_get_temp_dir().'/nexa-upgrade-opt-'.uniqid('', true).'.php';
        file_put_contents($script, <<<'PHP'
<?php
if (in_array('--without-tty', $argv, true)) {
    fwrite(STDERR, "PHPUnit 11.5.56 by Sebastian Bergmann and contributors.\n\nUnknown option \"--without-tty\"\n");
    exit(2);
}
exit(0);
PHP);

        try {
            $steps = [];
            app(SystemUpgradeService::class)->runProcessCommandWithUnknownOptionRetry(
                null,
                $steps,
                'Stabiliteitstests uitvoeren',
                [PHP_BINARY, $script, '--without-tty', '--colors=never'],
                15,
            );

            $labels = array_column($steps, 'label');
            $this->assertContains('Stabiliteitstests uitvoeren voltooid', $labels);
        } finally {
            @unlink($script);
        }
    }

    #[Test]
    public function it_compacts_composer_version_spam_in_failure_output(): void
    {
        $output = <<<'TXT'
Your requirements could not be resolved to an installable set of packages.

  Problem 1
    - laravel/tinker is locked to version v2.11.1 and an update of this package was not requested.
    - Conclusion: don't install laravel/framework v13.1.0 (conflict analysis result)
    - Conclusion: don't install laravel/framework v13.31.0 (conflict analysis result)
TXT;

        $compacted = app(SystemUpgradeService::class)->compactComposerFailureOutput($output);

        $this->assertStringContainsString('laravel/tinker is locked to version v2.11.1', $compacted);
        $this->assertStringNotContainsString("don't install laravel/framework v13.1.0", $compacted);
        $this->assertStringContainsString('2 verdere Composer-versieconflicten weggelaten', $compacted);
    }

    #[Test]
    public function it_surfaces_composer_script_failures_at_the_top(): void
    {
        $output = <<<'TXT'
Loading composer repositories with package information
Updating dependencies
Lock file operations: 0 installs, 24 updates, 2 removals
Script @php artisan config:clear handling the post-autoload-dump event returned with error code 1
TXT;

        $compacted = app(SystemUpgradeService::class)->compactComposerFailureOutput($output);

        $this->assertStringStartsWith('Script @php artisan config:clear', $compacted);
    }

    #[Test]
    public function it_surfaces_phpunit_failure_names_instead_of_html_dumps(): void
    {
        $html = str_repeat('<div class="kt-card">'.str_repeat('x', 80).'</div>'."\n", 80);
        $output = <<<TXT
PHPUnit 12.5.35 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.5.10
Configuration: /var/www/html/phpunit.xml

..............................FF.............................  488 / 1280 ( 38%)

There were 2 failures:

1) Tests\\Feature\\AdminAiImagesPageTest::delete_confirm_modal_is_a_centered_overlay_with_blur
Failed asserting that '<!DOCTYPE html>
{$html}' contains "id=\"ai-image-confirm\"".

2) Tests\\Unit\\SystemLaravelPackageCompatibilityTest::current_lock_marks_tinker_incompatible_with_laravel_13
Failed asserting that an array has the key 'laravel/tinker'.

FAILURES!
Tests: 1280, Assertions: 5388, Failures: 2.
TXT;

        $compacted = app(SystemUpgradeService::class)->compactPhpunitFailureOutput($output);

        $this->assertStringContainsString('Mislukte tests:', $compacted);
        $this->assertStringContainsString('AdminAiImagesPageTest::delete_confirm_modal_is_a_centered_overlay_with_blur', $compacted);
        $this->assertStringContainsString('current_lock_marks_tinker_incompatible_with_laravel_13', $compacted);
        $this->assertStringContainsString('assertiedump ingekort', $compacted);
        $this->assertStringNotContainsString('488 / 1280', $compacted);
        $this->assertLessThan(2500, strlen($compacted));
    }
}
