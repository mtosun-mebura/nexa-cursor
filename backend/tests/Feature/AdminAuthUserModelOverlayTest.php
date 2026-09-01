<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminAuthUserModelOverlayTest extends TestCase
{
    #[Test]
    public function admin_dashboard_does_not_five_hundred_when_session_user_model_cannot_be_loaded(): void
    {
        config(['auth.providers.users.model' => 'App\\Models\\MissingAuthUserModel']);

        $response = $this->withSession([
            Auth::guard('web')->getName() => 1,
        ])->get('/admin');

        $this->assertNotSame(500, $response->status());
        $response->assertRedirect();
    }
}
