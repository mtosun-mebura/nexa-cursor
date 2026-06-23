<?php

namespace Tests\Unit;

use App\DTO\AiChat\AiChatRequestContext;
use App\Enums\AiChat\AiChatChannel;
use App\Enums\AiChat\AiChatIntent;
use App\Enums\AiChat\AiChatResponseMode;
use App\Models\User;
use App\Services\AiChat\AiChatIntentDetector;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AiChatIntentDetectorTest extends TestCase
{
    private AiChatIntentDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new AiChatIntentDetector();
    }

    public function test_public_tarief_question_maps_to_tarieven(): void
    {
        $result = $this->detector->detect('Wat kost een rit per kilometer?', new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
        ));

        $this->assertSame(AiChatIntent::Tarieven, $result['intent']);
    }

    public function test_travel_intent_question_maps_to_rit_offerte(): void
    {
        $public = $this->detector->detect('Ik wil naar Schiphol', new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
        ));

        $this->assertSame(AiChatIntent::RitOfferte, $public['intent']);

        $admin = $this->detector->detect('Ik wil naar Schiphol', new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Admin,
            userId: 1,
        ));

        $this->assertSame(AiChatIntent::RitOfferte, $admin['intent']);
    }

    public function test_travel_intent_does_not_override_admin_operational_question(): void
    {
        $result = $this->detector->detect('Welke chauffeur rijdt morgen naar Schiphol?', new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Admin,
            userId: 1,
        ));

        $this->assertSame(AiChatIntent::ChauffeursSchipholMorgen, $result['intent']);
    }

    public function test_route_price_question_maps_to_rit_offerte(): void
    {
        $schiphol = $this->detector->detect('Wat kost een rit naar Schiphol?', new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
        ));

        $this->assertSame(AiChatIntent::RitOfferte, $schiphol['intent']);

        $duesseldorf = $this->detector->detect('Wat kost een rit van Enschede naar Düsseldorf Airport?', new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
        ));

        $this->assertSame(AiChatIntent::RitOfferte, $duesseldorf['intent']);
    }

    public function test_booking_question_maps_to_rit_offerte(): void
    {
        $result = $this->detector->detect('Boek een rit naar Schiphol', new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
        ));

        $this->assertSame(AiChatIntent::RitOfferte, $result['intent']);
    }

    public function test_booking_question_does_not_map_to_reserveren(): void
    {
        $result = $this->detector->detect('Boek een rit naar Schiphol', new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
        ));

        $this->assertNotSame(AiChatIntent::Reserveren, $result['intent']);
    }

    public function test_public_diensten_question_maps_to_diensten(): void
    {
        $result = $this->detector->detect('Hebben jullie luchthavenvervoer?', new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
        ));

        $this->assertSame(AiChatIntent::Diensten, $result['intent']);
    }

    public function test_admin_omzet_vandaag_maps_correctly(): void
    {
        $result = $this->detector->detect('Wat is de omzet van vandaag?', new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Admin,
            userId: 1,
        ));

        $this->assertSame(AiChatIntent::OmzetVandaag, $result['intent']);
        $this->assertSame(AiChatResponseMode::Summary, $result['response_mode']);
    }

    public function test_admin_hoeveel_ritten_vandaag_uses_count_mode(): void
    {
        $result = $this->detector->detect('Hoeveel ritten hebben we vandaag?', new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Admin,
            userId: 1,
        ));

        $this->assertSame(AiChatIntent::RittenVandaag, $result['intent']);
        $this->assertSame(AiChatResponseMode::Count, $result['response_mode']);
    }

    public function test_public_operational_question_classified_but_not_rag(): void
    {
        $result = $this->detector->detect('Welke ritten staan morgen gepland?', new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
        ));

        $this->assertSame(AiChatIntent::RittenMorgen, $result['intent']);
    }

    public function test_own_ride_question_always_maps_to_mijn_rit(): void
    {
        $guestResult = $this->detector->detect('Is mijn reservering bevestigd?', new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
        ));

        $this->assertSame(AiChatIntent::MijnRit, $guestResult['intent']);
        $this->assertSame('status', $guestResult['query_hint']);

        Permission::create(['name' => 'rides.view', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->givePermissionTo('rides.view');

        $adminResult = $this->detector->detect('Wie is mijn chauffeur?', new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
            userId: $admin->id,
            user: $admin,
        ));

        $this->assertSame(AiChatIntent::MijnRit, $adminResult['intent']);
        $this->assertNotSame(AiChatIntent::Reserveren, $adminResult['intent']);
    }

    public function test_wanneer_wordt_ik_opgehaald_maps_to_mijn_rit_with_pickup_hint(): void
    {
        $result = $this->detector->detect('Wanneer wordt ik opgehaald?', new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Public,
        ));

        $this->assertSame(AiChatIntent::MijnRit, $result['intent']);
        $this->assertSame('ophaaltijd', $result['query_hint']);
    }

    /**
     * @dataProvider mijnTaxiOwnRideQuestionsProvider
     */
    public function test_mijn_taxi_own_ride_questions_map_correctly(string $message, string $expectedHint, AiChatResponseMode $expectedMode): void
    {
        $result = $this->detector->detect($message, new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::MijnTaxi,
            userId: 1,
        ));

        $this->assertSame(AiChatIntent::MijnRit, $result['intent']);
        $this->assertSame($expectedHint, $result['query_hint']);
        $this->assertSame($expectedMode, $result['response_mode']);
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: AiChatResponseMode}>
     */
    public static function mijnTaxiOwnRideQuestionsProvider(): array
    {
        return [
            'volgende rit' => ['Wanneer is de volgende rit?', 'volgende', AiChatResponseMode::List],
            'geplande ritten tellen' => ['Hoeveel ritten heb ik gepland?', 'gepland', AiChatResponseMode::Count],
            'voltooide ritten tellen' => ['Hoeveel voltooide ritten heb ik?', 'voltooid', AiChatResponseMode::Count],
            'prijs volgende rit' => ['Wat is de prijs van de eerst volgende rit?', 'prijs', AiChatResponseMode::List],
            'factuur laatste rit' => ['Haal de factuur op van mijn laatste rit', 'factuur', AiChatResponseMode::List],
            'ritten vandaag' => ['Welke ritten heb ik vandaag?', 'vandaag', AiChatResponseMode::List],
            'aankomende ritten' => ['Welke aankomende ritten heb ik?', 'aankomend', AiChatResponseMode::List],
        ];
    }

    /**
     * @dataProvider adminOperationalQuestionsProvider
     */
    public function test_admin_operational_questions_map_correctly(
        string $message,
        AiChatIntent $expectedIntent,
        ?AiChatResponseMode $expectedMode = null,
    ): void {
        $result = $this->detector->detect($message, new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::Admin,
            userId: 1,
        ));

        $this->assertSame($expectedIntent, $result['intent']);

        if ($expectedMode !== null) {
            $this->assertSame($expectedMode, $result['response_mode']);
        }
    }

    /**
     * @return array<string, array{0: string, 1: AiChatIntent, 2?: AiChatResponseMode}>
     */
    public static function adminOperationalQuestionsProvider(): array
    {
        return [
            'ritten morgen' => ['Welke ritten staan morgen gepland?', AiChatIntent::RittenMorgen],
            'ritten vandaag' => ['Welke ritten staan vandaag gepland?', AiChatIntent::RittenVandaag],
            'aantal ritten vandaag' => ['Hoeveel ritten hebben we vandaag?', AiChatIntent::RittenVandaag, AiChatResponseMode::Count],
            'aantal ritten morgen' => ['Hoeveel ritten hebben we morgen?', AiChatIntent::RittenMorgen, AiChatResponseMode::Count],
            'open ritten' => ['Welke ritten moeten nog bevestigd worden?', AiChatIntent::OpenRitten],
            'geannuleerde ritten' => ['Welke ritten zijn geannuleerd?', AiChatIntent::RittenGeannuleerd],
            'zonder chauffeur' => ['Welke ritten hebben geen chauffeur toegewezen?', AiChatIntent::RittenZonderChauffeur],
            'chauffeurs morgen beschikbaar' => ['Welke chauffeurs zijn morgen beschikbaar?', AiChatIntent::VrijeChauffeursMorgen],
            'chauffeurs vandaag ritten' => ['Welke chauffeurs hebben vandaag ritten?', AiChatIntent::ChauffeursVandaag],
            'meeste ritten chauffeur' => ['Welke chauffeur heeft de meeste ritten vandaag?', AiChatIntent::ChauffeursMeesteRittenVandaag],
            'chauffeurs zonder rit' => ['Welke chauffeurs hebben nog geen rit toegewezen gekregen?', AiChatIntent::ChauffeursZonderRit],
            'chauffeur schiphol morgen' => ['Welke chauffeur rijdt morgen naar Schiphol?', AiChatIntent::ChauffeursSchipholMorgen],
            'chauffeurs onderweg' => ['Welke chauffeurs zijn momenteel onderweg?', AiChatIntent::ChauffeursOnderweg],
            'dienst niet gestart' => ['Welke chauffeurs hebben hun dienst nog niet gestart?', AiChatIntent::ChauffeursOnderweg],
            'klanten meeste ritten' => ['Welke klanten hebben de meeste ritten geboekt?', AiChatIntent::KlantenMeesteRitten],
            'klanten deze maand' => ['Welke klanten hebben deze maand een rit geboekt?', AiChatIntent::KlantenDezeMaand],
            'klanten luchthaven' => ['Welke klanten hebben een luchthavenrit gepland?', AiChatIntent::KlantenLuchthaven],
            'klanten geannuleerd' => ['Welke klanten hebben een rit geannuleerd?', AiChatIntent::KlantenGeannuleerd],
            'klanten nieuw' => ['Welke klanten zijn nieuw deze maand?', AiChatIntent::KlantenNieuwDezeMaand],
            'omzet morgen' => ['Wat is de verwachte omzet van morgen?', AiChatIntent::OmzetMorgen, AiChatResponseMode::Summary],
            'omzet vandaag' => ['Wat is de omzet van vandaag?', AiChatIntent::OmzetVandaag, AiChatResponseMode::Summary],
            'omzet vorige maand' => ['Wat was de omzet vorige maand?', AiChatIntent::OmzetVorigeMaand, AiChatResponseMode::Summary],
            'hoogste omzet ritten' => ['Welke ritten hebben de hoogste omzet?', AiChatIntent::RittenHoogsteOmzet, AiChatResponseMode::Summary],
            'luchthavenritten deze maand' => ['Hoeveel luchthavenritten hebben we deze maand uitgevoerd?', AiChatIntent::LuchthavenrittenDezeMaand, AiChatResponseMode::Count],
            'planning zonder chauffeur' => ['Zijn er ritten zonder chauffeur?', AiChatIntent::RittenZonderChauffeur],
            'dubbel ingepland' => ['Zijn er chauffeurs dubbel ingepland?', AiChatIntent::Planning],
            'overlappende ritten' => ['Welke ritten overlappen elkaar?', AiChatIntent::Planning],
            'vertrek binnen uur' => ['Welke ritten vertrekken binnen een uur?', AiChatIntent::Planning],
            'zonder voertuig' => ['Welke ritten hebben nog geen voertuig?', AiChatIntent::RittenZonderVoertuig],
        ];
    }

    public function test_mijn_taxi_hoeveel_ritten_does_not_map_to_admin_intent(): void
    {
        $result = $this->detector->detect('Hoeveel ritten heb ik gepland?', new AiChatRequestContext(
            companyId: 1,
            channel: AiChatChannel::MijnTaxi,
            userId: 1,
        ));

        $this->assertSame(AiChatIntent::MijnRit, $result['intent']);
        $this->assertNotSame(AiChatIntent::RittenKomend, $result['intent']);
    }
}
