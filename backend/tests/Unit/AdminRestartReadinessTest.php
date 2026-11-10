<?php

namespace Tests\Unit;

use App\Support\AdminRestartReadiness;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminRestartReadinessTest extends TestCase
{
    #[Test]
    public function a_login_redirect_or_ok_page_means_the_admin_is_up(): void
    {
        $this->assertTrue(AdminRestartReadiness::isUp(200));
        $this->assertTrue(AdminRestartReadiness::isUp(302));
        $this->assertTrue(AdminRestartReadiness::isUp(401));
        $this->assertTrue(AdminRestartReadiness::isUp(419));
        $this->assertTrue(AdminRestartReadiness::isUp(0, true));
    }

    #[Test]
    public function connection_refused_and_server_errors_stay_down(): void
    {
        $this->assertFalse(AdminRestartReadiness::isUp(null));
        $this->assertFalse(AdminRestartReadiness::isUp(0));
        $this->assertFalse(AdminRestartReadiness::isUp(500));
        $this->assertFalse(AdminRestartReadiness::isUp(503));
        $this->assertFalse(AdminRestartReadiness::isUp(404));
    }

    #[Test]
    #[DataProvider('statuses')]
    public function the_browser_list_matches_the_decision(int $status, bool $up): void
    {
        $this->assertSame($up, in_array($status, AdminRestartReadiness::upStatuses(), true));
        $this->assertSame($up, AdminRestartReadiness::isUp($status));
    }

    /**
     * @return array<string, array{0: int, 1: bool}>
     */
    public static function statuses(): array
    {
        return [
            'ok' => [200, true],
            'login redirect' => [302, true],
            'json login' => [401, true],
            'expired csrf' => [419, true],
            'server error' => [500, false],
        ];
    }
}
