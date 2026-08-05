<?php

use App\Models\GeneralSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private const PLATFORM_WHATSAPP_KEYS = [
        'WHATSAPP_API_TOKEN',
        'WHATSAPP_PHONE_NUMBER_ID',
        'WHATSAPP_BUSINESS_ACCOUNT_ID',
        'WHATSAPP_API_VERSION',
        'WHATSAPP_WEBHOOK_VERIFY_TOKEN',
        'WHATSAPP_DEFAULT_MESSAGE',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('general_settings')) {
            return;
        }

        foreach (self::PLATFORM_WHATSAPP_KEYS as $key) {
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

            // Tenant-rijen voor platform-keys verwijderen (voorkom verwarring).
            DB::table('general_settings')
                ->where('key', $key)
                ->whereNotNull('company_id')
                ->delete();
        }

        GeneralSetting::clearRequestCache();
    }

    public function down(): void
    {
        // Niet terugzetten naar tenant — credentials blijven platform-breed.
    }
};
