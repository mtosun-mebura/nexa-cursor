<?php

namespace Tests\Unit;

use App\Services\AiChat\AiChatProductFaqService;
use App\Services\NexaContactAanvraagEmailTemplateService;
use Tests\TestCase;

class AiChatProductFaqServiceTest extends TestCase
{
    private function faq(): AiChatProductFaqService
    {
        return new AiChatProductFaqService;
    }

    public function test_contact_question_points_to_form_and_inbox(): void
    {
        $reply = $this->faq()->answer('Ik ben geïnteresseerd, met wie moet ik contact zoeken?');

        $this->assertStringContainsString('/contact', $reply);
        $this->assertStringContainsString(NexaContactAanvraagEmailTemplateService::RECIPIENT_EMAIL, $reply);
        $this->assertStringContainsString('contactformulier', mb_strtolower($reply));
    }

    public function test_cost_question_points_to_pricing_page(): void
    {
        $reply = $this->faq()->answer('Wat kost NEXA Suite?');

        $this->assertStringContainsString('/prijzen', $reply);
        $this->assertStringContainsString('/contact', $reply);
        $this->assertStringContainsString('€ 49', $reply);
        $this->assertStringContainsString('€ 750', $reply);
        $this->assertStringContainsString('**Start**', $reply);
        $this->assertStringContainsString('**Pro**', $reply);
        $this->assertStringContainsString('**Business**', $reply);
        $this->assertStringContainsString('Website met boekingsmodule', $reply);
        $this->assertStringContainsString('Onbeperkt chauffeurs', $reply);
        $this->assertStringContainsString('Contractvervoer', $reply);
    }

    public function test_package_question_lists_all_packages(): void
    {
        $reply = $this->faq()->answer('Welke pakketten zijn er?');

        $this->assertStringContainsString('**Start**', $reply);
        $this->assertStringContainsString('**Pro**', $reply);
        $this->assertStringContainsString('**Business**', $reply);
        $this->assertStringContainsString('€ 99', $reply);
        $this->assertStringContainsString('/prijzen', $reply);
    }

    public function test_named_package_question_returns_that_package(): void
    {
        $reply = $this->faq()->answer('Wat zit er in het Pro-pakket?');

        $this->assertStringContainsString('**Pro**', $reply);
        $this->assertStringContainsString('€ 99', $reply);
        $this->assertStringContainsString('Onbeperkt chauffeurs', $reply);
        $this->assertStringContainsString('chauffeur-app', mb_strtolower($reply));
        $this->assertStringContainsString('pakket=Pro', $reply);
        $this->assertStringNotContainsString('**Start**', $reply);
        $this->assertStringNotContainsString('**Business**', $reply);
    }

    public function test_website_setup_question_uses_one_off_price(): void
    {
        $reply = $this->faq()->answer('Wat kost het om de website live te zetten?');

        $this->assertStringContainsString('€ 750', $reply);
        $this->assertStringContainsString('eenmalig', mb_strtolower($reply));
        $this->assertStringContainsString('/prijzen', $reply);
    }

    public function test_pros_and_cons_use_website_comparison(): void
    {
        $reply = $this->faq()->answer('Wat zijn de voor- en nadelen?');

        $this->assertStringContainsString('WhatsApp', $reply);
        $this->assertStringContainsString('boekingsmodule', mb_strtolower($reply));
        $this->assertStringContainsString('Excel', $reply);
    }

    public function test_taxi_product_question_describes_booking_and_driver_app(): void
    {
        $reply = $this->faq()->answer('Hoe werkt de taxi-applicatie?');

        $this->assertStringContainsString('Nexa Taxi', $reply);
        $this->assertStringContainsString('chauffeur-app', mb_strtolower($reply));
        $this->assertStringContainsString('/taxi', $reply);
    }

    public function test_contract_question_describes_fixed_rides(): void
    {
        $reply = $this->faq()->answer('Doen jullie ook contractvervoer voor school en zorg?');

        $this->assertStringContainsString('leerlingenvervoer', mb_strtolower($reply));
        $this->assertStringContainsString('/contractvervoer', $reply);
    }

    public function test_website_module_question(): void
    {
        $reply = $this->faq()->answer('Krijg ik een eigen website met boekingsknop?');

        $this->assertStringContainsString('boekingsmodule', mb_strtolower($reply));
        $this->assertStringContainsString('website builder', mb_strtolower($reply));
        $this->assertStringContainsString('SEO', $reply);
        $this->assertStringContainsString('/website', $reply);
    }

    public function test_chauffeur_app_explains_home_screen_install(): void
    {
        $reply = $this->faq()->answer('Hoe installeer ik de chauffeur-app op iPhone of Android?');

        $this->assertStringContainsString('beginscherm', mb_strtolower($reply));
        $this->assertStringContainsString('App Store', $reply);
        $this->assertStringContainsString('/taxi', $reply);
    }

    public function test_contract_app_explains_parent_and_contractor(): void
    {
        $reply = $this->faq()->answer('Wat ziet een ouder in de contract-app?');

        $this->assertStringContainsString('ouder', mb_strtolower($reply));
        $this->assertStringContainsString('afmelden', mb_strtolower($reply));
        $this->assertStringContainsString('/contractvervoer', $reply);
    }

    public function test_unknown_question_stays_helpful(): void
    {
        $reply = $this->faq()->answer('xyzzy foobar');

        $this->assertStringContainsString('NEXA Suite', $reply);
        $this->assertStringContainsString('/contact', $reply);
    }
}
