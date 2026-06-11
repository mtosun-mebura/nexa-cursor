<?php

namespace Tests\Unit;

use App\DTO\AiChat\AiChatRequestContext;
use App\Enums\AiChat\AiChatChannel;
use App\Enums\AiChat\AiChatIntent;
use App\Models\User;
use App\Services\AiChat\AiChatAccessService;
use App\Services\AiChat\AiChatIntentDetector;
use App\Services\AiChat\AiChatIntentService;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AiChatIntentServiceTest extends TestCase
{
    private function intentService(): AiChatIntentService
    {
        return new AiChatIntentService(new AiChatAccessService(), new AiChatIntentDetector());
    }

    public function test_public_user_gets_diensten_intent_for_service_question(): void
    {
        $service = $this->intentService();
        $context = new AiChatRequestContext(companyId: 1, channel: AiChatChannel::Public);

        $result = $service->classify('Hebben jullie luchthavenvervoer?', $context);

        $this->assertSame(AiChatIntent::Diensten, $result->intent);
        $this->assertFalse($result->isAdmin);
        $this->assertFalse($result->allowLiveData);
    }

    public function test_public_user_gets_tarieven_intent_with_public_rates_access(): void
    {
        $service = $this->intentService();
        $context = new AiChatRequestContext(companyId: 1, channel: AiChatChannel::Public);

        $result = $service->classify('Wat zijn jullie tarieven?', $context);

        $this->assertSame(AiChatIntent::Tarieven, $result->intent);
        $this->assertFalse($result->allowLiveData);
        $this->assertTrue($result->allowPublicRates);
    }

    public function test_public_user_live_question_is_classified_but_not_allowed(): void
    {
        $service = $this->intentService();
        $context = new AiChatRequestContext(companyId: 1, channel: AiChatChannel::Public);

        $result = $service->classify('Welke ritten staan morgen gepland?', $context);

        $this->assertSame(AiChatIntent::RittenMorgen, $result->intent);
        $this->assertFalse($result->allowLiveData);
    }

    public function test_generic_ritten_question_maps_to_upcoming_rides_intent(): void
    {
        Permission::create(['name' => 'rides.view', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo('rides.view');

        $service = $this->intentService();
        $context = new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Admin,
            userId: $user->id,
            user: $user,
        );

        $result = $service->classify('Welke ritten heb ik?', $context);

        $this->assertSame(AiChatIntent::RittenKomend, $result->intent);
        $this->assertTrue($result->allowLiveData);
    }

    public function test_chauffeurs_with_rides_today_maps_to_today_rides_intent(): void
    {
        Permission::create(['name' => 'rides.view', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo('rides.view');

        $service = $this->intentService();
        $context = new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Admin,
            userId: $user->id,
            user: $user,
        );

        $result = $service->classify('Welke chauffeurs hebben vandaag ritten?', $context);

        $this->assertSame(AiChatIntent::ChauffeursVandaag, $result->intent);
        $this->assertTrue($result->allowLiveData);
    }

    public function test_guest_own_reservation_question_is_mijn_rit_without_live_access(): void
    {
        $service = $this->intentService();
        $context = new AiChatRequestContext(companyId: 1, channel: AiChatChannel::Public);

        $result = $service->classify('Is mijn reservering bevestigd?', $context);

        $this->assertSame(AiChatIntent::MijnRit, $result->intent);
        $this->assertFalse($result->allowLiveData);
        $this->assertSame('status', $result->queryHint);
    }

    public function test_public_channel_never_allows_own_ride_data_even_when_user_present(): void
    {
        $user = User::factory()->create(['email' => 'klant@example.com']);

        $service = $this->intentService();
        $context = new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
            userId: $user->id,
            user: $user,
        );

        $result = $service->classify('Wanneer word ik opgehaald?', $context);

        $this->assertSame(AiChatIntent::MijnRit, $result->intent);
        $this->assertFalse($result->allowLiveData);
    }

    public function test_mijn_taxi_channel_allows_own_ride_data_for_customer(): void
    {
        $user = User::factory()->create(['email' => 'klant@example.com']);

        $service = $this->intentService();
        $context = new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::MijnTaxi,
            userId: $user->id,
            user: $user,
        );

        $result = $service->classify('Wanneer word ik opgehaald?', $context);

        $this->assertSame(AiChatIntent::MijnRit, $result->intent);
        $this->assertTrue($result->allowLiveData);
        $this->assertFalse($result->isAdmin);
    }

    public function test_mijn_taxi_channel_denies_own_ride_data_for_admin_user(): void
    {
        Permission::create(['name' => 'rides.view', 'guard_name' => 'web']);

        $user = User::factory()->create(['email' => 'admin@example.com']);
        $user->givePermissionTo('rides.view');

        $service = $this->intentService();
        $context = new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::MijnTaxi,
            userId: $user->id,
            user: $user,
        );

        $result = $service->classify('Wanneer word ik opgehaald?', $context);

        $this->assertSame(AiChatIntent::MijnRit, $result->intent);
        $this->assertFalse($result->allowLiveData);
    }

    public function test_admin_user_may_query_live_rides(): void
    {
        Permission::create(['name' => 'rides.view', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->givePermissionTo('rides.view');

        $service = $this->intentService();
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
