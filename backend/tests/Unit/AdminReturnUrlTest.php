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

    #[Test]
    public function resolve_intended_rejects_login_and_meld_paths(): void
    {
        $this->assertNull(AdminReturnUrl::resolveIntended('/admin/login'));
        $this->assertNull(AdminReturnUrl::resolveIntended('http://localhost:8085/admin/login'));
        $this->assertNull(AdminReturnUrl::resolveIntended('/admin/meld/sessie-verlopen'));
        $this->assertSame(
            'http://localhost:8085/admin/website-pages/1/edit',
            AdminReturnUrl::resolveIntended('http://localhost:8085/admin/website-pages/1/edit')
        );
        $this->assertSame(
            '/admin/website-pages/1/edit',
            AdminReturnUrl::resolveIntended('/admin/website-pages/1/edit')
        );
    }

    #[Test]
    public function login_url_with_intended_omits_blocked_destination(): void
    {
        $this->assertSame('/admin/login', AdminReturnUrl::loginUrlWithIntended('/admin/login'));
        $this->assertSame(
            '/admin/login?intended=%2Fadmin%2Fwebsite-pages%2F1%2Fedit',
            AdminReturnUrl::loginUrlWithIntended('/admin/website-pages/1/edit')
        );
    }
}
