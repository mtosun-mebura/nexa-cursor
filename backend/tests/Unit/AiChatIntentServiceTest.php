<?php

namespace Tests\Unit;

use App\DTO\AiChat\AiChatRequestContext;
use App\Enums\AiChat\AiChatChannel;
use App\Enums\AiChat\AiChatIntent;
use App\Models\User;
use App\Services\AiChat\AiChatAccessService;
use App\Services\AiChat\AiChatIntentService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AiChatIntentServiceTest extends TestCase
{
    public function test_public_user_gets_faq_intent_for_service_question(): void
    {
        $service = new AiChatIntentService(new AiChatAccessService());
        $context = new AiChatRequestContext(companyId: 1, channel: AiChatChannel::Public);

        $result = $service->classify('Hebben jullie luchthavenvervoer?', $context);

        $this->assertSame(AiChatIntent::Faq, $result->intent);
        $this->assertFalse($result->isAdmin);
        $this->assertFalse($result->allowLiveData);
    }

    public function test_public_user_gets_tarieven_intent_with_public_rates_access(): void
    {
        $service = new AiChatIntentService(new AiChatAccessService());
        $context = new AiChatRequestContext(companyId: 1, channel: AiChatChannel::Public);

        $result = $service->classify('Wat zijn jullie tarieven?', $context);

        $this->assertSame(AiChatIntent::Tarieven, $result->intent);
        $this->assertFalse($result->allowLiveData);
        $this->assertTrue($result->allowPublicRates);
    }

    public function test_public_user_live_question_is_classified_but_not_allowed(): void
    {
        $service = new AiChatIntentService(new AiChatAccessService());
        $context = new AiChatRequestContext(companyId: 1, channel: AiChatChannel::Public);

        $result = $service->classify('Welke ritten staan morgen gepland?', $context);

        $this->assertSame(AiChatIntent::RittenMorgen, $result->intent);
        $this->assertFalse($result->allowLiveData);
    }

    public function test_admin_user_may_query_live_rides(): void
    {
        Permission::create(['name' => 'rides.view', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo('rides.view');

        $service = new AiChatIntentService(new AiChatAccessService());
        $context = new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Admin,
            userId: $user->id,
            user: $user,
        );

        $result = $service->classify('Welke ritten staan morgen gepland?', $context);

        $this->assertSame(AiChatIntent::RittenMorgen, $result->intent);
        $this->assertTrue($result->isAdmin);
        $this->assertTrue($result->allowLiveData);
    }
}
