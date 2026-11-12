<?php

namespace App\Http\Controllers;

use App\Services\PublicLlmsTxtBuilder;
use Illuminate\Http\Response;

/**
 * llms.txt — discoverability voor AI-crawlers (ChatGPT, Perplexity, Claude, Google AI).
 */
class LlmsTxtController extends Controller
{
    public function __construct(
        protected PublicLlmsTxtBuilder $builder,
    ) {}

    public function __invoke(): Response
    {
        $body = $this->builder->toText();

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
            'X-Robots-Tag' => 'all',
        ]);
    }
}
