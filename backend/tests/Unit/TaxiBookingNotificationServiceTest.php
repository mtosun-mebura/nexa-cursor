<?php

namespace Tests\Unit;

use App\Models\User;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideRequestNotificationLog;
use App\Modules\NexaTaxi\Services\TaxiBookingNotificationService;
use App\Modules\NexaTaxi\Services\TaxiCustomerRideCancelledMailer;
use App\Modules\NexaTaxi\Services\TaxiDriverEligibilityService;
use App\Services\EnvService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class TaxiBookingNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.module_taxi' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::connection('module_taxi')->create('ride_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('source')->nullable();
            $table->string('payment_status')->nullable();
            $table->string('pickup_address');
            $table->string('dropoff_address');
            $table->dateTime('pickup_at');
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->json('booking_payload')->nullable();
            $table->timestamps();
        });

        Schema::connection('module_taxi')->create('ride_request_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ride_request_id');
            $table->string('channel');
            $table->string('status');
            $table->string('recipient_name')->nullable();
            $table->string('recipient_address')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->text('detail')->nullable();
            $table->json('meta')->nullable();
            $table->text('context')->nullable();
            $table->timestamps();
        });
    }

    #[Test]
    public function customer_booking_email_uses_tenant_mail_settings_when_ride_has_no_company(): void
    {
        Mail::fake();

        $companyId = 42;
        app()->instance('resolved_tenant_id', $companyId);

        $this->mock(EnvService::class, function ($mock) use ($companyId): void {
            $mock->shouldReceive('get')->andReturn('');
            $mock->shouldReceive('isMailDeliverableToInbox')->once()->with($companyId)->andReturn(true);
            $mock->shouldReceive('applyMailConfigToRuntime')->once()->with($companyId);
            $mock->shouldReceive('resolveMailFromHeaders')->once()->with($companyId)->andReturn([
                'from_address' => 'taxi@example.test',
                'from_name' => 'Taxi BV',
                'smtp_username' => '',
            ]);
        });

        $ride = RideRequest::on('module_taxi')->create([
            'company_id' => null,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Jan Tester',
            'customer_email' => 'jan@example.test',
            'customer_phone' => '0612345678',
            'quoted_price' => 34.94,
            'booking_payload' => [],
        ]);

        app(TaxiBookingNotificationService::class)->notifyNewRide('module_taxi', $ride, [
            'settings_company_id' => $companyId,
        ]);

        $this->assertTrue(true);
    }

    #[Test]
    public function marketplace_booking_skips_driver_email_and_still_emails_the_customer(): void
    {
        $this->mockDeliverableMail();
        $eligibility = \Mockery::mock(TaxiDriverEligibilityService::class);
        $eligibility->shouldReceive('buildChauffeurQuery')->never();
        $this->app->instance(TaxiDriverEligibilityService::class, $eligibility);

        $ride = $this->makeRide([
            'company_id' => null,
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'booking_payload' => [
                'channel' => RideRequest::SOURCE_NEXA_SUITE,
                'marketplace' => [
                    'source' => RideRequest::SOURCE_NEXA_SUITE,
                    'channel' => 'customer_app',
                    'candidate_company_ids' => [42],
                ],
            ],
        ]);

        app(TaxiBookingNotificationService::class)->notifyNewRide('module_taxi', $ride, [
            'settings_company_id' => 42,
        ]);

        $mails = $this->sentMails();
        $this->assertSame(['jan@example.test'], array_column($mails, 'to'));
        $this->assertStringContainsString('Bevestiging van uw taxiboeking', $mails[0]['subject']);
        $this->assertTrue(
            RideRequestNotificationLog::on('module_taxi')
                ->where('ride_request_id', $ride->id)
                ->where('channel', RideRequestNotificationLog::CHANNEL_EMAIL)
                ->where('status', RideRequestNotificationLog::STATUS_SKIPPED)
                ->where('detail', 'like', 'Marktplaatsboeking:%')
                ->exists()
        );
    }

    #[Test]
    public function tenant_website_booking_emails_the_driver_and_the_customer(): void
    {
        $this->mockDeliverableMail();
        $driver = User::factory()->create([
            'email' => 'chauffeur@example.test',
            'first_name' => 'Piet',
            'last_name' => 'Chauffeur',
            'company_id' => null,
        ]);
        $eligibility = \Mockery::mock(TaxiDriverEligibilityService::class);
        $eligibility->shouldReceive('buildChauffeurQuery')
            ->once()
            ->with(42)
            ->andReturn(User::query()->whereKey($driver->id));
        $this->app->instance(TaxiDriverEligibilityService::class, $eligibility);

        $ride = $this->makeRide([
            'company_id' => 42,
            'source' => RideRequest::SOURCE_BOOKING,
            'booking_payload' => [],
        ]);

        app(TaxiBookingNotificationService::class)->notifyNewRide('module_taxi', $ride, [
            'settings_company_id' => 42,
        ]);

        $byRecipient = [];
        foreach ($this->sentMails() as $mail) {
            $byRecipient[$mail['to']] = $mail['subject'];
        }

        $this->assertArrayHasKey('jan@example.test', $byRecipient);
        $this->assertArrayHasKey('chauffeur@example.test', $byRecipient);
        $this->assertStringContainsString('Bevestiging van uw taxiboeking', $byRecipient['jan@example.test']);
        $this->assertStringContainsString('Nieuwe taxirit', $byRecipient['chauffeur@example.test']);
    }

    #[Test]
    public function marketplace_cancellation_still_emails_the_customer_about_the_refund(): void
    {
        $this->mockDeliverableMail();

        $ride = $this->makeRide([
            'company_id' => null,
            'source' => RideRequest::SOURCE_NEXA_SUITE,
            'payment_status' => RideRequest::PAYMENT_STATUS_PAID,
            'booking_payload' => [
                'channel' => RideRequest::SOURCE_NEXA_SUITE,
                'marketplace' => [
                    'source' => RideRequest::SOURCE_NEXA_SUITE,
                    'channel' => 'customer_app',
                    'candidate_company_ids' => [42],
                ],
            ],
        ]);

        $sent = app(TaxiCustomerRideCancelledMailer::class)->send('module_taxi', $ride, [
            'reason' => 'customer',
            'refunded' => true,
        ]);

        $this->assertTrue($sent);
        $mails = $this->sentMails();
        $this->assertCount(1, $mails);
        $this->assertSame('jan@example.test', $mails[0]['to']);
        $this->assertStringContainsString('geannuleerd', $mails[0]['subject']);
        $this->assertStringContainsString('teruggestort', $mails[0]['html']);
        $this->assertStringNotContainsString('chauffeur@example.test', $mails[0]['html']);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRide(array $overrides = []): RideRequest
    {
        return RideRequest::on('module_taxi')->create(array_merge([
            'company_id' => null,
            'pickup_address' => 'A',
            'dropoff_address' => 'B',
            'pickup_at' => now()->addHour(),
            'customer_name' => 'Jan Tester',
            'customer_email' => 'jan@example.test',
            'customer_phone' => '0612345678',
            'quoted_price' => 34.94,
            'booking_payload' => [],
        ], $overrides));
    }

    private function mockDeliverableMail(): void
    {
        $this->mock(EnvService::class, function ($mock): void {
            $mock->shouldReceive('get')->andReturn('');
            $mock->shouldReceive('isMailDeliverableToInbox')->andReturn(true);
            $mock->shouldReceive('applyMailConfigToRuntime')->andReturnNull();
            $mock->shouldReceive('applyPlatformMailConfigToRuntime')->andReturnNull();
            $mock->shouldReceive('resolveMailFromHeaders')->andReturn([
                'from_address' => 'taxi@example.test',
                'from_name' => 'Taxi BV',
                'smtp_username' => '',
            ]);
        });
    }

    /**
     * @return list<array{to: string, subject: string, html: string}>
     */
    private function sentMails(): array
    {
        $transport = app('mail.manager')->mailer()->getSymfonyTransport();
        $this->assertInstanceOf(ArrayTransport::class, $transport);

        $mails = [];
        foreach ($transport->messages() as $sent) {
            $message = $sent->getOriginalMessage();
            $this->assertInstanceOf(Email::class, $message);
            $to = $message->getTo();
            $mails[] = [
                'to' => $to[0]->getAddress(),
                'subject' => (string) $message->getSubject(),
                'html' => (string) $message->getHtmlBody(),
            ];
        }

        return $mails;
    }
}
