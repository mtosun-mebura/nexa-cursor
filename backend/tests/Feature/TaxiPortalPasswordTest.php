<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TaxiPortalPasswordTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function taxi_portal_password_can_be_changed_with_valid_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'OldPass1!',
        ]);

        $response = $this->withoutMiddleware()
            ->actingAs($user)
            ->putJson(route('taxi.portal.api.profile.password'), [
                'current_password' => 'OldPass1!',
                'password' => 'NewPass2!',
                'password_confirmation' => 'NewPass2!',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Wachtwoord succesvol gewijzigd.',
            ]);

        $user->refresh();
        $this->assertTrue(Hash::check('NewPass2!', $user->password));
    }

    #[Test]
    public function taxi_portal_password_change_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'password' => 'OldPass1!',
        ]);

        $response = $this->withoutMiddleware()
            ->actingAs($user)
            ->putJson(route('taxi.portal.api.profile.password'), [
                'current_password' => 'WrongPass1!',
                'password' => 'NewPass2!',
                'password_confirmation' => 'NewPass2!',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    }
}
