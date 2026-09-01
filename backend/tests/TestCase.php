<?php

namespace Tests;

use App\Models\GeneralSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        GeneralSetting::clearRequestCache();
        config()->set('ai_chat.laravel_api_url', 'http://laravel.test');
        config()->set('services.openai.api_key', null);
    }
}
