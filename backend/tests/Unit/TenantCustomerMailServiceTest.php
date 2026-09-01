<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\TenantCustomerEmail;
use App\Services\TenantCustomerMailService;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TenantCustomerMailServiceTest extends TestCase
{
    public function test_send_logs_a_sent_customer_email(): void
    {
        Mail::fake();
        $company = Company::query()->create([
            'name' => 'Mail Test '.uniqid(),
            'is_active' => true,
        ]);

        $record = app(TenantCustomerMailService::class)->send([
            'company_id' => $company->id,
            'type' => TenantCustomerEmail::TYPE_BOOKING,
            'to_email' => 'klant@example.test',
            'to_name' => 'Test Klant',
            'subject' => 'Bevestiging van uw taxiboeking #12',
            'html' => '<p>Hallo Test Klant</p>',
            'text' => 'Hallo Test Klant',
            'related_type' => 'ride_request',
            'related_id' => 12,
        ]);

        $this->assertSame(TenantCustomerEmail::STATUS_SENT, $record->status);
        $this->assertSame($company->id, $record->company_id);
        $this->assertSame('klant@example.test', $record->recipient_email);
        $this->assertSame(TenantCustomerEmail::TYPE_BOOKING, $record->type);
        $this->assertStringContainsString('Hallo Test Klant', (string) $record->body_html);
        $this->assertDatabaseHas('tenant_customer_emails', [
            'id' => $record->id,
            'status' => TenantCustomerEmail::STATUS_SENT,
            'recipient_email' => 'klant@example.test',
        ]);
    }

    public function test_invalid_recipient_is_logged_as_failed_without_sending(): void
    {
        Mail::fake();

        $record = app(TenantCustomerMailService::class)->send([
            'company_id' => null,
            'type' => TenantCustomerEmail::TYPE_BOOKING,
            'to_email' => 'niet-geldig',
            'subject' => 'Test',
            'html' => '<p>Test</p>',
        ]);

        $this->assertSame(TenantCustomerEmail::STATUS_FAILED, $record->status);
        $this->assertSame('Geen geldig e-mailadres.', $record->error_message);
        Mail::assertNothingOutgoing();
    }

    public function test_resend_creates_a_new_row_and_increments_the_original(): void
    {
        Mail::fake();
        $company = Company::query()->create([
            'name' => 'Mail Resend '.uniqid(),
            'is_active' => true,
        ]);

        $original = app(TenantCustomerMailService::class)->send([
            'company_id' => $company->id,
            'type' => TenantCustomerEmail::TYPE_RIDE_ACCEPTED,
            'to_email' => 'klant@example.test',
            'to_name' => 'Test Klant',
            'subject' => 'Uw taxirit is geaccepteerd',
            'html' => '<p>Geaccepteerd</p>',
        ]);

        $copy = app(TenantCustomerMailService::class)->resend($original);

        $this->assertSame(TenantCustomerEmail::STATUS_SENT, $copy->status);
        $this->assertSame($original->id, $copy->resent_from_id);
        $this->assertSame($original->subject, $copy->subject);
        $this->assertSame('klant@example.test', $copy->recipient_email);
        $this->assertSame(1, (int) $original->fresh()->resent_count);
        $this->assertNotNull($original->fresh()->last_resent_at);
        $this->assertDatabaseHas('tenant_customer_emails', [
            'id' => $copy->id,
            'resent_from_id' => $original->id,
            'status' => TenantCustomerEmail::STATUS_SENT,
        ]);
    }

    public function test_mailbox_preview_html_keeps_light_mode_and_replaces_logo_placeholder(): void
    {
        $company = Company::query()->create([
            'name' => 'Preview Co',
            'is_active' => true,
        ]);
        $email = TenantCustomerEmail::query()->create([
            'company_id' => $company->id,
            'type' => TenantCustomerEmail::TYPE_BOOKING,
            'recipient_email' => 'klant@example.test',
            'subject' => 'Bevestiging',
            'body_html' => '<p>Hallo klant</p><!--NEXA_COMPANY_LOGO-->',
            'body_text' => 'Hallo klant',
            'status' => TenantCustomerEmail::STATUS_SENT,
            'sent_at' => now(),
        ]);

        $html = app(TenantCustomerMailService::class)->mailboxPreviewHtml($email);

        $this->assertStringContainsString('Hallo klant', $html);
        $this->assertStringContainsString('color-scheme', $html);
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringNotContainsString('<!--NEXA_COMPANY_LOGO-->', $html);
        $this->assertStringContainsString('Preview Co', $html);
    }
}
