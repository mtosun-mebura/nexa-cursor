<?php

return [
    'planner_model' => env('AI_WEBSITE_PLANNER_MODEL', env('OPENAI_MODEL', 'gpt-4o-mini')),
    'content_model' => env('AI_WEBSITE_CONTENT_MODEL', env('OPENAI_MODEL', 'gpt-4o-mini')),
    'image_model' => env('AI_WEBSITE_IMAGE_MODEL', env('OPENAI_IMAGE_MODEL', 'dall-e-3')),
    'image_quality' => env('AI_WEBSITE_IMAGE_QUALITY', env('OPENAI_IMAGE_QUALITY', 'standard')),
    'crawl_max_pages' => (int) env('AI_WEBSITE_CRAWL_MAX_PAGES', 25),
    'phase' => 1,
    'prompt_versions' => [
        'website-brief' => 'website-brief:v1',
        'sitemap-homepage' => 'sitemap-homepage:v1',
    ],
    'goals' => [
        'leads' => 'Meer aanvragen',
        'calls' => 'Meer telefoongesprekken',
        'appointments' => 'Meer afspraken',
        'sales' => 'Producten verkopen',
        'inform' => 'Informatie geven',
        'recruitment' => 'Recruitment',
        'other' => 'Anders',
    ],
    'styles' => [
        'professional' => 'Professioneel',
        'modern' => 'Modern',
        'zakelijk' => 'Zakelijk',
        'vriendelijk' => 'Vriendelijk',
        'luxueus' => 'Luxueus',
        'minimalistisch' => 'Minimalistisch',
        'speels' => 'Speels',
        'technisch' => 'Technisch',
    ],
    'tones' => [
        'zakelijk' => 'Zakelijk',
        'persoonlijk' => 'Persoonlijk',
        'direct' => 'Direct',
        'deskundig' => 'Deskundig',
        'enthousiast' => 'Enthousiast',
    ],
];
