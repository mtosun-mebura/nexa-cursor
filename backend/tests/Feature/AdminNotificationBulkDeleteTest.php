<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Notification;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminNotificationBulkDeleteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Role::query()->firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'company-admin', 'guard_name' => 'web']);
        Permission::query()->firstOrCreate(['name' => 'delete-notifications', 'guard_name' => 'web']);
        Permission::query()->firstOrCreate(['name' => 'view-notifications', 'guard_name' => 'web']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
    }

    #[Test]
    public function super_admin_can_bulk_delete_notifications(): void
    {
        $company = $this->company();
        $admin = $this->superAdmin();
        $recipient = User::factory()->create(['company_id' => $company->id]);
        $first = $this->notification($recipient, $company, 'Eerste');
        $second = $this->notification($recipient, $company, 'Tweede');
        $kept = $this->notification($recipient, $company, 'Blijft');

        $this->actingAs($admin)
            ->delete(route('admin.notifications.bulk-destroy'), [
                'ids' => [$first->id, $second->id],
            ])
            ->assertRedirect(route('admin.notifications.index'));

        $this->assertDatabaseMissing('notifications', ['id' => $first->id]);
        $this->assertDatabaseMissing('notifications', ['id' => $second->id]);
        $this->assertDatabaseHas('notifications', ['id' => $kept->id]);
    }

    #[Test]
    public function index_shows_multiselect_for_super_admin(): void
    {
        $company = $this->company();
        $admin = $this->superAdmin();
        $recipient = User::factory()->create(['company_id' => $company->id]);
        $this->notification($recipient, $company, 'Zichtbaar');

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('notification-row-checkbox', false)
            ->assertSee('notifications-bulk-delete', false)
            ->assertSee('Geselecteerde notificaties verwijderen', false);
    }

    #[Test]
    public function super_admin_with_tenant_cannot_bulk_delete_other_tenant_notifications(): void
    {
        $company = $this->company('Royaal Test');
        $other = $this->company('Andere Test');
        $admin = $this->superAdmin();
        $recipient = User::factory()->create(['company_id' => $other->id]);
        $foreign = $this->notification($recipient, $other, 'Andere tenant');

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->delete(route('admin.notifications.bulk-destroy'), [
                'ids' => [$foreign->id],
            ])
            ->assertRedirect(route('admin.notifications.index'));

        $this->assertDatabaseHas('notifications', ['id' => $foreign->id]);
    }

    #[Test]
    public function show_page_has_delete_button_for_super_admin(): void
    {
        $company = $this->company();
        $admin = $this->superAdmin();
        $recipient = User::factory()->create(['company_id' => $company->id]);
        $notification = $this->notification($recipient, $company, 'Te verwijderen');

        $this->actingAs($admin)
            ->withSession(['selected_tenant' => $company->id])
            ->get(route('admin.notifications.show', $notification))
            ->assertOk()
            ->assertSee('notification-delete-form', false)
            ->assertSee('notification-delete-open', false)
            ->assertSee('notifications-delete-modal', false)
            ->assertSee('Verwijderen')
            ->assertSee(route('admin.notifications.destroy', $notification), false);
    }

    #[Test]
    public function company_admin_without_delete_permission_cannot_bulk_delete(): void
    {
        $company = $this->company();
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('company-admin');
        $notification = $this->notification($user, $company, 'Beschermd');

        $this->actingAs($user)
            ->delete(route('admin.notifications.bulk-destroy'), [
                'ids' => [$notification->id],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
    }

    private function superAdmin(): User
    {
        $user = User::factory()->create(['company_id' => null]);
        $user->assignRole('super-admin');

        return $user;
    }

    private function company(string $name = 'Notificatie Test'): Company
    {
        return Company::query()->create([
            'name' => $name.' '.uniqid(),
            'is_active' => true,
        ]);
    }

    private function notification(User $user, Company $company, string $title): Notification
    {
        return Notification::query()->create([
            'user_id' => $user->id,
            'company_id' => $company->id,
            'type' => 'info',
            'title' => $title,
            'message' => $title.' bericht',
            'priority' => 'medium',
        ]);
    }
}
