<?php

use App\Models\CompanyBillingProfile;
use App\Services\NexaPricingService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_billing_profiles')) {
            return;
        }

        if (! Schema::hasColumn('company_billing_profiles', 'agreed_monthly_amount')) {
            Schema::table('company_billing_profiles', function (Blueprint $table) {
                $table->decimal('agreed_monthly_amount', 10, 2)->nullable()->after('custom_monthly_amount');
            });
        }

        $pricing = app(NexaPricingService::class);
        CompanyBillingProfile::query()
            ->with(['package', 'company'])
            ->where('billing_mode', CompanyBillingProfile::MODE_PACKAGE)
            ->whereNull('agreed_monthly_amount')
            ->chunkById(100, function ($profiles) use ($pricing) {
                foreach ($profiles as $profile) {
                    $key = trim((string) ($profile->package?->package_key ?? $profile->company?->package_key ?? ''));
                    $amount = $key !== '' ? $pricing->monthlyAmountForKey($key) : null;
                    if ($amount === null && $profile->package) {
                        $amount = (float) $profile->package->monthly_amount;
                    }
                    if ($amount === null || $amount < 0) {
                        continue;
                    }
                    $profile->forceFill(['agreed_monthly_amount' => round((float) $amount, 2)])->save();
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('company_billing_profiles') && Schema::hasColumn('company_billing_profiles', 'agreed_monthly_amount')) {
            Schema::table('company_billing_profiles', function (Blueprint $table) {
                $table->dropColumn('agreed_monthly_amount');
            });
        }
    }
};
