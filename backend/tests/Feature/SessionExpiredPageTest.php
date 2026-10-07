<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SessionExpiredPageTest extends TestCase
{
    #[Test]
    public function admin_session_expired_redirects_directly_to_login(): void
    {
        $this->get('/admin/meld/sessie-verlopen?intended=/admin/dashboard')
            ->assertRedirect('/admin/login?intended=%2Fadmin%2Fdashboard');
    }

    #[Test]
    public function frontend_session_expired_redirects_directly_to_login(): void
    {
        $this->get('/meld/sessie-verlopen?intended=/account')
            ->assertRedirect('/login?intended=%2Faccount');
    }
}
