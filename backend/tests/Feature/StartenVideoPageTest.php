<?php

namespace Tests\Feature;

use Tests\TestCase;

class StartenVideoPageTest extends TestCase
{
    public function test_starten_page_shows_video_and_chapters(): void
    {
        $this->get('/starten')
            ->assertOk()
            ->assertSee('Zo start je met NEXA', false)
            ->assertSee('starten/video', false)
            ->assertDontSee('nexa-starten.nl.vtt', false)
            ->assertDontSee('<track', false)
            ->assertSee('Eerste keer inloggen', false)
            ->assertSee('Contract-app', false)
            ->assertSee('Direct naar het juiste onderwerp in de video gaan?', false)
            ->assertSee('Maak dan je keuze hieronder.', false)
            ->assertSee('04:00', false)
            ->assertSee('07:03', false)
            ->assertSee('07:31', false)
            ->assertSee('Open de admin', false);
    }

    public function test_starten_video_supports_http_range(): void
    {
        $this->get('/starten/video', ['Range' => 'bytes=0-1'])
            ->assertStatus(206)
            ->assertHeader('Content-Type', 'video/mp4')
            ->assertHeader('Accept-Ranges', 'bytes');
    }
}
