<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\WebsitePage;
use App\Modules\NexaTaxi\Models\KnowledgeDocument;
use App\Modules\NexaTaxi\Services\TaxiKnowledgeWebsiteImportService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TaxiKnowledgeWebsiteImportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.connections.module_taxi_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::connection('module_taxi_test')->create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('category')->nullable();
            $table->text('embedding')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function test_import_creates_documents_from_website_service_cards(): void
    {
        $company = Company::query()->create([
            'name' => 'Taxi Import Test',
            'is_active' => true,
        ]);

        WebsitePage::query()->create([
            'slug' => 'diensten',
            'title' => 'Diensten',
            'content' => '',
            'page_type' => 'custom',
            'module_name' => 'taxi',
            'company_id' => $company->id,
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 1,
            'home_sections' => [
                'cards_ronde_hoeken' => [
                    'items' => [
                        [
                            'text' => '<p><strong>Luchthavenvervoer</strong></p><p>Nexa Taxi biedt luchthavenvervoer naar onder andere Schiphol, Eindhoven Airport, Düsseldorf en Weeze.</p>',
                        ],
                    ],
                ],
            ],
        ]);

        $service = new TaxiKnowledgeWebsiteImportService();
        $stats = $service->importForCompany((int) $company->id, 'module_taxi_test');

        $this->assertSame(1, $stats['created']);
        $this->assertSame(0, $stats['updated']);

        $document = KnowledgeDocument::on('module_taxi_test')->first();
        $this->assertNotNull($document);
        $this->assertSame('Luchthavenvervoer', $document->title);
        $this->assertSame('diensten', $document->category);
        $this->assertStringContainsString('Schiphol', $document->content);
    }

    public function test_import_updates_existing_document_with_same_title_and_category(): void
    {
        $company = Company::query()->create([
            'name' => 'Taxi Import Update Test',
            'is_active' => true,
        ]);

        KnowledgeDocument::on('module_taxi_test')->create([
            'title' => 'Zakelijk vervoer',
            'category' => 'diensten',
            'content' => 'Oude tekst die lang genoeg is voor validatie.',
        ]);

        WebsitePage::query()->create([
            'slug' => 'diensten',
            'title' => 'Diensten',
            'content' => '',
            'page_type' => 'custom',
            'module_name' => 'taxi',
            'company_id' => $company->id,
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 1,
            'home_sections' => [
                'cards_ronde_hoeken' => [
                    'items' => [
                        [
                            'text' => '<p><strong>Zakelijk vervoer</strong></p><p>Zakelijk vervoer is mogelijk op afspraak. Bedrijven kunnen vaste ritten en facturatie aanvragen.</p>',
                        ],
                    ],
                ],
            ],
        ]);

        $service = new TaxiKnowledgeWebsiteImportService();
        $stats = $service->importForCompany((int) $company->id, 'module_taxi_test');

        $this->assertSame(0, $stats['created']);
        $this->assertSame(1, $stats['updated']);

        $document = KnowledgeDocument::on('module_taxi_test')->first();
        $this->assertStringContainsString('facturatie', $document->content);
    }

    public function test_import_creates_general_documents_for_contact_and_booking(): void
    {
        $company = Company::query()->create([
            'name' => 'Taxi Algemeen Test',
            'phone' => '+31123456789',
            'email' => 'info@taxitest.nl',
            'street' => 'Stationsweg',
            'house_number' => '12',
            'postal_code' => '7511 HB',
            'city' => 'Enschede',
            'is_active' => true,
        ]);

        WebsitePage::query()->create([
            'slug' => 'home',
            'title' => 'Home',
            'content' => '',
            'page_type' => 'home',
            'module_name' => 'taxi',
            'company_id' => $company->id,
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 1,
            'home_sections' => [
                'section_order' => ['hero', 'component:taxi.boekingsmodule', 'footer'],
                'visibility' => [
                    'hero' => true,
                    'component:taxi.boekingsmodule' => true,
                    'footer' => true,
                ],
                'component:taxi.boekingsmodule' => [
                    'title' => 'Boek je rit online',
                ],
                'footer' => [
                    'tagline' => 'Betrouwbaar taxivervoer in Enschede en omgeving.',
                    'quick_links_title' => 'Snelle links',
                    'quick_links' => [
                        ['label' => 'Contact', 'url' => '/contact'],
                    ],
                    'support_links_title' => 'Hulp',
                    'support_links' => [
                        ['label' => 'Privacy', 'url' => '/privacy'],
                    ],
                ],
            ],
        ]);

        $service = new TaxiKnowledgeWebsiteImportService();
        $stats = $service->importForCompany((int) $company->id, 'module_taxi_test');

        $this->assertGreaterThanOrEqual(3, $stats['created']);

        $titles = KnowledgeDocument::on('module_taxi_test')
            ->orderBy('title')
            ->pluck('title')
            ->all();

        $this->assertContains('Contactgegevens', $titles);
        $this->assertContains('Taxirit boeken op de website', $titles);
        $this->assertContains('Veelgestelde vragen over de website', $titles);

        $contact = KnowledgeDocument::on('module_taxi_test')->where('title', 'Contactgegevens')->first();
        $this->assertSame('contact', $contact->category);
        $this->assertStringContainsString('info@taxitest.nl', $contact->content);
        $this->assertStringContainsString('+31123456789', $contact->content);

        $booking = KnowledgeDocument::on('module_taxi_test')->where('title', 'Taxirit boeken op de website')->first();
        $this->assertSame('algemeen', $booking->category);
        $this->assertStringContainsString('Boek je rit online', $booking->content);
        $this->assertStringContainsString('Reisgegevens', $booking->content);
    }
}
