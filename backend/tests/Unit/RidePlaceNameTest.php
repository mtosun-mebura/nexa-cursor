<?php

namespace Tests\Unit;

use App\Modules\NexaTaxi\Support\RidePlaceName;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RidePlaceNameTest extends TestCase
{
    #[Test]
    public function a_business_name_is_kept_above_a_street(): void
    {
        $this->assertSame(
            'Pathé Enschede',
            RidePlaceName::distinct('Pathé Enschede', 'Boulevard 1941 1, Enschede')
        );
        $this->assertSame(
            'De Grolsch Veste',
            RidePlaceName::distinct('  De Grolsch Veste ', 'Colosseum 65, Enschede')
        );
    }

    #[Test]
    public function a_plain_street_or_city_does_not_become_a_second_line(): void
    {
        $this->assertNull(RidePlaceName::distinct('', 'Deurningerstraat 147, Enschede'));
        $this->assertNull(RidePlaceName::distinct('Deurningerstraat 147', 'Deurningerstraat 147, Enschede'));
        $this->assertNull(RidePlaceName::distinct('Colosseum 60', 'Colosseum 60, Enschede'));
        $this->assertNull(RidePlaceName::distinct('Colosseum', 'Colosseum 60, Enschede'));
        $this->assertNull(RidePlaceName::distinct('Enschede', 'Deurningerstraat 147, Enschede'));
        $this->assertNull(RidePlaceName::distinct('Deurningerstraat 147, Enschede', 'Deurningerstraat 147, Enschede'));
    }

    #[Test]
    public function payload_returns_only_a_stored_name(): void
    {
        $this->assertSame('Pathé Enschede', RidePlaceName::fromPayload([
            'pickup_place_name' => 'Pathé Enschede',
        ], 'pickup'));
        $this->assertNull(RidePlaceName::fromPayload(['pickup_place_name' => 'Pathé Enschede'], 'dropoff'));
        $this->assertNull(RidePlaceName::fromPayload(null, 'pickup'));
    }

    #[Test]
    public function format_line_prefixes_business_name_when_distinct(): void
    {
        $this->assertSame(
            'Action — Deurningerstraat 155, Enschede',
            RidePlaceName::formatLine('Action', 'Deurningerstraat 155, Enschede')
        );
        $this->assertSame(
            'Coolblue — Schuttersveld 1F',
            RidePlaceName::formatLine('Coolblue', 'Schuttersveld 1F')
        );
        $this->assertSame(
            'Deurningerstraat 155, Enschede',
            RidePlaceName::formatLine(null, 'Deurningerstraat 155, Enschede')
        );
        $this->assertSame(
            'Deurningerstraat 155, Enschede',
            RidePlaceName::formatLine('Deurningerstraat 155', 'Deurningerstraat 155, Enschede')
        );
    }
}
