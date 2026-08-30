<?php

namespace App\Services;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterProspect;
use App\Models\NewsletterSend;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class NewsletterSendService
{
    public function __construct(
        protected NewsletterHtmlCompiler $compiler,
        protected EnvService $env,
    ) {}

    /**
     * @param  list<string>  $provinces
     * @return Collection<int, NewsletterProspect>
     */
    public function recipients(array $provinces = [], ?int $campaignId = null): Collection
    {
        $query = NewsletterProspect::query()->subscribed()->orderBy('company_name');
        $provinces = array_values(array_filter($provinces));
        if ($provinces !== []) {
            $query->whereIn('province', $provinces);
        }
        if ($campaignId) {
            $query->whereDoesntHave('sends', function ($sends) use ($campaignId) {
                $sends->where('campaign_id', $campaignId)->where('status', NewsletterSend::STATUS_SENT);
            });
        }

        return $query->get();
    }

    /**
     * @param  list<string>  $provinces
     * @return array{sent: int, failed: int, skipped: int}
     */
    public function send(NewsletterCampaign $campaign, array $provinces = []): array
    {
        $this->env->applyPlatformMailConfigToRuntime();
        $from = $this->env->resolveMailFromHeaders(null, true);
        $fromAddress = $from['from_address'] ?: config('mail.from.address');
        $fromName = $from['from_name'] ?: (config('newsletter.from_name') ?: config('mail.from.name'));

        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $limit = (int) config('newsletter.max_send_per_run', 80);

        foreach ($this->recipients($provinces, $campaign->id) as $prospect) {
            if ($sent + $failed >= $limit) {
                $skipped++;

                continue;
            }
            if (! $prospect->isSubscribed()) {
                $skipped++;

                continue;
            }

            $html = $this->compiler->compileCampaign($campaign, $prospect);
            $text = $this->compiler->textVersion($html);
            $unsubscribe = $prospect->unsubscribeUrl();

            try {
                Mail::send([], [], function ($message) use ($prospect, $campaign, $html, $text, $fromAddress, $fromName, $unsubscribe) {
                    if ($fromAddress) {
                        $message->from($fromAddress, $fromName ?: $fromAddress);
                    }
                    $message->to($prospect->email, $prospect->greetingName())
                        ->subject((string) $campaign->subject);
                    $message->html($html);
                    $message->text($text);
                    $headers = $message->getHeaders();
                    $headers->addTextHeader('List-Unsubscribe', '<'.$unsubscribe.'>');
                    $headers->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
                });
                NewsletterSend::query()->updateOrCreate(
                    ['campaign_id' => $campaign->id, 'prospect_id' => $prospect->id],
                    ['status' => NewsletterSend::STATUS_SENT, 'sent_at' => now(), 'error_message' => null]
                );
                $sent++;
            } catch (\Throwable $e) {
                NewsletterSend::query()->updateOrCreate(
                    ['campaign_id' => $campaign->id, 'prospect_id' => $prospect->id],
                    ['status' => NewsletterSend::STATUS_FAILED, 'error_message' => $e->getMessage()]
                );
                $failed++;
            }
        }

        if ($sent > 0) {
            $campaign->status = NewsletterCampaign::STATUS_SENT;
            $campaign->last_sent_at = now();
            $campaign->save();
        }

        return compact('sent', 'failed', 'skipped');
    }
}
