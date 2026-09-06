<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiWebsiteGeneration extends Model
{
    public const STATUS_CONTEXT = 'context';

    public const STATUS_ANALYSIS = 'analysis';

    public const STATUS_WEBSITE_BRIEF = 'website_brief';

    public const STATUS_SITEMAP = 'sitemap';

    public const STATUS_PAGE_PLANS = 'page_plans';

    public const STATUS_CONTENT = 'content';

    public const STATUS_IMAGES = 'images';

    public const STATUS_BUILD = 'build';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'company_id',
        'user_id',
        'homepage_page_id',
        'status',
        'current_step',
        'source_type',
        'source_url',
        'source_context',
        'website_brief_json',
        'sitemap_json',
        'page_plans_json',
        'generation_settings_json',
        'source_extract_json',
        'prompt_versions',
        'used_openai',
        'error_message',
        'started_at',
        'completed_at',
        'failed_at',
    ];

    protected $casts = [
        'website_brief_json' => 'array',
        'sitemap_json' => 'array',
        'page_plans_json' => 'array',
        'generation_settings_json' => 'array',
        'source_extract_json' => 'array',
        'used_openai' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function homepagePage(): BelongsTo
    {
        return $this->belongsTo(WebsitePage::class, 'homepage_page_id');
    }

    public function markStep(string $step): void
    {
        $this->current_step = $step;
        $this->status = $step;
        if ($step === self::STATUS_COMPLETED) {
            $this->completed_at = now();
            $this->error_message = null;
        }
        $this->save();
    }

    public function markFailed(string $message): void
    {
        $this->status = self::STATUS_FAILED;
        $this->current_step = self::STATUS_FAILED;
        $this->error_message = mb_substr($message, 0, 2000);
        $this->failed_at = now();
        $this->save();
    }
}
