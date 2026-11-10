<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\DurableUserCredentials;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DurableUserCredentialsTest extends TestCase
{
    #[Test]
    public function ensure_by_email_creates_missing_user(): void
    {
        $user = app(DurableUserCredentials::class)->ensureByEmail(
            'new-user@example.test',
            [
                'password' => Hash::make('InitialPass1!'),
                'first_name' => 'New',
                'last_name' => 'User',
                'email_verified_at' => now(),
            ]
        );

        $this->assertTrue($user->exists);
        $this->assertSame('new-user@example.test', $user->email);
        $this->assertTrue(Hash::check('InitialPass1!', $user->password));
    }

    #[Test]
    public function ensure_by_email_never_overwrites_existing_password_or_name(): void
    {
        $existing = User::factory()->create([
            'email' => 'keep@example.test',
            'password' => 'KeepMyPass1!',
            'first_name' => 'Original',
            'last_name' => 'Name',
        ]);
        $originalHash = $existing->getRawOriginal('password');

        $returned = app(DurableUserCredentials::class)->ensureByEmail(
            'keep@example.test',
            [
                'password' => Hash::make('ShouldNeverApply1!'),
                'first_name' => 'Hacker',
                'last_name' => 'Attempt',
                'email_verified_at' => now(),
            ]
        );

        $this->assertTrue($returned->is($existing));
        $this->assertSame($originalHash, $returned->fresh()->getRawOriginal('password'));
        $this->assertTrue(Hash::check('KeepMyPass1!', $returned->fresh()->password));
        $this->assertSame('Original', $returned->fresh()->first_name);
        $this->assertSame('Name', $returned->fresh()->last_name);
    }

    #[Test]
    public function attributes_for_create_only_strips_password_on_existing_users(): void
    {
        $user = User::factory()->create(['password' => 'KeepMyPass1!']);
        $service = app(DurableUserCredentials::class);

        $attrs = $service->attributesForCreateOnly($user, [
            'first_name' => 'X',
            'password' => 'NewPass1!',
        ], 'NewPass1!');

        $this->assertArrayNotHasKey('password', $attrs);
        $this->assertSame('X', $attrs['first_name']);
    }
}
