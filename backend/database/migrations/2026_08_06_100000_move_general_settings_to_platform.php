<?php

use App\Models\GeneralSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const PLATFORM_GENERAL_KEYS = [
        'logo',
        'logo_dark',
        'logo_mode',
        'logo_size',
        'favicon',
        'site_name',
        'site_description',
        'ai_chat_enabled',
        'ai_chat_nexa_taxi_webhook_url',
        'admin_footer_brand',
        'info_request_success_title',
        'info_request_success_subtitle',
        'info_request_success_footer',
        'info_request_success_texts_enabled',
        'info_request_success_icon',
        'info_request_success_icon_size',
        'info_request_success_image_size_percent',
        'info_request_success_image',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('general_settings')) {
            return;
        }

        foreach (self::PLATFORM_GENERAL_KEYS as $key) {
            $this->promoteKeyToPlatform($key);
        }

        // Dynamische AI-chat webhook keys
        $webhookKeys = DB::table('general_settings')
            ->where('key', 'like', 'ai_chat_%_webhook_url')
            ->distinct()
            ->pluck('key');

        foreach ($webhookKeys as $key) {
            $this->promoteKeyToPlatform((string) $key);
        }

        GeneralSetting::clearRequestCache();
    }

    private function promoteKeyToPlatform(string $key): void
    {
        $platform = DB::table('general_settings')
            ->where('key', $key)
            ->whereNull('company_id')
            ->orderByDesc('id')
            ->first();

        $platformValue = $platform ? trim((string) $platform->value) : '';

        if ($platformValue === '') {
            $tenant = DB::table('general_settings')
                ->where('key', $key)
                ->whereNotNull('company_id')
                ->where('value', '!=', '')
                ->orderByDesc('id')
                ->first();

            if ($tenant) {
                if ($platform) {
                    DB::table('general_settings')->where('id', $platform->id)->update([
                        'value' => (string) $tenant->value,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('general_settings')->insert([
                        'company_id' => null,
                        'key' => $key,
                        'value' => (string) $tenant->value,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        DB::table('general_settings')
            ->where('key', $key)
            ->whereNotNull('company_id')
            ->delete();
    }

    public function down(): void
    {
        // Platform-waarden blijven staan.
    }
};
