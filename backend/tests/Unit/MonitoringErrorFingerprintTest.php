<?php

namespace Tests\Unit;

use App\Models\ErrorLog;
use App\Services\Monitoring\MonitoringBulkResolver;
use App\Services\Monitoring\MonitoringErrorFingerprint;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MonitoringErrorFingerprintTest extends TestCase
{
    #[Test]
    public function ids_numbers_paths_and_quoted_values_do_not_change_the_fingerprint(): void
    {
        $fingerprints = new MonitoringErrorFingerprint;
        $a = new ErrorLog(['source' => 'exception', 'message' => "Ride 12 failed for 'a@b.nl' in /var/www/app/Foo.php on uuid 3f2b8c1e-1111-2222-3333-444455556666", 'context' => ['exception' => 'RuntimeException']]);
        $b = new ErrorLog(['source' => 'exception', 'message' => "Ride 9876 failed for 'x@y.nl' in /srv/other/Bar.php on uuid aaaaaaaa-1111-2222-3333-444455556666", 'context' => ['exception' => 'RuntimeException']]);
        $other = new ErrorLog(['source' => 'queue', 'message' => $a->message, 'context' => ['exception' => 'RuntimeException']]);

        $this->assertSame($fingerprints->for($a), $fingerprints->for($b));
        $this->assertNotSame($fingerprints->for($a), $fingerprints->for($other));
    }

    #[Test]
    public function resolver_stops_at_the_time_budget_and_reports_the_remaining_rows(): void
    {
        $logs = collect([
            new ErrorLog(['source' => 'x', 'message' => 'een', 'resolved' => false]),
            new ErrorLog(['source' => 'x', 'message' => 'twee', 'resolved' => false]),
        ]);

        $summary = app(MonitoringBulkResolver::class)->resolve($logs, -1);

        $this->assertSame(2, $summary['remaining']);
        $this->assertSame(0, $summary['resolved'] + $summary['manual'] + $summary['failed']);
    }
}
