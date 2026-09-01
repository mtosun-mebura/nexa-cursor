<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\InfoRequestController;
use App\Models\Company;
use App\Models\EmailTemplate;
use App\Models\InfoRequestFormField;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InfoRequestSubmitTest extends TestCase
{
    public function test_info_request_form_submits_via_registered_route(): void
    {
        Mail::fake();

        $company = Company::query()->create([
            'name' => 'Test Taxi BV',
            'is_active' => true,
        ]);

        $template = EmailTemplate::query()->create([
            'name' => 'Contact test',
            'subject' => 'Nieuwe informatieaanvraag',
            'type' => 'informatieaanvraag',
            'html_content' => '<p>{{ VOORNAAM }}</p>',
            'is_active' => true,
            'company_id' => $company->id,
            'recipient_type' => 'email',
            'recipient_email' => 'ontvanger@example.com',
        ]);

        foreach ([
            ['name' => 'voornaam', 'label' => 'Voornaam', 'is_required' => true, 'validation_rule' => null, 'sort_order' => 10],
            ['name' => 'achternaam', 'label' => 'Achternaam', 'is_required' => true, 'validation_rule' => null, 'sort_order' => 20],
            ['name' => 'email_aanvraag', 'label' => 'E-mailadres', 'is_required' => true, 'validation_rule' => 'email', 'sort_order' => 30],
            ['name' => 'omschrijving', 'label' => 'Omschrijving / vraag', 'is_required' => true, 'validation_rule' => null, 'sort_order' => 50],
        ] as $field) {
            InfoRequestFormField::query()->create($field);
        }

        $formTimeFields = InfoRequestController::formTimeFields(time() - 5);

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenantFromHost::class,
            \App\Http\Middleware\TenantMiddleware::class,
        ])->postJson(route('frontend.send-info-request'), array_merge($formTimeFields, [
            'template_id' => $template->id,
            'company_website' => '',
            'voornaam' => 'Jan',
            'achternaam' => 'Jansen',
            'email_aanvraag' => 'jan@example.com',
            'omschrijving' => 'Ik heb een vraag over taxi.',
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_info_request_route_is_registered(): void
    {
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('frontend.send-info-request'));
    }

    public function test_info_request_rejects_invalid_form_time_token_and_returns_fresh_fields(): void
    {
        Mail::fake();

        $response = $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenantFromHost::class,
            \App\Http\Middleware\TenantMiddleware::class,
        ])->postJson(route('frontend.send-info-request'), [
            'template_id' => 1,
            'form_time' => time() - 5,
            'form_time_token' => 'invalid-token',
            'company_website' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'form_time', 'form_time_token']);
    }

    public function test_info_request_honeypot_looks_successful_but_sends_no_mail(): void
    {
        Mail::fake();

        $response = $this->withoutTenantMiddleware()->postJson(route('frontend.send-info-request'), [
            'template_id' => 1,
            'company_website' => 'https://spam.example',
            'voornaam' => 'Bot',
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        Mail::assertNothingSent();
    }

    public function test_info_request_rejects_instant_submit(): void
    {
        Mail::fake();

        $response = $this->withoutTenantMiddleware()->postJson(route('frontend.send-info-request'), array_merge(
            InfoRequestController::formTimeFields(),
            [
                'template_id' => 1,
                'company_website' => '',
            ]
        ));

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Het formulier is verlopen of ongeldig. Vernieuw de pagina en probeer opnieuw.']);
        Mail::assertNothingSent();
    }

    public function test_info_request_rejects_script_markup(): void
    {
        Mail::fake();

        $company = Company::query()->create([
            'name' => 'Test Taxi BV',
            'is_active' => true,
        ]);

        $template = EmailTemplate::query()->create([
            'name' => 'Contact test',
            'subject' => 'Nieuwe informatieaanvraag',
            'type' => 'informatieaanvraag',
            'html_content' => '<p>{{ VOORNAAM }}</p>',
            'is_active' => true,
            'company_id' => $company->id,
            'recipient_type' => 'email',
            'recipient_email' => 'ontvanger@example.com',
        ]);

        foreach ([
            ['name' => 'voornaam', 'label' => 'Voornaam', 'is_required' => true, 'validation_rule' => null, 'sort_order' => 10],
            ['name' => 'achternaam', 'label' => 'Achternaam', 'is_required' => true, 'validation_rule' => null, 'sort_order' => 20],
            ['name' => 'email_aanvraag', 'label' => 'E-mailadres', 'is_required' => true, 'validation_rule' => 'email', 'sort_order' => 30],
            ['name' => 'omschrijving', 'label' => 'Omschrijving / vraag', 'is_required' => true, 'validation_rule' => null, 'sort_order' => 50],
        ] as $field) {
            InfoRequestFormField::query()->create($field);
        }

        $response = $this->withoutTenantMiddleware()->postJson(route('frontend.send-info-request'), array_merge(
            InfoRequestController::formTimeFields(time() - 5),
            [
                'template_id' => $template->id,
                'company_website' => '',
                'voornaam' => 'Jan',
                'achternaam' => 'Jansen',
                'email_aanvraag' => 'jan@example.com',
                'omschrijving' => '<script>alert(1)</script>',
            ]
        ));

        $response->assertStatus(422);
        Mail::assertNothingSent();
    }

    private function withoutTenantMiddleware()
    {
        return $this->withoutMiddleware([
            \App\Http\Middleware\ResolveTenantFromHost::class,
            \App\Http\Middleware\TenantMiddleware::class,
        ]);
    }
}
