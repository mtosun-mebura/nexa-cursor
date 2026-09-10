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
}
