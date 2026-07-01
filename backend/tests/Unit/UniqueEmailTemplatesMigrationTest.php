<?php

namespace Tests\Unit;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UniqueEmailTemplatesMigrationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function migration_deduplicates_global_templates_before_unique_index(): void
    {
        if (! Schema::hasTable('email_templates')) {
            $this->markTestSkipped('email_templates table required');
        }

        $migration = require database_path('migrations/2026_06_22_120000_unique_email_templates_per_type_and_company.php');
        $migration->down();

        DB::table('email_templates')->where('type', 'invoice')->whereNull('company_id')->delete();

        foreach (range(1, 3) as $i) {
            DB::table('email_templates')->insert([
                'type' => 'invoice',
                'company_id' => null,
                'name' => 'Factuur '.$i,
                'subject' => 'Onderwerp '.$i,
                'html_content' => '<p>'.$i.'</p>',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()->subMinutes(4 - $i),
            ]);
        }

        $companyId = Company::query()->create(['name' => 'Taxi BV'])->id;

        DB::table('email_templates')->insert([
            'type' => 'taxi_customer_login_code',
            'company_id' => $companyId,
            'name' => 'Login A',
            'subject' => 'A',
            'html_content' => '<p>A</p>',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now()->subMinute(),
        ]);
        DB::table('email_templates')->insert([
            'type' => 'taxi_customer_login_code',
            'company_id' => $companyId,
            'name' => 'Login B',
            'subject' => 'B',
            'html_content' => '<p>B</p>',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration->up();

        $this->assertSame(1, DB::table('email_templates')->where('type', 'invoice')->whereNull('company_id')->count());
        $this->assertSame(1, DB::table('email_templates')
            ->where('type', 'taxi_customer_login_code')
            ->where('company_id', $companyId)
            ->count());

        $keptInvoice = DB::table('email_templates')->where('type', 'invoice')->whereNull('company_id')->value('subject');
        $this->assertSame('Onderwerp 3', $keptInvoice);
    }
}
