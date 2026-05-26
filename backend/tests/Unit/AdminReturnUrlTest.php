<?php

namespace Tests\Unit;

use App\Support\AdminReturnUrl;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminReturnUrlTest extends TestCase
{
    #[Test]
    public function sanitize_allows_admin_paths_only(): void
    {
        $this->assertSame(
            '/admin/payments/voldaan?company_id=2',
            AdminReturnUrl::sanitize('/admin/payments/voldaan?company_id=2')
        );
        $this->assertNull(AdminReturnUrl::sanitize('https://evil.test/admin'));
        $this->assertNull(AdminReturnUrl::sanitize('/public/page'));
    }

    #[Test]
    public function append_return_param_adds_encoded_query(): void
    {
        $url = AdminReturnUrl::appendReturnParam(
            'http://localhost/admin/invoices/1',
            '/admin/payments/voldaan?page=2'
        );

        $this->assertStringContainsString('return=', $url);
        $this->assertStringContainsString(urlencode('/admin/payments/voldaan?page=2'), $url);
    }
}
