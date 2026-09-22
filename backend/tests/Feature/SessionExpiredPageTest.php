<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SessionExpiredPageTest extends TestCase
{
    #[Test]
    public function admin_session_expired_page_renders_compact_theme_card(): void
    {
        $this->get('/admin/meld/sessie-verlopen')
            ->assertOk()
            ->assertSee('redirect-card', false)
            ->assertSee('redirect-page-shell', false)
            ->assertSee('Uw sessie is verlopen. Log opnieuw in om verder te gaan.', false)
            ->assertSee('Naar inlogpagina', false)
            ->assertDontSee('w-full max-w-md', false)
            ->assertDontSee('bg-white dark:bg-gray-800', false);
    }

    #[Test]
    public function frontend_session_expired_page_renders_compact_theme_card(): void
    {
        $this->get('/meld/sessie-verlopen')
            ->assertOk()
            ->assertSee('redirect-card', false)
            ->assertSee('Uw sessie is verlopen. Log opnieuw in om verder te gaan.', false)
            ->assertSee('Naar inlogpagina', false);
    }
}
