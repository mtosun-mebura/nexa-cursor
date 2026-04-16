<?php

declare(strict_types=1);

namespace App\Database;

use App\Models\Interview;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Geconsolideerde pre-2026-baseline (voorheen migrations_archive/pre-2026-baseline).
 * Gegenereerd door scripts/build-pre2026-baseline.php — niet handmatig bewerken.
 */
final class Pre2026Baseline
{
    /**
     * Zelfde volgorde als de oude bundelmigratie: alle bestanden globaal gesorteerd op bestandsnaam.
     *
     * @return list<array{set: string, basename: string, run: callable}>
     */
    public static function steps(): array
    {
        return [
            [
                'set' => 'shared',
                'basename' => '0001_01_01_000000_create_users_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('users', function (Blueprint $table) {
                                $table->id();
                                $table->string('first_name')->nullable();
                                $table->string('middle_name')->nullable();
                                $table->string('last_name')->nullable();
                                $table->string('email')->unique();
                                $table->date('date_of_birth')->nullable();
                                $table->timestamp('email_verified_at')->nullable();
                                $table->timestamp('phone_verified_at')->nullable();
                                $table->string('password');
                                $table->foreignId('company_id')->nullable();
                                $table->rememberToken();
                                $table->timestamps();
                            });

                            Schema::create('password_reset_tokens', function (Blueprint $table) {
                                $table->string('email')->primary();
                                $table->string('token');
                                $table->timestamp('created_at')->nullable();
                            });

                            Schema::create('sessions', function (Blueprint $table) {
                                $table->string('id')->primary();
                                $table->foreignId('user_id')->nullable()->index();
                                $table->string('ip_address', 45)->nullable();
                                $table->text('user_agent')->nullable();
                                $table->longText('payload');
                                $table->integer('last_activity')->index();
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('users');
                            Schema::dropIfExists('password_reset_tokens');
                            Schema::dropIfExists('sessions');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '0001_01_01_000001_create_cache_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('cache', function (Blueprint $table) {
                                $table->string('key')->primary();
                                $table->mediumText('value');
                                $table->integer('expiration');
                            });

                            Schema::create('cache_locks', function (Blueprint $table) {
                                $table->string('key')->primary();
                                $table->string('owner');
                                $table->integer('expiration');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('cache');
                            Schema::dropIfExists('cache_locks');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '0001_01_01_000002_create_jobs_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('jobs', function (Blueprint $table) {
                                $table->id();
                                $table->string('queue')->index();
                                $table->longText('payload');
                                $table->unsignedTinyInteger('attempts');
                                $table->unsignedInteger('reserved_at')->nullable();
                                $table->unsignedInteger('available_at');
                                $table->unsignedInteger('created_at');
                            });

                            Schema::create('job_batches', function (Blueprint $table) {
                                $table->string('id')->primary();
                                $table->string('name');
                                $table->integer('total_jobs');
                                $table->integer('pending_jobs');
                                $table->integer('failed_jobs');
                                $table->longText('failed_job_ids');
                                $table->mediumText('options')->nullable();
                                $table->integer('cancelled_at')->nullable();
                                $table->integer('created_at');
                                $table->integer('finished_at')->nullable();
                            });

                            Schema::create('failed_jobs', function (Blueprint $table) {
                                $table->id();
                                $table->string('uuid')->unique();
                                $table->text('connection');
                                $table->text('queue');
                                $table->longText('payload');
                                $table->longText('exception');
                                $table->timestamp('failed_at')->useCurrent();
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('jobs');
                            Schema::dropIfExists('job_batches');
                            Schema::dropIfExists('failed_jobs');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2024_01_15_000000_create_payment_providers_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::create('payment_providers', function (Blueprint $table) {
                                $table->id();
                                $table->string('name');
                                $table->string('provider_type'); // mollie, stripe, etc.
                                $table->boolean('is_active')->default(false);
                                $table->json('config')->nullable(); // API keys, tokens, settings
                                $table->timestamps();
                                $table->index(['provider_type']);
                            });
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('payment_providers');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'core',
                'basename' => '2025_01_27_000001_create_modules_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::create('modules', function (Blueprint $table) {
                                $table->id();
                                $table->string('name')->unique();
                                $table->string('display_name');
                                $table->string('version');
                                $table->text('description')->nullable();
                                $table->string('icon')->nullable();
                                $table->boolean('installed')->default(false);
                                $table->boolean('active')->default(false);
                                $table->json('configuration')->nullable();
                                $table->timestamps();
                            });
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('modules');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_08_19_193520_create_personal_access_tokens_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('personal_access_tokens', function (Blueprint $table) {
                                $table->id();
                                $table->morphs('tokenable');
                                $table->text('name');
                                $table->string('token', 64)->unique();
                                $table->text('abilities')->nullable();
                                $table->timestamp('last_used_at')->nullable();
                                $table->timestamp('expires_at')->nullable()->index();
                                $table->timestamps();
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('personal_access_tokens');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_08_19_193522_create_permission_tables.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            $teams = config('permission.teams');
                            $tableNames = config('permission.table_names');
                            $columnNames = config('permission.column_names');
                            $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
                            $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

                            throw_if(empty($tableNames), new \Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.'));
                            throw_if($teams && empty($columnNames['team_foreign_key'] ?? null), new \Exception('Error: team_foreign_key on config/permission.php not loaded. Run [php artisan config:clear] and try again.'));

                            Schema::create($tableNames['permissions'], static function (Blueprint $table) {
                                // $table->engine('InnoDB');
                                $table->bigIncrements('id'); // permission id
                                $table->string('name');       // For MyISAM use string('name', 225); // (or 166 for InnoDB with Redundant/Compact row format)
                                $table->string('guard_name'); // For MyISAM use string('guard_name', 25);
                                $table->timestamps();

                                $table->unique(['name', 'guard_name']);
                            });

                            Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
                                // $table->engine('InnoDB');
                                $table->bigIncrements('id'); // role id
                                if ($teams || config('permission.testing')) { // permission.testing is a fix for sqlite testing
                                    $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                                    $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
                                }
                                $table->string('name');       // For MyISAM use string('name', 225); // (or 166 for InnoDB with Redundant/Compact row format)
                                $table->string('guard_name'); // For MyISAM use string('guard_name', 25);
                                $table->timestamps();
                                if ($teams || config('permission.testing')) {
                                    $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
                                } else {
                                    $table->unique(['name', 'guard_name']);
                                }
                            });

                            Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
                                $table->unsignedBigInteger($pivotPermission);

                                $table->string('model_type');
                                $table->unsignedBigInteger($columnNames['model_morph_key']);
                                $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

                                $table->foreign($pivotPermission)
                                    ->references('id') // permission id
                                    ->on($tableNames['permissions'])
                                    ->onDelete('cascade');
                                if ($teams) {
                                    $table->unsignedBigInteger($columnNames['team_foreign_key']);
                                    $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');

                                    $table->primary([$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                                        'model_has_permissions_permission_model_type_primary');
                                } else {
                                    $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                                        'model_has_permissions_permission_model_type_primary');
                                }

                            });

                            Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
                                $table->unsignedBigInteger($pivotRole);

                                $table->string('model_type');
                                $table->unsignedBigInteger($columnNames['model_morph_key']);
                                $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

                                $table->foreign($pivotRole)
                                    ->references('id') // role id
                                    ->on($tableNames['roles'])
                                    ->onDelete('cascade');
                                if ($teams) {
                                    $table->unsignedBigInteger($columnNames['team_foreign_key']);
                                    $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');

                                    $table->primary([$columnNames['team_foreign_key'], $pivotRole, $columnNames['model_morph_key'], 'model_type'],
                                        'model_has_roles_role_model_type_primary');
                                } else {
                                    $table->primary([$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                                        'model_has_roles_role_model_type_primary');
                                }
                            });

                            Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
                                $table->unsignedBigInteger($pivotPermission);
                                $table->unsignedBigInteger($pivotRole);

                                $table->foreign($pivotPermission)
                                    ->references('id') // permission id
                                    ->on($tableNames['permissions'])
                                    ->onDelete('cascade');

                                $table->foreign($pivotRole)
                                    ->references('id') // role id
                                    ->on($tableNames['roles'])
                                    ->onDelete('cascade');

                                $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
                            });

                            app('cache')
                                ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
                                ->forget(config('permission.cache.key'));
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            $tableNames = config('permission.table_names');

                            if (empty($tableNames)) {
                                throw new \Exception('Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');
                            }

                            Schema::drop($tableNames['role_has_permissions']);
                            Schema::drop($tableNames['model_has_roles']);
                            Schema::drop($tableNames['model_has_permissions']);
                            Schema::drop($tableNames['roles']);
                            Schema::drop($tableNames['permissions']);
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_08_19_200000_create_companies_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::create('companies', function (Blueprint $table) {
                                $table->id();
                                $table->string('name');
                                $table->string('slug', 100)->unique();
                                $table->text('description')->nullable();
                                $table->string('department', 255)->nullable();
                                // Address
                                $table->string('street', 255)->nullable();
                                $table->string('house_number', 10)->nullable();
                                $table->string('house_number_extension', 10)->nullable();
                                $table->string('postal_code', 20)->nullable();
                                $table->string('city', 100)->nullable();
                                $table->string('country', 100)->nullable();
                                // Contact
                                $table->string('website', 255)->nullable();
                                $table->string('email', 255)->nullable();
                                $table->string('phone', 50)->nullable();
                                $table->string('contact_first_name', 100)->nullable();
                                $table->string('contact_middle_name', 100)->nullable();
                                $table->string('contact_last_name', 100)->nullable();
                                $table->string('contact_email', 255)->nullable();
                                // Status
                                $table->boolean('is_active')->default(true);
                                $table->timestamps();
                            });
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('companies');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_08_19_200100_add_company_fk_to_users_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
                            });
                        }

                        public function down(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->dropForeign(['company_id']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_08_19_200200_create_categories_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::create('categories', function (Blueprint $table) {
                                $table->id();
                                $table->string('name', 100);
                                $table->string('slug', 100)->unique();
                                $table->timestamps();
                            });
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('categories');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_08_19_200300_create_job_configurations_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::create('job_configurations', function (Blueprint $table) {
                                $table->id();
                                $table->string('type', 50);
                                $table->string('value', 100);
                                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                                $table->timestamps();
                            });
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('job_configurations');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_08_19_200400_create_vacancies_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::create('vacancies', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                                $table->string('title');
                                $table->string('location')->nullable();
                                $table->string('employment_type', 50)->nullable();
                                $table->text('description')->nullable();
                                $table->text('requirements')->nullable();
                                $table->text('offer')->nullable();
                                $table->text('application_instructions')->nullable();
                                $table->unsignedBigInteger('category_id')->nullable()->index();
                                $table->string('reference_number', 100)->nullable();
                                $table->string('logo', 255)->nullable();
                                $table->string('salary_range', 100)->nullable();
                                $table->date('start_date')->nullable();
                                $table->string('working_hours', 50)->nullable();
                                $table->boolean('travel_expenses')->default(false);
                                $table->boolean('remote_work')->default(false);
                                $table->string('status', 20)->default('Open');
                                $table->string('language', 20)->default('Nederlands');
                                $table->timestamp('publication_date')->nullable();
                                $table->timestamp('closing_date')->nullable();
                                $table->string('meta_title', 255)->nullable();
                                $table->text('meta_description')->nullable();
                                $table->text('meta_keywords')->nullable();
                                $table->timestamps();
                            });
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('vacancies');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_08_19_200500_create_matches_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::create('matches', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                                $table->foreignId('vacancy_id')->constrained('vacancies')->cascadeOnDelete();
                                $table->decimal('score', 5, 2)->nullable();
                                $table->string('status', 20)->default('matched');
                                $table->text('ai_feedback')->nullable();
                                $table->timestamps();
                            });
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('matches');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_08_19_200600_create_interviews_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::create('interviews', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
                                $table->foreignId('company_id')->constrained('companies');
                                $table->timestamp('scheduled_at')->nullable();
                                $table->string('location', 255)->nullable();
                                $table->string('video_chat_url', 255)->nullable();
                                $table->text('notes')->nullable();
                                $table->timestamps();
                            });
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('interviews');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_08_19_200700_create_notifications_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::create('notifications', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                                $table->text('content');
                                $table->boolean('is_read')->default(false);
                                $table->timestamps();
                            });
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('notifications');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_08_19_200800_create_email_templates_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::create('email_templates', function (Blueprint $table) {
                                $table->id();
                                $table->string('name', 100);
                                $table->string('subject', 255);
                                $table->text('body');
                                $table->string('language', 20)->default('Nederlands');
                                $table->foreignId('company_id')->nullable()->constrained('companies');
                                $table->timestamps();
                            });
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('email_templates');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_08_19_200900_create_chat_history_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::create('chat_history', function (Blueprint $table) {
                                $table->id();
                                $table->string('session_id', 50);
                                $table->string('role', 20);
                                $table->text('message');
                                $table->timestamps();
                            });
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('chat_history');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_08_19_201240_make_company_id_nullable_in_permission_tables.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            // Drop primary keys first
                            Schema::table('model_has_roles', function (Blueprint $table) {
                                $table->dropPrimary('model_has_roles_role_model_type_primary');
                            });

                            Schema::table('model_has_permissions', function (Blueprint $table) {
                                $table->dropPrimary('model_has_permissions_permission_model_type_primary');
                            });

                            // Make company_id nullable
                            Schema::table('roles', function (Blueprint $table) {
                                $table->unsignedBigInteger('company_id')->nullable()->change();
                            });

                            Schema::table('model_has_roles', function (Blueprint $table) {
                                $table->unsignedBigInteger('company_id')->nullable()->change();
                            });

                            Schema::table('model_has_permissions', function (Blueprint $table) {
                                $table->unsignedBigInteger('company_id')->nullable()->change();
                            });

                            // Recreate primary keys with nullable company_id
                            Schema::table('model_has_roles', function (Blueprint $table) {
                                $table->primary(['role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
                            });

                            Schema::table('model_has_permissions', function (Blueprint $table) {
                                $table->primary(['permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('roles', function (Blueprint $table) {
                                $table->unsignedBigInteger('company_id')->nullable(false)->change();
                            });

                            Schema::table('model_has_roles', function (Blueprint $table) {
                                $table->unsignedBigInteger('company_id')->nullable(false)->change();
                            });

                            Schema::table('model_has_permissions', function (Blueprint $table) {
                                $table->unsignedBigInteger('company_id')->nullable(false)->change();
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_08_24_204643_add_industry_and_kvk_to_companies_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('companies', function (Blueprint $table) {
                                $table->string('industry', 255)->nullable()->after('department');
                                $table->string('kvk_number', 20)->nullable()->after('industry');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('companies', function (Blueprint $table) {
                                $table->dropColumn(['industry', 'kvk_number']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_08_24_204900_add_fields_to_categories_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('categories', function (Blueprint $table) {
                                $table->text('description')->nullable()->after('slug');
                                $table->string('color', 7)->nullable()->after('description');
                                $table->string('icon', 255)->nullable()->after('color');
                                $table->boolean('is_active')->default(true)->after('icon');
                                $table->integer('sort_order')->default(0)->after('is_active');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('categories', function (Blueprint $table) {
                                $table->dropColumn(['description', 'color', 'icon', 'is_active', 'sort_order']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_08_24_205425_update_email_templates_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('email_templates', function (Blueprint $table) {
                                // Rename body to html_content
                                $table->renameColumn('body', 'html_content');

                                // Add new columns
                                $table->string('type', 50)->nullable()->after('subject');
                                $table->text('text_content')->nullable()->after('html_content');
                                $table->text('description')->nullable()->after('text_content');
                                $table->boolean('is_active')->default(true)->after('description');

                                // Drop language column as it's not needed
                                $table->dropColumn('language');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('email_templates', function (Blueprint $table) {
                                // Revert changes
                                $table->renameColumn('html_content', 'body');
                                $table->string('language', 20)->default('Nederlands')->after('body');
                                $table->dropColumn(['type', 'text_content', 'description', 'is_active']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_08_24_205826_update_matches_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('matches', function (Blueprint $table) {
                                // Rename score to match_score
                                $table->renameColumn('score', 'match_score');

                                // Add new columns
                                $table->string('ai_recommendation', 50)->nullable()->after('status');
                                $table->date('application_date')->nullable()->after('ai_recommendation');
                                $table->text('notes')->nullable()->after('application_date');

                                // Rename ai_feedback to ai_analysis
                                $table->renameColumn('ai_feedback', 'ai_analysis');

                                // Update status column to allow more values
                                $table->string('status', 50)->default('pending')->change();
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('matches', function (Blueprint $table) {
                                // Revert changes
                                $table->renameColumn('match_score', 'score');
                                $table->renameColumn('ai_analysis', 'ai_feedback');
                                $table->string('status', 20)->default('matched')->change();
                                $table->dropColumn(['ai_recommendation', 'application_date', 'notes']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_08_26_174919_update_interviews_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('interviews', function (Blueprint $table) {
                                // Add new columns
                                $table->string('type', 50)->nullable()->after('match_id');
                                $table->integer('duration')->nullable()->after('scheduled_at');
                                $table->string('status', 50)->default('scheduled')->after('duration');
                                $table->string('interviewer_name', 255)->nullable()->after('location');
                                $table->string('interviewer_email', 255)->nullable()->after('interviewer_name');
                                $table->text('feedback')->nullable()->after('notes');

                                // Drop video_chat_url as it's replaced by location
                                $table->dropColumn('video_chat_url');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('interviews', function (Blueprint $table) {
                                // Revert changes
                                $table->string('video_chat_url', 255)->nullable()->after('location');
                                $table->dropColumn(['type', 'duration', 'status', 'interviewer_name', 'interviewer_email', 'feedback']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_08_26_175228_update_notifications_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('notifications', function (Blueprint $table) {
                                // Rename content to message
                                $table->renameColumn('content', 'message');

                                // Add new columns
                                $table->string('type', 50)->nullable()->after('user_id');
                                $table->string('title', 255)->nullable()->after('type');
                                $table->string('priority', 20)->default('normal')->after('message');
                                $table->timestamp('read_at')->nullable()->after('priority');
                                $table->string('action_url', 500)->nullable()->after('read_at');
                                $table->text('data')->nullable()->after('action_url');
                                $table->timestamp('scheduled_at')->nullable()->after('data');

                                // Drop is_read as it's replaced by read_at
                                $table->dropColumn('is_read');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('notifications', function (Blueprint $table) {
                                // Revert changes
                                $table->renameColumn('message', 'content');
                                $table->boolean('is_read')->default(false)->after('content');
                                $table->dropColumn(['type', 'title', 'priority', 'read_at', 'action_url', 'data', 'scheduled_at']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_08_26_184908_add_phone_to_users_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->string('phone')->nullable()->after('email');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->dropColumn('phone');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_08_27_192621_add_description_and_group_to_permissions_and_roles_tables.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            $tableNames = config('permission.table_names');

                            // Add description and group to permissions table
                            Schema::table($tableNames['permissions'], function (Blueprint $table) {
                                $table->text('description')->nullable()->after('guard_name');
                                $table->string('group', 100)->nullable()->after('description');
                            });

                            // Add description to roles table
                            Schema::table($tableNames['roles'], function (Blueprint $table) {
                                $table->text('description')->nullable()->after('guard_name');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            $tableNames = config('permission.table_names');

                            // Remove description and group from permissions table
                            Schema::table($tableNames['permissions'], function (Blueprint $table) {
                                $table->dropColumn(['description', 'group']);
                            });

                            // Remove description from roles table
                            Schema::table($tableNames['roles'], function (Blueprint $table) {
                                $table->dropColumn('description');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_08_27_211014_add_company_id_to_notifications_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('notifications', function (Blueprint $table) {
                                $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('notifications', function (Blueprint $table) {
                                $table->dropForeign(['company_id']);
                                $table->dropColumn('company_id');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_09_01_192000_create_candidates_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('candidates', function (Blueprint $table) {
                                $table->id();
                                $table->string('reference_number')->unique();
                                $table->string('first_name');
                                $table->string('last_name');
                                $table->string('email')->unique();
                                $table->string('phone')->nullable();
                                $table->date('date_of_birth')->nullable();
                                $table->text('address')->nullable();
                                $table->string('city')->nullable();
                                $table->string('postal_code')->nullable();
                                $table->string('country')->default('Nederland');
                                $table->string('cv_path')->nullable();
                                $table->text('cover_letter')->nullable();
                                $table->string('linkedin_url')->nullable();
                                $table->string('website_url')->nullable();
                                $table->integer('experience_years')->default(0);
                                $table->enum('education_level', [
                                    'high_school',
                                    'vocational',
                                    'bachelor',
                                    'master',
                                    'phd',
                                ])->nullable();
                                $table->string('current_position')->nullable();
                                $table->string('desired_position')->nullable();
                                $table->decimal('salary_expectation', 10, 2)->nullable();
                                $table->enum('availability', [
                                    'immediate',
                                    '2_weeks',
                                    '1_month',
                                    '3_months',
                                    'custom',
                                ])->default('immediate');
                                $table->enum('preferred_work_type', [
                                    'full_time',
                                    'part_time',
                                    'freelance',
                                    'contract',
                                    'hybrid',
                                    'remote',
                                ])->default('full_time');
                                $table->string('preferred_location')->nullable();
                                $table->json('skills')->nullable();
                                $table->json('languages')->nullable();
                                $table->enum('status', [
                                    'pending',
                                    'active',
                                    'rejected',
                                    'hired',
                                ])->default('pending');
                                $table->text('notes')->nullable();
                                $table->string('source')->default('website');
                                $table->boolean('consent_gdpr')->default(false);
                                $table->boolean('consent_marketing')->default(false);
                                $table->timestamps();

                                $table->index(['status', 'created_at']);
                                $table->index(['email']);
                                $table->index(['experience_years']);
                                $table->index(['education_level']);
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('candidates');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_09_17_184639_add_frontend_fields_to_vacancies_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('vacancies', function (Blueprint $table) {
                                $table->boolean('is_active')->default(true)->after('status');
                                $table->timestamp('published_at')->nullable()->after('is_active');
                                $table->integer('salary_min')->nullable()->after('salary_range');
                                $table->integer('salary_max')->nullable()->after('salary_min');
                                $table->string('experience_level', 50)->nullable()->after('employment_type');
                                $table->text('benefits')->nullable()->after('offer');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('vacancies', function (Blueprint $table) {
                                $table->dropColumn(['is_active', 'published_at', 'salary_min', 'salary_max', 'experience_level', 'benefits']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_09_22_184508_add_is_intermediary_to_companies_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('companies', function (Blueprint $table) {
                                $table->boolean('is_intermediary')->default(false)->after('description');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('companies', function (Blueprint $table) {
                                $table->dropColumn('is_intermediary');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_09_24_193510_add_geo_coordinates_to_vacancies_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('vacancies', function (Blueprint $table) {
                                $table->decimal('latitude', 10, 8)->nullable()->after('location');
                                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('vacancies', function (Blueprint $table) {
                                $table->dropColumn(['latitude', 'longitude']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_09_24_194144_add_geo_coordinates_to_candidates_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('candidates', function (Blueprint $table) {
                                $table->decimal('latitude', 10, 8)->nullable()->after('location');
                                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('candidates', function (Blueprint $table) {
                                $table->dropColumn(['latitude', 'longitude']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_09_24_201722_create_favorites_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('favorites', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                                $table->foreignId('vacancy_id')->constrained()->onDelete('cascade');
                                $table->timestamps();

                                // Ensure a user can only favorite a vacancy once
                                $table->unique(['user_id', 'vacancy_id']);
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('favorites');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_09_24_203359_add_profile_fields_to_users_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->string('location')->nullable();
                                $table->text('bio')->nullable();
                                $table->string('photo')->nullable();
                            });
                        }

                        public function down(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->dropColumn(['location', 'bio', 'photo']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_09_24_203404_create_skills_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::create('skills', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                                $table->string('name');
                                $table->enum('type', ['technical', 'soft']);
                                $table->timestamps();
                            });
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('skills');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_09_24_203411_create_experiences_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::create('experiences', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                                $table->string('title');
                                $table->string('company');
                                $table->date('start_date');
                                $table->date('end_date')->nullable();
                                $table->text('description')->nullable();
                                $table->boolean('current')->default(false);
                                $table->timestamps();
                            });
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('experiences');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_09_25_195948_add_photo_blob_to_users_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->longText('photo_blob')->nullable()->after('photo');
                                $table->string('photo_mime_type')->nullable()->after('photo_blob');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->dropColumn(['photo_blob', 'photo_mime_type']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_09_30_194825_add_birth_date_to_users_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->date('birth_date')->nullable()->after('email');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->dropColumn('birth_date');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_10_03_114824_add_cv_path_to_users_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->string('cv_path')->nullable()->after('photo');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->dropColumn('cv_path');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_10_03_115637_add_cv_original_name_to_users_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->string('cv_original_name')->nullable()->after('cv_path');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->dropColumn('cv_original_name');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_10_03_131227_create_cv_files_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('cv_files', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                                $table->string('original_name');
                                $table->string('file_path');
                                $table->string('file_type');
                                $table->bigInteger('file_size');
                                $table->timestamps();
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('cv_files');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_10_05_191912_add_job_preferences_to_users_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->string('preferred_location')->nullable();
                                $table->integer('max_distance')->nullable();
                                $table->string('contract_type')->nullable();
                                $table->string('work_hours')->nullable();
                                $table->integer('min_salary')->nullable();
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->dropColumn(['preferred_location', 'max_distance', 'contract_type', 'work_hours', 'min_salary']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_10_05_192247_change_min_salary_to_integer_in_users_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->integer('min_salary')->nullable()->change();
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->decimal('min_salary', 10, 2)->nullable()->change();
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_10_05_192618_add_notification_preferences_to_users_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->boolean('email_notifications')->default(true);
                                $table->boolean('sms_notifications')->default(false);
                                $table->boolean('push_notifications')->default(true);
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->dropColumn(['email_notifications', 'sms_notifications', 'push_notifications']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_10_05_194010_add_privacy_preferences_to_users_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->boolean('profile_visible')->default(true)->after('push_notifications');
                                $table->boolean('cv_downloadable')->default(true)->after('profile_visible');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->dropColumn(['profile_visible', 'cv_downloadable']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_11_06_204132_add_n8n_fields_to_candidates_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('candidates', function (Blueprint $table) {
                                $table->boolean('work_permission_nl')->nullable()->after('postal_code');
                                $table->string('availability_type')->nullable()->after('availability'); // 'per_direct' | 'datum' | 'opzegtermijn'
                                $table->date('availability_date')->nullable()->after('availability_type');
                                $table->integer('notice_weeks')->nullable()->after('availability_date');
                                $table->integer('hours_per_week')->nullable()->after('notice_weeks');
                                $table->string('work_mode')->nullable()->after('preferred_work_type'); // 'locatie' | 'hybride' | 'remote'
                                $table->json('primary_titles')->nullable()->after('desired_position'); // functietitels voorkeur
                                $table->json('sectors')->nullable()->after('primary_titles'); // sector/branche
                                $table->integer('travel_radius_km')->nullable()->after('preferred_location');
                                $table->boolean('drivers_license')->nullable()->after('travel_radius_km');
                                $table->boolean('notify_new_roles')->default(false)->after('consent_marketing');
                                $table->integer('consent_retention_months')->nullable()->after('notify_new_roles');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('candidates', function (Blueprint $table) {
                                $table->dropColumn([
                                    'work_permission_nl',
                                    'availability_type',
                                    'availability_date',
                                    'notice_weeks',
                                    'hours_per_week',
                                    'work_mode',
                                    'primary_titles',
                                    'sectors',
                                    'travel_radius_km',
                                    'drivers_license',
                                    'notify_new_roles',
                                    'consent_retention_months',
                                ]);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_11_06_204132_create_candidate_embeddings_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('candidate_embeddings', function (Blueprint $table) {
                                $table->foreignId('candidate_id')->primary()->constrained('candidates')->cascadeOnDelete();
                                $table->string('model')->notNull();
                                // Store as JSON for now - can be converted to vector type later when pgvector is installed
                                $table->json('embedding')->nullable();
                            });

                            // Try to enable pgvector extension if using PostgreSQL (gracefully fail if not available)
                            if (config('database.default') === 'pgsql') {
                                try {
                                    DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
                                    // Try to convert JSON column to vector type if extension is available
                                    try {
                                        DB::statement('ALTER TABLE candidate_embeddings DROP COLUMN embedding');
                                        DB::statement('ALTER TABLE candidate_embeddings ADD COLUMN embedding vector(1536)');
                                    } catch (\Exception $e) {
                                        // If conversion fails, keep JSON column
                                    }
                                } catch (\Exception $e) {
                                    // pgvector extension not available - continue with JSON storage
                                }
                            }
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('candidate_embeddings');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_11_06_204132_create_candidate_texts_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('candidate_texts', function (Blueprint $table) {
                                $table->foreignId('candidate_id')->primary()->constrained('candidates')->cascadeOnDelete();
                                $table->text('last_responsibilities')->nullable(); // Q14
                                $table->json('top_skills')->nullable(); // Q15
                                $table->json('tools_tech')->nullable(); // Q16
                                $table->text('employer_values')->nullable(); // Q21
                                $table->text('best_result')->nullable(); // Q22
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('candidate_texts');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_11_06_204133_add_n8n_fields_to_vacancies_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('vacancies', function (Blueprint $table) {
                                $table->json('required_skills')->nullable()->after('description');
                                $table->json('nice_to_have')->nullable()->after('required_skills');
                                $table->json('tools_tech')->nullable()->after('nice_to_have');
                                $table->string('sector')->nullable()->after('category_id');
                                $table->string('location_city')->nullable()->after('location');
                                $table->string('work_mode')->nullable()->after('remote_work'); // 'locatie' | 'hybride' | 'remote'
                                $table->integer('min_experience')->nullable()->after('experience_level');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('vacancies', function (Blueprint $table) {
                                $table->dropColumn([
                                    'required_skills',
                                    'nice_to_have',
                                    'tools_tech',
                                    'sector',
                                    'location_city',
                                    'work_mode',
                                    'min_experience',
                                ]);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_11_06_204133_create_applications_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('applications', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
                                $table->foreignId('vacancy_id')->constrained('vacancies')->cascadeOnDelete();
                                $table->string('status')->default('initiated'); // initiated|submitted|interview|offer|rejected
                                $table->timestamp('created_at')->useCurrent();

                                $table->index(['candidate_id', 'created_at']);
                                $table->index(['vacancy_id', 'created_at']);
                                $table->index('status');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('applications');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_11_06_204133_create_vacancy_embeddings_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('vacancy_embeddings', function (Blueprint $table) {
                                $table->foreignId('vacancy_id')->primary()->constrained('vacancies')->cascadeOnDelete();
                                $table->string('model')->notNull();
                                // Store as JSON for now - can be converted to vector type later when pgvector is installed
                                $table->json('embedding')->nullable();
                            });

                            // Try to enable pgvector extension if using PostgreSQL (gracefully fail if not available)
                            if (config('database.default') === 'pgsql') {
                                try {
                                    DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
                                    // Try to convert JSON column to vector type if extension is available
                                    try {
                                        DB::statement('ALTER TABLE vacancy_embeddings DROP COLUMN embedding');
                                        DB::statement('ALTER TABLE vacancy_embeddings ADD COLUMN embedding vector(1536)');
                                    } catch (\Exception $e) {
                                        // If conversion fails, keep JSON column
                                    }
                                } catch (\Exception $e) {
                                    // pgvector extension not available - continue with JSON storage
                                }
                            }
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('vacancy_embeddings');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_11_06_204346_create_candidate_with_texts_view.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            // Create view that joins candidates with candidate_texts
                            // SQLite doesn't support CREATE OR REPLACE VIEW, so we drop first if exists
                            if (DB::getDriverName() === 'sqlite') {
                                DB::statement('DROP VIEW IF EXISTS candidate_with_texts_view');
                            }

                            DB::statement('
            CREATE VIEW candidate_with_texts_view AS
            SELECT 
                c.*,
                ct.last_responsibilities,
                ct.top_skills,
                ct.tools_tech,
                ct.employer_values,
                ct.best_result
            FROM candidates c
            LEFT JOIN candidate_texts ct ON ct.candidate_id = c.id
        ');
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            DB::statement('DROP VIEW IF EXISTS candidate_with_texts_view');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_11_06_204346_create_ranked_vacancies_view.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            // Note: This view is parameterized, so we'll create it as a function instead
                            // The actual ranking will be done in the MatchService using raw queries
                            // This migration is kept for potential future use or documentation
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            // Nothing to drop as we're not creating a static view
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_11_06_213544_create_upsert_candidate_function.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            // Only create function if using PostgreSQL
                            if (config('database.default') !== 'pgsql') {
                                return;
                            }

                            DB::unprepared("
            CREATE OR REPLACE FUNCTION upsert_candidate(
                p_session_id TEXT,
                p_field      TEXT,
                p_answer     TEXT,
                p_step       INT
            ) RETURNS TABLE(done BOOLEAN, candidate JSONB) AS \$\$
            DECLARE 
                v_cid INT;
                v_array_text TEXT[];
            BEGIN
                -- 1) sessie → kandidaat (één kandidaat per sessie)
                -- Use email as session identifier
                IF NOT EXISTS (SELECT 1 FROM candidates WHERE email = p_session_id) THEN
                    INSERT INTO candidates (email, created_at, updated_at) 
                    VALUES (p_session_id, NOW(), NOW()) 
                    RETURNING id INTO v_cid;
                    
                    -- Ensure candidate_texts record exists
                    INSERT INTO candidate_texts (candidate_id) 
                    VALUES (v_cid)
                    ON CONFLICT (candidate_id) DO NOTHING;
                ELSE
                    SELECT id INTO v_cid FROM candidates WHERE email = p_session_id;
                END IF;

                -- 2) map veldnamen → kolommen
                IF p_field = 'first_name' THEN 
                    UPDATE candidates SET first_name = p_answer, updated_at = NOW() WHERE id = v_cid;
                    
                ELSIF p_field = 'last_name' THEN 
                    UPDATE candidates SET last_name = p_answer, updated_at = NOW() WHERE id = v_cid;
                    
                ELSIF p_field = 'birth_date' OR p_field = 'date_of_birth' THEN 
                    UPDATE candidates 
                    SET date_of_birth = CASE 
                        WHEN p_answer ~ '^\\d{4}-\\d{2}-\\d{2}' THEN p_answer::date
                        WHEN p_answer ~ '^\\d{2}-\\d{2}-\\d{4}' THEN to_date(p_answer, 'DD-MM-YYYY')
                        ELSE NULL
                    END, 
                    updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'email' THEN 
                    UPDATE candidates SET email = p_answer, updated_at = NOW() WHERE id = v_cid;
                    
                ELSIF p_field = 'phone' THEN 
                    UPDATE candidates SET phone = p_answer, updated_at = NOW() WHERE id = v_cid;
                    
                ELSIF p_field = 'city' THEN 
                    UPDATE candidates SET city = p_answer, updated_at = NOW() WHERE id = v_cid;
                    
                ELSIF p_field = 'postal_code' THEN 
                    UPDATE candidates SET postal_code = p_answer, updated_at = NOW() WHERE id = v_cid;
                    
                ELSIF p_field = 'work_permission_nl' THEN 
                    UPDATE candidates 
                    SET work_permission_nl = (LOWER(p_answer) IN ('ja', 'yes', 'true', '1')), 
                        updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'availability' THEN
                    IF LOWER(p_answer) LIKE '%direct%' OR LOWER(p_answer) LIKE '%per direct%' OR LOWER(p_answer) LIKE '%meteen%' THEN 
                        UPDATE candidates 
                        SET availability_type = 'per_direct', 
                            availability_date = NULL, 
                            notice_weeks = NULL,
                            updated_at = NOW() 
                        WHERE id = v_cid;
                    ELSIF p_answer ~ '\\d{2}-\\d{2}-\\d{4}' OR p_answer ~ '\\d{4}-\\d{2}-\\d{2}' THEN 
                        UPDATE candidates 
                        SET availability_type = 'datum', 
                            availability_date = CASE 
                                WHEN p_answer ~ '^\\d{2}-\\d{2}-\\d{4}' THEN to_date(p_answer, 'DD-MM-YYYY')
                                WHEN p_answer ~ '^\\d{4}-\\d{2}-\\d{2}' THEN p_answer::date
                                ELSE NULL
                            END,
                            notice_weeks = NULL,
                            updated_at = NOW() 
                        WHERE id = v_cid;
                    ELSE 
                        UPDATE candidates 
                        SET availability_type = 'opzegtermijn', 
                            notice_weeks = NULLIF(regexp_replace(p_answer, '\\D', '', 'g'), '')::INT,
                            availability_date = NULL,
                            updated_at = NOW() 
                        WHERE id = v_cid;
                    END IF;
                    
                ELSIF p_field = 'hours_per_week' THEN 
                    UPDATE candidates 
                    SET hours_per_week = NULLIF(regexp_replace(p_answer, '\\D', '', 'g'), '')::INT, 
                        updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'work_mode' THEN 
                    UPDATE candidates 
                    SET work_mode = CASE 
                        WHEN LOWER(p_answer) LIKE '%locatie%' OR LOWER(p_answer) LIKE '%kantoor%' THEN 'locatie'
                        WHEN LOWER(p_answer) LIKE '%hybride%' OR LOWER(p_answer) LIKE '%hybrid%' THEN 'hybride'
                        WHEN LOWER(p_answer) LIKE '%remote%' OR LOWER(p_answer) LIKE '%thuis%' THEN 'remote'
                        ELSE LOWER(p_answer)
                    END, 
                    updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'primary_titles' THEN 
                    -- Convert comma-separated string to JSON array
                    v_array_text := string_to_array(p_answer, ',');
                    UPDATE candidates 
                    SET primary_titles = to_jsonb(v_array_text), 
                        updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'sectors' THEN 
                    -- Convert comma-separated string to JSON array
                    v_array_text := string_to_array(p_answer, ',');
                    UPDATE candidates 
                    SET sectors = to_jsonb(v_array_text), 
                        updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'experience_years' THEN 
                    UPDATE candidates 
                    SET experience_years = NULLIF(regexp_replace(p_answer, '\\D', '', 'g'), '')::INT, 
                        updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'last_responsibilities' THEN 
                    UPDATE candidate_texts 
                    SET last_responsibilities = p_answer 
                    WHERE candidate_id = v_cid;
                    
                ELSIF p_field = 'top_skills' THEN 
                    -- Convert comma-separated string to JSON array
                    v_array_text := string_to_array(p_answer, ',');
                    UPDATE candidate_texts 
                    SET top_skills = to_jsonb(v_array_text) 
                    WHERE candidate_id = v_cid;
                    
                ELSIF p_field = 'tools_tech' THEN 
                    -- Convert comma-separated string to JSON array
                    v_array_text := string_to_array(p_answer, ',');
                    UPDATE candidate_texts 
                    SET tools_tech = to_jsonb(v_array_text) 
                    WHERE candidate_id = v_cid;
                    
                ELSIF p_field = 'education_level' THEN 
                    UPDATE candidates 
                    SET education_level = CASE 
                        WHEN LOWER(p_answer) LIKE '%mbo%' OR LOWER(p_answer) LIKE '%vocational%' THEN 'vocational'
                        WHEN LOWER(p_answer) LIKE '%hbo%' OR LOWER(p_answer) LIKE '%bachelor%' THEN 'bachelor'
                        WHEN LOWER(p_answer) LIKE '%wo%' OR LOWER(p_answer) LIKE '%master%' THEN 'master'
                        WHEN LOWER(p_answer) LIKE '%phd%' OR LOWER(p_answer) LIKE '%doctoraat%' THEN 'phd'
                        WHEN LOWER(p_answer) LIKE '%middelbaar%' OR LOWER(p_answer) LIKE '%high_school%' THEN 'high_school'
                        ELSE LOWER(p_answer)
                    END, 
                    updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'salary' OR p_field = 'salary_expectation' THEN 
                    UPDATE candidates 
                    SET salary_expectation = NULLIF(regexp_replace(p_answer, '[^0-9\\.]', '', 'g'), '')::NUMERIC, 
                        updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'travel' OR p_field = 'travel_radius' THEN
                    UPDATE candidates 
                    SET travel_radius_km = NULLIF(regexp_replace(p_answer, '\\D', '', 'g'), '')::INT,
                        drivers_license = (POSITION('ja' IN LOWER(p_answer)) > 0 OR POSITION('yes' IN LOWER(p_answer)) > 0),
                        updated_at = NOW()
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'languages' THEN
                    -- Convert comma-separated string to JSON array
                    v_array_text := string_to_array(p_answer, ',');
                    UPDATE candidates 
                    SET languages = to_jsonb(v_array_text), 
                        updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'employer_values' THEN 
                    UPDATE candidate_texts 
                    SET employer_values = p_answer 
                    WHERE candidate_id = v_cid;
                    
                ELSIF p_field = 'best_result' THEN 
                    UPDATE candidate_texts 
                    SET best_result = p_answer 
                    WHERE candidate_id = v_cid;
                    
                ELSIF p_field = 'consent' OR p_field = 'consent_retention' THEN 
                    UPDATE candidates 
                    SET consent_retention_months = CASE 
                        WHEN LOWER(p_answer) IN ('ja', 'yes', 'true', '1') THEN 12 
                        ELSE 0 
                    END, 
                    updated_at = NOW() 
                    WHERE id = v_cid;
                    
                ELSIF p_field = 'notify_new_roles' THEN 
                    UPDATE candidates 
                    SET notify_new_roles = (LOWER(p_answer) IN ('ja', 'yes', 'true', '1')), 
                        updated_at = NOW() 
                    WHERE id = v_cid;
                END IF;

                -- Return result with candidate data
                RETURN QUERY
                    SELECT 
                        (p_step >= 23) AS done,
                        (
                            SELECT row_to_json(c)::jsonb
                            FROM (
                                SELECT * FROM candidates WHERE id = v_cid
                            ) c
                        ) AS candidate;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            if (config('database.default') === 'pgsql') {
                                DB::unprepared('DROP FUNCTION IF EXISTS upsert_candidate(TEXT, TEXT, TEXT, INT)');
                            }
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_11_16_103158_create_invoices_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('invoices', function (Blueprint $table) {
                                $table->id();
                                $table->string('invoice_number')->unique();
                                $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
                                $table->decimal('amount', 10, 2);
                                $table->decimal('tax_amount', 10, 2)->default(0);
                                $table->decimal('total_amount', 10, 2);
                                $table->string('currency', 3)->default('EUR');
                                $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
                                $table->date('invoice_date');
                                $table->date('due_date');
                                $table->date('paid_date')->nullable();
                                $table->boolean('is_partial')->default(false);
                                $table->string('parent_invoice_number')->nullable(); // For partial invoices
                                $table->integer('partial_number')->nullable(); // 1, 2, 3, etc.
                                $table->json('line_items')->nullable(); // Invoice line items
                                $table->json('company_details')->nullable(); // Snapshot of company details at invoice time
                                $table->text('notes')->nullable();
                                $table->string('pdf_path')->nullable(); // Path to generated PDF
                                $table->timestamps();

                                $table->index(['company_id', 'status']);
                                $table->index(['status', 'due_date']);
                                $table->index('invoice_number');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('invoices');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_11_16_103158_create_payments_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('payments', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
                                $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
                                $table->decimal('amount', 10, 2);
                                $table->string('currency', 3)->default('EUR');
                                $table->enum('status', ['pending', 'paid', 'failed', 'cancelled'])->default('pending');
                                $table->string('payment_provider')->nullable(); // mollie, stripe, etc.
                                $table->string('payment_provider_id')->nullable(); // External payment ID
                                $table->timestamp('paid_at')->nullable();
                                $table->json('metadata')->nullable();
                                $table->text('notes')->nullable();
                                $table->timestamps();

                                $table->index(['company_id', 'status']);
                                $table->index(['status', 'paid_at']);
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('payments');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_11_16_103159_create_invoice_settings_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('invoice_settings', function (Blueprint $table) {
                                $table->id();
                                $table->string('invoice_number_prefix')->default('NX'); // NX
                                $table->string('invoice_number_format')->default('{prefix}{year}-{number}'); // NX2025-0001
                                $table->integer('next_invoice_number')->default(1);
                                $table->integer('current_year')->default(date('Y'));
                                $table->string('company_name')->nullable();
                                $table->string('company_address')->nullable();
                                $table->string('company_city')->nullable();
                                $table->string('company_postal_code')->nullable();
                                $table->string('company_country')->nullable();
                                $table->string('company_vat_number')->nullable();
                                $table->string('company_email')->nullable();
                                $table->string('company_phone')->nullable();
                                $table->string('bank_account')->nullable();
                                $table->decimal('default_tax_rate', 5, 2)->default(21.00); // 21% VAT
                                $table->integer('payment_terms_days')->default(30); // 30 days payment terms
                                $table->text('invoice_footer_text')->nullable();
                                $table->string('logo_path')->nullable();
                                $table->timestamps();
                            });

                            // Insert default settings
                            DB::table('invoice_settings')->insert([
                                'invoice_number_prefix' => 'NX',
                                'invoice_number_format' => '{prefix}{year}-{number}',
                                'next_invoice_number' => 1,
                                'current_year' => date('Y'),
                                'default_tax_rate' => 21.00,
                                'payment_terms_days' => 30,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('invoice_settings');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_11_16_103412_create_payment_reminders_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('payment_reminders', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('invoice_id')->constrained('invoices')->onDelete('cascade');
                                $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
                                $table->enum('reminder_type', ['first', 'second', 'final'])->default('first');
                                $table->timestamp('sent_at')->nullable();
                                $table->string('sent_to_email')->nullable();
                                $table->text('message')->nullable();
                                $table->timestamps();

                                $table->index(['invoice_id', 'reminder_type']);
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('payment_reminders');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_11_16_112932_add_default_amount_to_invoice_settings_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            // Check if column already exists before adding it
                            if (! Schema::hasColumn('invoice_settings', 'default_amount')) {
                                Schema::table('invoice_settings', function (Blueprint $table) {
                                    $table->decimal('default_amount', 10, 2)->nullable();
                                });
                            }
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            // Check if column exists before dropping it
                            if (Schema::hasColumn('invoice_settings', 'default_amount')) {
                                Schema::table('invoice_settings', function (Blueprint $table) {
                                    $table->dropColumn('default_amount');
                                });
                            }
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_11_17_100000_add_job_match_id_to_invoices_and_payments_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         * Invoices/payments kunnen aan een match (skillmatching) gekoppeld worden; alleen in skillmatching-DB.
                         */
                        public function up(): void
                        {
                            Schema::table('invoices', function (Blueprint $table) {
                                $table->foreignId('job_match_id')->nullable()->after('company_id')->constrained('matches')->onDelete('set null');
                            });

                            Schema::table('payments', function (Blueprint $table) {
                                $table->foreignId('job_match_id')->nullable()->after('company_id')->constrained('matches')->onDelete('set null');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('invoices', function (Blueprint $table) {
                                $table->dropForeign(['job_match_id']);
                            });
                            Schema::table('payments', function (Blueprint $table) {
                                $table->dropForeign(['job_match_id']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_11_17_220723_add_logo_path_to_companies_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('companies', function (Blueprint $table) {
                                $table->string('logo_path', 255)->nullable()->after('description');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('companies', function (Blueprint $table) {
                                $table->dropColumn('logo_path');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_11_28_105438_add_archived_at_to_notifications_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('notifications', function (Blueprint $table) {
                                $table->timestamp('archived_at')->nullable()->after('scheduled_at');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('notifications', function (Blueprint $table) {
                                $table->dropColumn('archived_at');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_12_06_115617_create_chat_rooms_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            if (! Schema::hasTable('chat_rooms')) {
                                Schema::create('chat_rooms', function (Blueprint $table) {
                                    $table->id();
                                    $table->foreignId('candidate_id')->constrained('users')->onDelete('cascade');
                                    $table->string('title')->nullable(); // Optioneel: custom titel voor de chat
                                    $table->timestamp('last_message_at')->nullable();
                                    $table->timestamps();
                                });
                            }
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('chat_rooms');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_12_06_115618_create_chat_messages_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            if (! Schema::hasTable('chat_messages')) {
                                Schema::create('chat_messages', function (Blueprint $table) {
                                    $table->id();
                                    $table->foreignId('chat_room_id')->constrained('chat_rooms')->onDelete('cascade');
                                    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                                    $table->text('message');
                                    $table->boolean('is_read')->default(false);
                                    $table->timestamp('read_at')->nullable();
                                    $table->timestamps();

                                    $table->index(['chat_room_id', 'created_at']);
                                });
                            }
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('chat_messages');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_12_06_115619_create_chat_participants_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            if (! Schema::hasTable('chat_participants')) {
                                Schema::create('chat_participants', function (Blueprint $table) {
                                    $table->id();
                                    $table->foreignId('chat_room_id')->constrained('chat_rooms')->onDelete('cascade');
                                    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                                    $table->enum('status', ['pending', 'accepted', 'declined'])->default('pending');
                                    $table->timestamp('joined_at')->nullable();
                                    $table->timestamp('last_read_at')->nullable();
                                    $table->timestamps();

                                    $table->unique(['chat_room_id', 'user_id']);
                                    $table->index('status');
                                });
                            }
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('chat_participants');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_12_06_115619_create_typing_indicators_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            if (! Schema::hasTable('typing_indicators')) {
                                Schema::create('typing_indicators', function (Blueprint $table) {
                                    $table->id();
                                    $table->foreignId('chat_room_id')->constrained('chat_rooms')->onDelete('cascade');
                                    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                                    $table->timestamp('updated_at');

                                    $table->unique(['chat_room_id', 'user_id']);
                                });
                            }
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('typing_indicators');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_12_06_123330_create_company_locations_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('company_locations', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
                                $table->string('name');
                                $table->string('street')->nullable();
                                $table->string('house_number')->nullable();
                                $table->string('house_number_extension')->nullable();
                                $table->string('postal_code')->nullable();
                                $table->string('city')->nullable();
                                $table->string('country')->nullable();
                                $table->string('phone')->nullable();
                                $table->string('email')->nullable();
                                $table->boolean('is_main')->default(false);
                                $table->boolean('is_active')->default(true);
                                $table->timestamps();

                                $table->index('company_id');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('company_locations');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_12_06_200434_add_is_main_to_companies_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('companies', function (Blueprint $table) {
                                $table->boolean('is_main')->default(false)->after('is_intermediary');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('companies', function (Blueprint $table) {
                                $table->dropColumn('is_main');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_12_07_200243_rename_categories_to_branches_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            // Check if categories table exists and branches doesn't exist
                            if (Schema::hasTable('categories') && ! Schema::hasTable('branches')) {
                                // Rename table from categories to branches
                                Schema::rename('categories', 'branches');

                                // For SQLite, we need to recreate the table with the new column name
                                if (DB::getDriverName() === 'sqlite') {
                                    // Drop old foreign key constraint by recreating vacancies table
                                    if (Schema::hasTable('vacancies')) {
                                        try {
                                            Schema::table('vacancies', function (Blueprint $table) {
                                                $table->dropForeign(['category_id']);
                                            });
                                        } catch (\Exception $e) {
                                            // Foreign key might not exist, continue
                                        }

                                        // Rename column using raw SQL for SQLite
                                        DB::statement('ALTER TABLE vacancies RENAME COLUMN category_id TO branch_id');

                                        // Re-add foreign key constraint
                                        Schema::table('vacancies', function (Blueprint $table) {
                                            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                                        });
                                    }
                                } else {
                                    // For other databases, use standard renameColumn
                                    if (Schema::hasTable('vacancies')) {
                                        try {
                                            Schema::table('vacancies', function (Blueprint $table) {
                                                $table->dropForeign(['category_id']);
                                            });
                                        } catch (\Exception $e) {
                                            // Foreign key might not exist, continue
                                        }

                                        // Check if category_id column exists before renaming
                                        if (Schema::hasColumn('vacancies', 'category_id')) {
                                            Schema::table('vacancies', function (Blueprint $table) {
                                                $table->renameColumn('category_id', 'branch_id');
                                            });
                                        }

                                        // Check if foreign key doesn't already exist
                                        if (! Schema::hasColumn('vacancies', 'branch_id') ||
                                            ! $this->foreignKeyExists('vacancies', 'vacancies_branch_id_foreign')) {
                                            Schema::table('vacancies', function (Blueprint $table) {
                                                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                                            });
                                        }
                                    }
                                }
                            } elseif (Schema::hasTable('branches')) {
                                // Branches table already exists, skip migration
                                echo "Branches table already exists, skipping rename migration.\n";
                            } else {
                                // Neither table exists, skip migration
                                echo "Categories table does not exist, skipping rename migration.\n";
                            }
                        }

                        /**
                         * Check if a foreign key constraint exists
                         */
                        private function foreignKeyExists(string $table, string $constraintName): bool
                        {
                            $connection = DB::connection();
                            $driver = $connection->getDriverName();

                            if ($driver === 'pgsql') {
                                $result = DB::selectOne(
                                    "SELECT constraint_name 
                 FROM information_schema.table_constraints 
                 WHERE table_name = ? AND constraint_name = ? AND constraint_type = 'FOREIGN KEY'",
                                    [$table, $constraintName]
                                );

                                return $result !== null;
                            }

                            // For other databases, assume it doesn't exist to be safe
                            return false;
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            // Drop foreign key constraint
                            Schema::table('vacancies', function (Blueprint $table) {
                                $table->dropForeign(['branch_id']);
                            });

                            // Rename column back
                            if (DB::getDriverName() === 'sqlite') {
                                DB::statement('ALTER TABLE vacancies RENAME COLUMN branch_id TO category_id');
                            } else {
                                Schema::table('vacancies', function (Blueprint $table) {
                                    $table->renameColumn('branch_id', 'category_id');
                                });
                            }

                            // Add old foreign key constraint
                            Schema::table('vacancies', function (Blueprint $table) {
                                $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
                            });

                            // Rename table back
                            Schema::rename('branches', 'categories');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_12_08_204320_add_logo_blob_to_companies_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            // Use raw SQL to ensure correct column types for PostgreSQL
                            if (! Schema::hasColumn('companies', 'logo_blob')) {
                                DB::statement('ALTER TABLE companies ADD COLUMN logo_blob TEXT');
                            }
                            if (! Schema::hasColumn('companies', 'logo_mime_type')) {
                                DB::statement('ALTER TABLE companies ADD COLUMN logo_mime_type VARCHAR(255)');
                            }
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('companies', function (Blueprint $table) {
                                if (Schema::hasColumn('companies', 'logo_blob')) {
                                    $table->dropColumn('logo_blob');
                                }
                                if (Schema::hasColumn('companies', 'logo_mime_type')) {
                                    $table->dropColumn('logo_mime_type');
                                }
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_12_10_223530_add_is_active_to_users_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            // Check if column already exists before adding it
                            if (! Schema::hasColumn('users', 'is_active')) {
                                Schema::table('users', function (Blueprint $table) {
                                    $table->boolean('is_active')->default(true)->after('email_verified_at');
                                });
                            }
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            // Only drop column if it exists
                            if (Schema::hasColumn('users', 'is_active')) {
                                Schema::table('users', function (Blueprint $table) {
                                    $table->dropColumn('is_active');
                                });
                            }
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_12_11_221599_create_job_titles_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            if (Schema::hasTable('job_titles')) {
                                return;
                            }
                            Schema::create('job_titles', function (Blueprint $table) {
                                $table->id();
                                $table->string('name')->unique();
                                $table->integer('usage_count')->default(0); // Track how often this title is used
                                $table->timestamps();

                                $table->index('name');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('job_titles');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_12_11_221600_add_function_to_users_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            // First ensure job_titles table exists
                            if (! Schema::hasTable('job_titles')) {
                                Schema::create('job_titles', function (Blueprint $table) {
                                    $table->id();
                                    $table->string('name')->unique();
                                    $table->integer('usage_count')->default(0);
                                    $table->timestamps();
                                    $table->index('name');
                                });
                            }

                            Schema::table('users', function (Blueprint $table) {
                                $table->string('function')->nullable()->after('last_name');
                                $table->foreignId('job_title_id')->nullable()->after('function')->constrained('job_titles')->onDelete('set null');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('users', function (Blueprint $table) {
                                $table->dropForeign(['job_title_id']);
                                $table->dropColumn(['function', 'job_title_id']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_12_13_230000_create_branch_functions_table_and_seed.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            if (! Schema::hasTable('branch_functions')) {
                                Schema::create('branch_functions', function (Blueprint $table) {
                                    $table->id();
                                    $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
                                    $table->string('name');
                                    $table->timestamps();

                                    $table->unique(['branch_id', 'name']);
                                    $table->index(['branch_id', 'name']);
                                });
                            }

                            // Seed initial branch functions list (as requested)
                            $data = [
                                'Marketing' => [
                                    'Marketingmanager', 'Digital_Marketeer', 'Content_Marketeer', 'SEO_Specialist', 'SEA_Specialist', 'Socialmedia_Manager', 'Brandmanager', 'Productmarketeer', 'Growth_Marketeer', 'Emailmarketeer', 'Marketinganalist', 'Copywriter', 'Creatief_Directeur', 'Marketingcoordinator', 'Campagnemanager', 'Communitymanager', 'Influencer_Manager', 'UX_Schrijver',
                                ],
                                'Finance' => [
                                    'Financieel_Analist', 'Accountant', 'Controller', 'Finance_Manager', 'CFO', 'Beleggingsanalist', 'Portefeuillemanager', 'Risico_Analist', 'Compliance_Officer', 'Auditor', 'Budgetanalist', 'Treasury_Analist', 'Belastingadviseur', 'Financieel_Planner', 'Kredietanalist', 'Actuaris',
                                ],
                                'HR' => [
                                    'HR_Manager', 'HR_Businesspartner', 'Recruiter', 'Talent_Acquisition_Specialist', 'HR_Coordinator', 'Opleiding_Development_Specialist', 'Compensation_Benefits_Specialist', 'HR_Generalist', 'HR_Directeur', 'Payroll_Specialist', 'Diversiteit_Inclusie_Manager',
                                ],
                                'Sales' => [
                                    'Vertegenwoordiger', 'Accountmanager', 'Salesmanager', 'Business_Development_Manager', 'Inside_Sales', 'Outside_Sales', 'Key_Accountmanager', 'Sales_Executive', 'Salesdirecteur', 'Customer_Success_Manager', 'Regiomanager', 'SDR', 'BDR',
                                ],
                                'Engineering' => [
                                    'Software_Engineer', 'Softwareontwikkelaar', 'Werktuigbouwkundig_Ingenieur', 'Elektrotechnisch_Ingenieur', 'Civiel_Ingenieur', 'Chemisch_Ingenieur', 'Aerospace_Engineer', 'Systems_Engineer', 'QA_Engineer', 'DevOps_Engineer', 'Data_Engineer', 'Netwerkingenieur', 'Constructeur', 'Robotica_Engineer', 'Embedded_Engineer',
                                ],
                                'Education' => [
                                    'Docent', 'Leerkracht', 'Leraar', 'Universitair_Docent', 'Professor', 'Onderwijsassistent', 'Onderwijscoordinator', 'Onderwijskundig_Ontwerper', 'Studieadviseur', 'Schoolleider', 'Curriculumontwikkelaar', 'Docent_Speciaal_Onderwijs', 'Tutor', 'Onderwijsconsultant',
                                ],
                                'Construction' => [
                                    'Bouwvakker', 'Uitvoerder', 'Projectleider_Bouw', 'Architect', 'Civiel_Ingenieur', 'Calculator', 'Timmerman', 'Elektricien', 'Loodgieter', 'Voorman', 'Veiligheidskundige', 'Bouwinspecteur',
                                ],
                                'Hospitality' => [
                                    'Hotelmanager', 'Receptiemedewerker', 'Concierge', 'Housekeeping_Medewerker', 'Eventmanager', 'Chefkok', 'Souschef', 'Bartender', 'Bedieningsmedewerker', 'Restaurantmanager', 'Gastheer_Gastvrouw', 'Guest_Relations_Manager',
                                ],
                                'Legal' => [
                                    'Jurist', 'Advocaat', 'Legal_Counsel', 'Paralegal', 'Juridisch_Assistent', 'Bedrijfsjurist', 'Contractspecialist', 'Compliance_Officer', 'Rechter', 'Juridisch_Onderzoeker', 'Procesjurist',
                                ],
                                'Real_Estate' => [
                                    'Makelaar', 'Vastgoedadviseur', 'Property_Manager', 'Vastgoedbeheerder', 'Verhuurconsulent', 'Taxateur', 'Vastgoedanalist', 'Asset_Manager', 'Facilitair_Manager',
                                ],
                                'Retail' => [
                                    'Filiaalmanager', 'Verkoopmedewerker', 'Kassamedewerker', 'Visual_Merchandiser', 'Voorraadbeheerder', 'Assistent_Filiaalmanager', 'Retail_Buyer', 'Diefstalpreventie_Medewerker',
                                ],
                                'Travel_Tourism' => [
                                    'Reisagent', 'Reisadviseur', 'Gids', 'Reserveringsmedewerker', 'Stewardess', 'Piloot', 'Toerisme_Manager', 'Guest_Services_Medewerker',
                                ],
                                'Transportation' => [
                                    'Vrachtwagenchauffeur', 'Koerier', 'Logistiek_Coordinator', 'Fleet_Manager', 'Transportplanner', 'Dispatcher', 'Magazijnmedewerker', 'Supply_Chain_Analist',
                                ],
                                'Manufacturing' => [
                                    'Productiemedewerker', 'Machineoperator', 'Manufacturing_Engineer', 'Kwaliteitscontroleur', 'Plantmanager', 'Productiemanager', 'Onderhoudsmonteur', 'Supply_Chain_Specialist',
                                ],
                                'Arts' => [
                                    'Grafisch_Ontwerper', 'Illustrator', 'Animator', 'Art_Director', 'Fotograaf', 'Videograaf', 'Muzikant', 'Beeldhouwer', 'Conservator', 'Creatief_Producer',
                                ],
                                'Science' => [
                                    'Onderzoeker', 'Laborant', 'Chemicus', 'Natuurkundige', 'Bioloog', 'Data_Scientist', 'Onderzoeksassistent', 'Veldonderzoeker',
                                ],
                                'Government' => [
                                    'Beleidsadviseur', 'Ambtenaar', 'Publiek_Administrator', 'Programmamanager', 'Inspecteur', 'Diplomaat', 'Stedenbouwkundige', 'Maatschappelijk_Werker',
                                ],
                                'Non_Profit' => [
                                    'Programmacoordinator', 'Fondsenwerver', 'Subsidieschrijver', 'Vrijwilligerscoordinator', 'Outreach_Specialist', 'Directeur_Stichting', 'Casemanager',
                                ],
                                'Advertising' => [
                                    'Art_Director', 'Copywriter', 'Mediaplanner', 'Account_Executive', 'Creative_Director', 'Campagnestrateeg', 'Ad_Ops_Specialist',
                                ],
                                'Agriculture' => [
                                    'Landbouwer', 'Agronoom', 'Landbouwkundig_Ingenieur', 'Bedrijfsleider_Landbouw', 'Tuinbouwer', 'Veeteeltmanager', 'Landbouwtechnicus',
                                ],
                                'Automotive' => [
                                    'Automonteur', 'Technisch_Specialist_Auto', 'Serviceadviseur', 'Automotive_Engineer', 'Productiemedewerker_Auto', 'Onderdelenspecialist', 'Kwaliteitscontroleur',
                                ],
                                'Biotechnology' => [
                                    'Biotech_Onderzoeker', 'Laboratoriumtechnicus', 'Bioinformaticus', 'Procesingenieur', 'Regulatory_Affairs_Specialist', 'QA_Wetenschapper',
                                ],
                                'Consulting' => [
                                    'Consultant', 'Managementconsultant', 'Strategieconsultant', 'IT_Consultant', 'Financieel_Consultant', 'HR_Consultant', 'Operations_Consultant', 'Business_Analist',
                                ],
                                'Sports' => [
                                    'Coach', 'Sporter', 'Fitnessinstructeur', 'Sportmanager', 'Scheidsrechter', 'Sportmarketeer', 'Sportfysiotherapeut', 'Scout',
                                ],
                                'Energy' => [
                                    'Energie_Engineer', 'Petroleumingenieur', 'Zonnepaneeltechnicus', 'Windturbinetechnicus', 'Energie_Analist', 'Centrale_Operator', 'Milieutechnisch_Ingenieur',
                                ],
                                'Entertainment' => [
                                    'Acteur', 'Producer', 'Regisseur', 'Scenarioschrijver', 'Video_Editor', 'Geluidstechnicus', 'Talentmanager', 'Productieassistent',
                                ],
                                'Environmental' => [
                                    'Milieuwetenschapper', 'Duurzaamheidsspecialist', 'Milieuconsultant', 'Ecoloog', 'Afvalbeheer_Specialist', 'Milieu_Engineer',
                                ],
                                'Fashion' => [
                                    'Modeontwerper', 'Stylist', 'Patroonmaker', 'Mode_Inkoper', 'Merchandiser', 'Mode_Illustrator', 'Productontwikkelaar_Mode', 'Showroommanager',
                                ],
                                'Food_Beverage' => [
                                    'Chefkok', 'Kok', 'Banketbakker', 'Barista', 'Restaurantmanager', 'Voedingsmiddelentechnoloog', 'QA_Technicus_Voeding', 'Productiemedewerker_Voeding',
                                ],
                                'Gaming' => [
                                    'Game_Designer', 'Game_Developer', '3D_Artist', 'Level_Designer', 'QA_Tester', 'Narrative_Designer', 'Game_Producer', 'Communitymanager',
                                ],
                                'Insurance' => [
                                    'Verzekeringsadviseur', 'Schadebehandelaar', 'Acceptant', 'Actuaris', 'Risicomanager', 'Verzekeringsanalist', 'Klantenservicemedewerker',
                                ],
                                'Media' => [
                                    'Journalist', 'Redacteur', 'Producer', 'Reporter', 'Cameraperson', 'Audiotechnicus', 'Social_Media_Editor', 'Content_Producer',
                                ],
                                'Pharmaceuticals' => [
                                    'Farmaceutisch_Onderzoeker', 'Apothekersassistent', 'Clinical_Research_Associate', 'Kwaliteitscontroleur_Farmacie', 'Regulatory_Affairs_Specialist', 'Productontwikkelaar_Farmacie',
                                ],
                                'Public_Relations' => [
                                    'PR_Adviseur', 'Communicatieadviseur', 'Woordvoerder', 'Mediarelatie_Specialist', 'PR_Manager', 'Event_Coordinator',
                                ],
                                'Research_Development' => [
                                    'R&D_Engineer', 'Onderzoeker', 'Innovatiemanager', 'Productontwikkelaar', 'Labonderzoeker', 'Research_Engineer',
                                ],
                                'Security' => [
                                    'Beveiliger', 'Security_Officer', 'Cybersecurity_Specialist', 'Security_Analist', 'Bedrijfsrechercheur', 'Security_Consultant',
                                ],
                                'Telecommunications' => [
                                    'Netwerkbeheerder', 'Telecom_Engineer', 'Customer_Service_Telecom', 'Telecom_Sales', 'Systems_Engineer_Telecom', 'Installatiemonteur',
                                ],
                                'Healthcare' => [
                                    'Verpleegkundige', 'Arts', 'Apotheker', 'Fysiotherapeut', 'Zorgassistent', 'Medisch_Specialist', 'Psycholoog', 'Tandarts', 'Radioloog', 'Verpleegkundig_Specialist',
                                ],
                                'IT' => [
                                    'Softwareontwikkelaar', 'Software_Engineer', 'IT_Support', 'Systeembeheerder', 'DevOps_Engineer', 'Cloud_Engineer', 'Data_Scientist', 'Data_Engineer', 'IT_Consultant', 'Business_Analist_IT', 'Cybersecurity_Specialist', 'Solutions_Architect',
                                ],
                                'Accounting' => [
                                    'Boekhouder', 'Accountant', 'Financieel_Administratief_Medewerker', 'Controller', 'Assistent_Accountant', 'Auditmedewerker', 'Payroll_Specialist', 'Belastingadviseur',
                                ],
                            ];

                            $now = now();

                            foreach ($data as $branchName => $functions) {
                                // Branch keys can contain underscores. We store the branch name as a human label.
                                $branchDisplayName = str_replace('_', ' ', $branchName);
                                $branchSlug = Str::slug($branchDisplayName);

                                $branchId = DB::table('branches')
                                    ->where('slug', $branchSlug)
                                    ->orWhere('name', $branchDisplayName)
                                    ->orWhere('name', $branchName)
                                    ->value('id');

                                if (! $branchId) {
                                    $branchId = DB::table('branches')->insertGetId([
                                        'name' => $branchDisplayName,
                                        'slug' => $branchSlug,
                                        'description' => null,
                                        'color' => null,
                                        'icon' => null,
                                        'is_active' => true,
                                        'sort_order' => 0,
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                    ]);
                                }

                                foreach ($functions as $fn) {
                                    DB::table('branch_functions')->updateOrInsert(
                                        ['branch_id' => $branchId, 'name' => $fn],
                                        ['created_at' => $now, 'updated_at' => $now]
                                    );
                                }
                            }
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('branch_functions');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_12_13_231500_update_kunst_arts_branch_functions.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            // If the base table doesn't exist yet, don't do anything.
                            if (! Schema::hasTable('branches') || ! Schema::hasTable('branch_functions')) {
                                return;
                            }

                            $kunstFunctions = [
                                'Grafisch_Ontwerper',
                                'Illustrator',
                                'Animator',
                                'Art_Director',
                                'Fotograaf',
                                'Videograaf',
                                'Muzikant',
                                'Beeldhouwer',
                                'Conservator',
                                'Creatief_Producer',
                            ];

                            $artsFunctions = [
                                'Huisarts',
                                'Chirurg',
                                'Hartchirurg',
                                'Kinderarts',
                                'Internist',
                                'Neuroloog',
                                'Psychiater',
                                'Oncoloog',
                                'Anesthesioloog',
                                'Gynaecoloog',
                                'Radioloog',
                                'Revalidatiearts',
                                'Spoedeisende_Hulp_Arts',
                                'Bedrijfsarts',
                            ];

                            $now = now();

                            // Helper: get branch by name/slug
                            $getBranchId = function (string $name, string $slug) {
                                return DB::table('branches')
                                    ->where('slug', $slug)
                                    ->orWhere('name', $name)
                                    ->value('id');
                            };

                            // 1) "pas de Branch voor de huidige arts aan naar Kunst"
                            $kunstSlug = Str::slug('Kunst');
                            $artsSlug = Str::slug('Arts');

                            $artsOldId = DB::table('branches')->where('name', 'Arts')->value('id');
                            $kunstId = $getBranchId('Kunst', $kunstSlug);

                            if ($artsOldId && ! $kunstId) {
                                // Rename the existing "Arts" branch to "Kunst"
                                DB::table('branches')->where('id', $artsOldId)->update([
                                    'name' => 'Kunst',
                                    'slug' => $kunstSlug,
                                    'updated_at' => $now,
                                ]);
                                $kunstId = $artsOldId;
                            } elseif ($artsOldId && $kunstId && $artsOldId !== $kunstId) {
                                // If Kunst already exists, move references and remove the old Arts-branch.
                                if (Schema::hasTable('vacancies')) {
                                    DB::table('vacancies')->where('branch_id', $artsOldId)->update([
                                        'branch_id' => $kunstId,
                                    ]);
                                }

                                // Deleting the old branch will cascade-delete its branch_functions.
                                DB::table('branches')->where('id', $artsOldId)->delete();
                            }

                            // Ensure Kunst exists
                            $kunstId = $getBranchId('Kunst', $kunstSlug);
                            if (! $kunstId) {
                                $kunstId = DB::table('branches')->insertGetId([
                                    'name' => 'Kunst',
                                    'slug' => $kunstSlug,
                                    'description' => null,
                                    'color' => null,
                                    'icon' => null,
                                    'is_active' => true,
                                    'sort_order' => 0,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ]);
                            }

                            // 2) Ensure "Arts" exists (new medical branch)
                            $artsId = $getBranchId('Arts', $artsSlug);
                            if (! $artsId) {
                                $artsId = DB::table('branches')->insertGetId([
                                    'name' => 'Arts',
                                    'slug' => $artsSlug,
                                    'description' => null,
                                    'color' => null,
                                    'icon' => null,
                                    'is_active' => true,
                                    'sort_order' => 0,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ]);
                            } else {
                                // Normalize name/slug if needed
                                DB::table('branches')->where('id', $artsId)->update([
                                    'name' => 'Arts',
                                    'slug' => $artsSlug,
                                    'updated_at' => $now,
                                ]);
                            }

                            // 3) Replace functions for both branches (exact lists)
                            DB::table('branch_functions')->where('branch_id', $kunstId)->delete();
                            foreach ($kunstFunctions as $fn) {
                                DB::table('branch_functions')->insert([
                                    'branch_id' => $kunstId,
                                    'name' => $fn,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ]);
                            }

                            DB::table('branch_functions')->where('branch_id', $artsId)->delete();
                            foreach ($artsFunctions as $fn) {
                                DB::table('branch_functions')->insert([
                                    'branch_id' => $artsId,
                                    'name' => $fn,
                                    'created_at' => $now,
                                    'updated_at' => $now,
                                ]);
                            }
                        }

                        public function down(): void
                        {
                            // Intentionally left blank (data migration).
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_12_14_100649_create_branch_function_skills_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('branch_function_skills', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('branch_function_id')->constrained('branch_functions')->cascadeOnDelete();
                                $table->string('name');
                                $table->timestamps();

                                $table->unique(['branch_function_id', 'name']);
                                $table->index(['branch_function_id', 'name']);
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('branch_function_skills');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_12_14_100650_add_required_skills_to_vacancies_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('vacancies', function (Blueprint $table) {
                                if (! Schema::hasColumn('vacancies', 'required_skills')) {
                                    // Prefer JSON when supported; fallback to TEXT for SQLite (tests).
                                    if (DB::getDriverName() === 'sqlite') {
                                        $table->text('required_skills')->nullable()->after('requirements');
                                    } else {
                                        $table->json('required_skills')->nullable()->after('requirements');
                                    }
                                }
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('vacancies', function (Blueprint $table) {
                                if (Schema::hasColumn('vacancies', 'required_skills')) {
                                    $table->dropColumn('required_skills');
                                }
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_12_14_110000_seed_branch_function_skills.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            if (! Schema::hasTable('branch_functions') || ! Schema::hasTable('branch_function_skills')) {
                                return;
                            }

                            $data = [
                                'Software_Engineer' => [
                                    'hard_skills' => [
                                        'Programmeren_JavaScript',
                                        'Programmeren_PHP',
                                        'Programmeren_Python',
                                        'Object_Oriented_Programming',
                                        'REST_API_Design',
                                        'Databases_SQL',
                                        'Versiebeheer_Git',
                                        'Design_Patterns',
                                        'Unit_Tests_Schrijven',
                                        'Debuggen',
                                        'CI_CD_Pipelines',
                                        'Cloud_Basics_AWS_Azure_GCP',
                                        'Security_Basics_OWASP',
                                        'Laravel_Of_Ander_Framework',
                                        'Code_Reviews',
                                    ],
                                    'soft_skills' => [
                                        'Probleemoplossend_Vermogen',
                                        'Analytisch_Denken',
                                        'Samenwerken',
                                        'Communicatieve_Vaardigheden',
                                        'Zelfstandigheid',
                                        'Plannen_En_Organiseren',
                                        'Nauwkeurigheid',
                                        'Lerend_Vermogen',
                                        'Omgaan_Met_Feedback',
                                        'Proactieve_Houding',
                                    ],
                                ],
                                'Projectmanager' => [
                                    'hard_skills' => [
                                        'Projectplanning',
                                        'Scope_Management',
                                        'Risicomanagement',
                                        'Budgetbeheer',
                                        'Stakeholdermanagement',
                                        'Resource_Planning',
                                        'Scrum_Kennis',
                                        'Agile_Methodieken',
                                        'Prince2_Of_PMP_Basis',
                                        'Rapportage_Opstellen',
                                        'MS_Project_Of_Alternatief',
                                        'Change_Management',
                                        'Kwaliteitsbewaking',
                                    ],
                                    'soft_skills' => [
                                        'Leiderschap',
                                        'Besluitvaardigheid',
                                        'Communicatieve_Vaardigheden',
                                        'Conflicthantering',
                                        'Onderhandelingsvaardigheden',
                                        'Resultaatgerichtheid',
                                        'Stressbestendigheid',
                                        'Helikopterview',
                                        'Organisatorisch_Vermogen',
                                        'Samenwerken',
                                    ],
                                ],
                                'Accountmanager' => [
                                    'hard_skills' => [
                                        'Relatiebeheer',
                                        'New_Business_Acquisitie',
                                        'Sales_Funnels_Beheren',
                                        'Offertes_Maken',
                                        'Contractonderhandelingen',
                                        'CRM_Systeem_Gebruik',
                                        'Marktanalyse',
                                        'Pipeline_Management',
                                        'Presentatievaardigheden',
                                        'Forecasting',
                                        'Up_En_Cross_Selling',
                                    ],
                                    'soft_skills' => [
                                        'Communicatieve_Vaardigheden',
                                        'Luistervaardigheid',
                                        'Overtuigingskracht',
                                        'Klantgerichtheid',
                                        'Netwerken',
                                        'Resultaatgerichtheid',
                                        'Doorzettingsvermogen',
                                        'Relatiegericht_Denken',
                                        'Empathisch_Vermogen',
                                        'Zelfdiscipline',
                                    ],
                                ],
                                'Marketingmanager' => [
                                    'hard_skills' => [
                                        'Marketingstrategie_Ontwikkelen',
                                        'Campagneplanning',
                                        'Digital_Marketing',
                                        'SEO_Basis',
                                        'SEA_Basis',
                                        'Social_Media_Advertising',
                                        'Contentstrategie',
                                        'E_mailmarketing',
                                        'Marketingautomatisering',
                                        'Data_Analyseren_Google_Analytics_Of_Similar',
                                        'Budgetbeheer',
                                        'Marktonderzoek',
                                        'Brand_Management',
                                    ],
                                    'soft_skills' => [
                                        'Creatief_Denken',
                                        'Analytisch_Denken',
                                        'Communicatieve_Vaardigheden',
                                        'Leiderschap',
                                        'Samenwerken',
                                        'Strategisch_Denken',
                                        'Besluitvaardigheid',
                                        'Organisatorisch_Vermogen',
                                        'Resultaatgerichtheid',
                                        'Aanpassingsvermogen',
                                    ],
                                ],
                                'Salesmanager' => [
                                    'hard_skills' => [
                                        'Salesstrategie_Ontwikkelen',
                                        'Teamsturing',
                                        'Targetsetting',
                                        'Performance_Analyse',
                                        'Forecasting',
                                        'Sales_Coaching',
                                        'CRM_Gebruik',
                                        'Key_Account_Management',
                                        'Onderhandelingsstrategien',
                                        'Rapportage_Opstellen',
                                    ],
                                    'soft_skills' => [
                                        'Leiderschap',
                                        'Motiveren_Van_Anderen',
                                        'Resultaatgerichtheid',
                                        'Communicatieve_Vaardigheden',
                                        'Overtuigingskracht',
                                        'Conflicthantering',
                                        'Besluitvaardigheid',
                                        'Stressbestendigheid',
                                        'Veranderingsbereidheid',
                                        'Empathie',
                                    ],
                                ],
                                'HR_Manager' => [
                                    'hard_skills' => [
                                        'HR_Beleid_Ontwikkelen',
                                        'Werving_En_Selectie',
                                        'Arbeidsrecht_Basis',
                                        'Performance_Management',
                                        'Verzuimbegeleiding',
                                        'Compensatie_En_Benefits',
                                        'HR_Data_Analyse',
                                        'Training_En_Ontwikkeling',
                                        'Functie_En_Salarishuis',
                                        'Medewerkerstevredenheidsonderzoek',
                                    ],
                                    'soft_skills' => [
                                        'Communicatieve_Vaardigheden',
                                        'Conflicthantering',
                                        'Coaching',
                                        'Integer_Handelen',
                                        'Organisatiesensitiviteit',
                                        'Empathisch_Vermogen',
                                        'Luistervaardigheid',
                                        'Besluitvaardigheid',
                                        'Samenwerken',
                                        'Adviesvaardigheden',
                                    ],
                                ],
                                'Recruiter' => [
                                    'hard_skills' => [
                                        'Vacatureteksten_Schrijven',
                                        'Candidate_Sourcing',
                                        'LinkedIn_Recruitment',
                                        'Interviewtechnieken',
                                        'Selectiecriteria_Opmaken',
                                        'ATS_Systemen_Gebruik',
                                        'Arbeidsmarktkennis',
                                        'Screenen_Van_CVs',
                                        'Referentiechecks',
                                        'Aanbod_En_Contractafhandeling',
                                    ],
                                    'soft_skills' => [
                                        'Communicatieve_Vaardigheden',
                                        'Relatieopbouw',
                                        'Luistervaardigheid',
                                        'Organisatorisch_Vermogen',
                                        'Snel_Schakelen',
                                        'Proactieve_Houding',
                                        'Resultaatgerichtheid',
                                        'Empathie',
                                        'Overtuigingskracht',
                                        'Netwerken',
                                    ],
                                ],
                                'Klantenservice_Medewerker' => [
                                    'hard_skills' => [
                                        'Telefoonvaardigheid',
                                        'E_mail_En_Chat_Afhandeling',
                                        'Ticketingsystemen',
                                        'Basis_Administratie',
                                        'Product_Of_Dienstkennis',
                                        'Klachtenafhandeling',
                                        'Registratie_Van_Calls',
                                        'Basis_IT_Vaardigheden',
                                        'Script_Volgen_En_Aanpassen',
                                    ],
                                    'soft_skills' => [
                                        'Klantgerichtheid',
                                        'Geduld',
                                        'Luistervaardigheid',
                                        'Stressbestendigheid',
                                        'Empathie',
                                        'Duidelijk_Formuleren',
                                        'Oplossingsgericht_Denken',
                                        'Teamwork',
                                        'Flexibiliteit',
                                        'Omgaan_Met_Weerstand',
                                    ],
                                ],
                                'Boekhouder' => [
                                    'hard_skills' => [
                                        'Financiele_Administratie',
                                        'Grootboekboekingen',
                                        'Crediteurenbeheer',
                                        'Debiteurenbeheer',
                                        'BTW_Aangifte',
                                        'Jaarafsluiting_Ondersteuning',
                                        'Excel_Gevorderd',
                                        'Boekhoudpakketten_Exact_Of_Similar',
                                        'Bankboekingen',
                                        'Kosten_En_Opbrengstenanalyse',
                                    ],
                                    'soft_skills' => [
                                        'Nauwkeurigheid',
                                        'Structuur_En_Orde',
                                        'Betrouwbaarheid',
                                        'Discretie',
                                        'Analytisch_Denken',
                                        'Tijdmanagement',
                                        'Probleemoplossend_Vermogen',
                                        'Communicatie_Met_Collegas',
                                        'Verantwoordelijkheidsgevoel',
                                    ],
                                ],
                                'Data_Scientist' => [
                                    'hard_skills' => [
                                        'Statistiek',
                                        'Data_Analyse',
                                        'Python_Of_R',
                                        'Machine_Learning_Basics',
                                        'SQL',
                                        'Datavisualisatie',
                                        'Pandas_Of_Vergelijkbaar',
                                        'Feature_Engineering',
                                        'Model_Validatie',
                                        'A_B_Testing',
                                        'Data_Cleaning',
                                        'Dashboarding_Tools',
                                    ],
                                    'soft_skills' => [
                                        'Analytisch_Denken',
                                        'Probleemoplossend_Vermogen',
                                        'Nieuwsgierigheid',
                                        'Communicatieve_Vaardigheden',
                                        'Complexe_Zaken_Eenvoudig_Uitleggen',
                                        'Zelfstandigheid',
                                        'Samenwerken_Met_Niet_Technische_Stakeholders',
                                        'Kritisch_Denken',
                                        'Nauwkeurigheid',
                                    ],
                                ],
                                'Verpleegkundige' => [
                                    'hard_skills' => [
                                        'Verpleegtechnische_Handelingen',
                                        'Medicatie_Toedienen',
                                        'Observatie_Van_Patienten',
                                        'Dossiervoering',
                                        'Triageren',
                                        'Basis_Life_Support',
                                        'Hygieneprotocollen',
                                        'Wondzorg',
                                        'Overdracht_Schrijven',
                                        'Samenwerken_Met_Artsen_En_Therapeuten',
                                    ],
                                    'soft_skills' => [
                                        'Empathie',
                                        'Stressbestendigheid',
                                        'Teamwork',
                                        'Communicatieve_Vaardigheden',
                                        'Zorgvuldigheid',
                                        'Flexibiliteit',
                                        'Besluitvaardigheid_In_Druk',
                                        'Geduld',
                                        'Verantwoordelijkheidsgevoel',
                                        'Omgaan_Met_Emoties_Van_Anderen',
                                    ],
                                ],
                                'Arts' => [
                                    'hard_skills' => [
                                        'Medische_Diagnostiek',
                                        'Anamnese_Afnemen',
                                        'Lichamelijk_Onderzoek',
                                        'Behandelplannen_Opmaken',
                                        'Voorschrijven_Van_Medicatie',
                                        'Interpretatie_Van_Lab_Uitslagen',
                                        'Kenntnis_Richtlijnen_En_Protocollen',
                                        'Acute_Zorg_Basis',
                                        'Dossiervoering',
                                        'Multidisciplinaire_Samenwerking',
                                    ],
                                    'soft_skills' => [
                                        'Empathie',
                                        'Communicatieve_Vaardigheden',
                                        'Besluitvaardigheid',
                                        'Stressbestendigheid',
                                        'Ethiek_En_Integriteit',
                                        'Luistervaardigheid',
                                        'Uitleggen_Van_Complexe_Informatie',
                                        'Samenwerken',
                                        'Leiderschap_In_Behandelteam',
                                        'Reflectievermogen',
                                    ],
                                ],
                                'Docent' => [
                                    'hard_skills' => [
                                        'Lesvoorbereiding',
                                        'Didactische_Vaardigheden',
                                        'Klassenmanagement',
                                        'Toetsen_Ontwerpen',
                                        'Toetsen_Nakijken',
                                        'Digitale_Lesmiddelen_Gebruik',
                                        'Differentiatie_In_De_Klas',
                                        'Leerdoelen_Formuleren',
                                        'Evalueren_Van_Leren',
                                        'Basis_Administratie_Resultaten',
                                    ],
                                    'soft_skills' => [
                                        'Communicatieve_Vaardigheden',
                                        'Geduld',
                                        'Klassenleiderschap',
                                        'Motiveren_Van_Leerlingen',
                                        'Empathie',
                                        'Creativiteit',
                                        'Flexibiliteit',
                                        'Consequent_Handelen',
                                        'Samenwerken_Met_Collegas',
                                        'Contact_Met_Ouders',
                                    ],
                                ],
                                'Grafisch_Ontwerper' => [
                                    'hard_skills' => [
                                        'Adobe_Photoshop',
                                        'Adobe_Illustrator',
                                        'Adobe_InDesign',
                                        'Typografie',
                                        'Kleurgebruik',
                                        'Layout_Design',
                                        'Branding_En_Huisstijl',
                                        'Bestandsvoorbereiding_Drukwerk',
                                        'Wireframing_Basis',
                                        'Digitale_Assets_Maken_Web_Social',
                                    ],
                                    'soft_skills' => [
                                        'Creatief_Denken',
                                        'Oog_Voor_Detail',
                                        'Tijdsbeheer',
                                        'Feedback_Verwerken',
                                        'Communicatie_Met_Opdrachtgevers',
                                        'Samenwerken_Met_Team',
                                        'Probleemoplossend_Denken',
                                        'Aanpassingsvermogen',
                                        'Zelfstandigheid',
                                    ],
                                ],
                                'Product_Owner' => [
                                    'hard_skills' => [
                                        'Product_Backlog_Management',
                                        'User_Stories_Schrijven',
                                        'Prioriteren_Volgens_Waarde',
                                        'Stakeholdermanagement',
                                        'Agile_Scrum_Kennis',
                                        'Roadmap_Planning',
                                        'Basis_Datagedreven_Beslissen',
                                        'Release_Planning',
                                        'Acceptatiecriteria_Formuleren',
                                    ],
                                    'soft_skills' => [
                                        'Communicatieve_Vaardigheden',
                                        'Stakeholder_Afstemming',
                                        'Besluitvaardigheid',
                                        'Visionair_Denken',
                                        'Prioriteiten_Stellen',
                                        'Samenwerken_Met_Development_Team',
                                        'Luistervaardigheid',
                                        'Overtuigingskracht',
                                        'Flexibiliteit',
                                        'Resultaatgerichtheid',
                                    ],
                                ],
                                'Logistiek_Medewerker' => [
                                    'hard_skills' => [
                                        'Orderpicking',
                                        'Voorraadbeheer_Basis',
                                        'Magazijnsystemen_WMS',
                                        'Scannen_En_Inboeken',
                                        'Verzendklaar_Maken',
                                        'In_En_Uitpakken',
                                        'Basis_Veiligheidsvoorschriften',
                                        'Eventueel_Heftruck_Rijden',
                                        'Retourenverwerking',
                                    ],
                                    'soft_skills' => [
                                        'Nauwkeurigheid',
                                        'Fysieke_Belastbaarheid',
                                        'Teamwork',
                                        'Tijdsdruk_Aankunnen',
                                        'Discipline',
                                        'Zelfstandigheid',
                                        'Verantwoordelijkheidsgevoel',
                                        'Communicatie_Met_Collegas',
                                    ],
                                ],
                            ];

                            $now = now();

                            foreach ($data as $functionName => $skillSets) {
                                // Find function by name (normalized: underscores -> spaces for matching)
                                $functionNameNormalized = str_replace('_', ' ', $functionName);
                                $function = DB::table('branch_functions')
                                    ->join('branches', 'branch_functions.branch_id', '=', 'branches.id')
                                    ->where(function ($q) use ($functionName, $functionNameNormalized) {
                                        $q->where('branch_functions.name', $functionName)
                                            ->orWhere('branch_functions.name', $functionNameNormalized);
                                    })
                                    ->select('branch_functions.id', 'branch_functions.name')
                                    ->first();

                                if (! $function) {
                                    continue; // Skip if function doesn't exist
                                }

                                $allSkills = array_merge(
                                    $skillSets['hard_skills'] ?? [],
                                    $skillSets['soft_skills'] ?? []
                                );

                                foreach ($allSkills as $skillName) {
                                    DB::table('branch_function_skills')->updateOrInsert(
                                        [
                                            'branch_function_id' => $function->id,
                                            'name' => $skillName,
                                        ],
                                        [
                                            'created_at' => $now,
                                            'updated_at' => $now,
                                        ]
                                    );
                                }
                            }
                        }

                        public function down(): void
                        {
                            // Intentionally left blank (data migration).
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_12_14_120000_add_skills_to_all_software_engineer_functions.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            if (! Schema::hasTable('branch_functions') || ! Schema::hasTable('branch_function_skills')) {
                                return;
                            }

                            $connection = DB::connection('pgsql');
                            $now = now();

                            // Alle functies met hun skills (uit de seed migration)
                            $functionSkillsMap = [
                                'Software_Engineer' => [
                                    'Programmeren_JavaScript', 'Programmeren_PHP', 'Programmeren_Python', 'Object_Oriented_Programming',
                                    'REST_API_Design', 'Databases_SQL', 'Versiebeheer_Git', 'Design_Patterns', 'Unit_Tests_Schrijven',
                                    'Debuggen', 'CI_CD_Pipelines', 'Cloud_Basics_AWS_Azure_GCP', 'Security_Basics_OWASP',
                                    'Laravel_Of_Ander_Framework', 'Code_Reviews', 'Probleemoplossend_Vermogen', 'Analytisch_Denken',
                                    'Samenwerken', 'Communicatieve_Vaardigheden', 'Zelfstandigheid', 'Plannen_En_Organiseren',
                                    'Nauwkeurigheid', 'Lerend_Vermogen', 'Omgaan_Met_Feedback', 'Proactieve_Houding',
                                ],
                                'Projectmanager' => [
                                    'Projectplanning', 'Scope_Management', 'Risicomanagement', 'Budgetbeheer', 'Stakeholdermanagement',
                                    'Resource_Planning', 'Scrum_Kennis', 'Agile_Methodieken', 'Prince2_Of_PMP_Basis', 'Rapportage_Opstellen',
                                    'MS_Project_Of_Alternatief', 'Change_Management', 'Kwaliteitsbewaking', 'Leiderschap', 'Besluitvaardigheid',
                                    'Communicatieve_Vaardigheden', 'Conflicthantering', 'Onderhandelingsvaardigheden', 'Resultaatgerichtheid',
                                    'Stressbestendigheid', 'Helikopterview', 'Organisatorisch_Vermogen', 'Samenwerken',
                                ],
                                'Accountmanager' => [
                                    'Relatiebeheer', 'New_Business_Acquisitie', 'Sales_Funnels_Beheren', 'Offertes_Maken',
                                    'Contractonderhandelingen', 'CRM_Systeem_Gebruik', 'Marktanalyse', 'Pipeline_Management',
                                    'Presentatievaardigheden', 'Forecasting', 'Up_En_Cross_Selling', 'Communicatieve_Vaardigheden',
                                    'Luistervaardigheid', 'Overtuigingskracht', 'Klantgerichtheid', 'Netwerken', 'Resultaatgerichtheid',
                                    'Doorzettingsvermogen', 'Relatiegericht_Denken', 'Empathisch_Vermogen', 'Zelfdiscipline',
                                ],
                                'Marketingmanager' => [
                                    'Marketingstrategie_Ontwikkelen', 'Campagneplanning', 'Digital_Marketing', 'SEO_Basis', 'SEA_Basis',
                                    'Social_Media_Advertising', 'Contentstrategie', 'E_mailmarketing', 'Marketingautomatisering',
                                    'Data_Analyseren_Google_Analytics_Of_Similar', 'Budgetbeheer', 'Marktonderzoek', 'Brand_Management',
                                    'Creatief_Denken', 'Analytisch_Denken', 'Communicatieve_Vaardigheden', 'Leiderschap', 'Samenwerken',
                                    'Strategisch_Denken', 'Besluitvaardigheid', 'Organisatorisch_Vermogen', 'Resultaatgerichtheid', 'Aanpassingsvermogen',
                                ],
                                'Salesmanager' => [
                                    'Salesstrategie_Ontwikkelen', 'Teamsturing', 'Targetsetting', 'Performance_Analyse', 'Forecasting',
                                    'Sales_Coaching', 'CRM_Gebruik', 'Key_Account_Management', 'Onderhandelingsstrategien', 'Rapportage_Opstellen',
                                    'Leiderschap', 'Motiveren_Van_Anderen', 'Resultaatgerichtheid', 'Communicatieve_Vaardigheden', 'Overtuigingskracht',
                                    'Conflicthantering', 'Besluitvaardigheid', 'Stressbestendigheid', 'Veranderingsbereidheid', 'Empathie',
                                ],
                                'HR_Manager' => [
                                    'HR_Beleid_Ontwikkelen', 'Werving_En_Selectie', 'Arbeidsrecht_Basis', 'Performance_Management',
                                    'Verzuimbegeleiding', 'Compensatie_En_Benefits', 'HR_Data_Analyse', 'Training_En_Ontwikkeling',
                                    'Functie_En_Salarishuis', 'Medewerkerstevredenheidsonderzoek', 'Communicatieve_Vaardigheden', 'Conflicthantering',
                                    'Coaching', 'Integer_Handelen', 'Organisatiesensitiviteit', 'Empathisch_Vermogen', 'Luistervaardigheid',
                                    'Besluitvaardigheid', 'Samenwerken', 'Adviesvaardigheden',
                                ],
                                'Recruiter' => [
                                    'Vacatureteksten_Schrijven', 'Candidate_Sourcing', 'LinkedIn_Recruitment', 'Interviewtechnieken',
                                    'Selectiecriteria_Opmaken', 'ATS_Systemen_Gebruik', 'Arbeidsmarktkennis', 'Screenen_Van_CVs',
                                    'Referentiechecks', 'Aanbod_En_Contractafhandeling', 'Communicatieve_Vaardigheden', 'Relatieopbouw',
                                    'Luistervaardigheid', 'Organisatorisch_Vermogen', 'Snel_Schakelen', 'Proactieve_Houding',
                                    'Resultaatgerichtheid', 'Empathie', 'Overtuigingskracht', 'Netwerken',
                                ],
                                'Klantenservice_Medewerker' => [
                                    'Telefoonvaardigheid', 'E_mail_En_Chat_Afhandeling', 'Ticketingsystemen', 'Basis_Administratie',
                                    'Product_Of_Dienstkennis', 'Klachtenafhandeling', 'Registratie_Van_Calls', 'Basis_IT_Vaardigheden',
                                    'Script_Volgen_En_Aanpassen', 'Klantgerichtheid', 'Geduld', 'Luistervaardigheid', 'Stressbestendigheid',
                                    'Empathie', 'Duidelijk_Formuleren', 'Oplossingsgericht_Denken', 'Teamwork', 'Flexibiliteit', 'Omgaan_Met_Weerstand',
                                ],
                                'Boekhouder' => [
                                    'Financiele_Administratie', 'Grootboekboekingen', 'Crediteurenbeheer', 'Debiteurenbeheer', 'BTW_Aangifte',
                                    'Jaarafsluiting_Ondersteuning', 'Excel_Gevorderd', 'Boekhoudpakketten_Exact_Of_Similar', 'Bankboekingen',
                                    'Kosten_En_Opbrengstenanalyse', 'Nauwkeurigheid', 'Structuur_En_Orde', 'Betrouwbaarheid', 'Discretie',
                                    'Analytisch_Denken', 'Tijdmanagement', 'Probleemoplossend_Vermogen', 'Communicatie_Met_Collegas', 'Verantwoordelijkheidsgevoel',
                                ],
                                'Data_Scientist' => [
                                    'Statistiek', 'Data_Analyse', 'Python_Of_R', 'Machine_Learning_Basics', 'SQL', 'Datavisualisatie',
                                    'Pandas_Of_Vergelijkbaar', 'Feature_Engineering', 'Model_Validatie', 'A_B_Testing', 'Data_Cleaning',
                                    'Dashboarding_Tools', 'Analytisch_Denken', 'Probleemoplossend_Vermogen', 'Nieuwsgierigheid',
                                    'Communicatieve_Vaardigheden', 'Complexe_Zaken_Eenvoudig_Uitleggen', 'Zelfstandigheid',
                                    'Samenwerken_Met_Niet_Technische_Stakeholders', 'Kritisch_Denken', 'Nauwkeurigheid',
                                ],
                                'Verpleegkundige' => [
                                    'Verpleegtechnische_Handelingen', 'Medicatie_Toedienen', 'Observatie_Van_Patienten', 'Dossiervoering',
                                    'Triageren', 'Basis_Life_Support', 'Hygieneprotocollen', 'Wondzorg', 'Overdracht_Schrijven',
                                    'Samenwerken_Met_Artsen_En_Therapeuten', 'Empathie', 'Stressbestendigheid', 'Teamwork',
                                    'Communicatieve_Vaardigheden', 'Zorgvuldigheid', 'Flexibiliteit', 'Besluitvaardigheid_In_Druk',
                                    'Geduld', 'Verantwoordelijkheidsgevoel', 'Omgaan_Met_Emoties_Van_Anderen',
                                ],
                                'Arts' => [
                                    'Medische_Diagnostiek', 'Anamnese_Afnemen', 'Lichamelijk_Onderzoek', 'Behandelplannen_Opmaken',
                                    'Voorschrijven_Van_Medicatie', 'Interpretatie_Van_Lab_Uitslagen', 'Kenntnis_Richtlijnen_En_Protocollen',
                                    'Acute_Zorg_Basis', 'Dossiervoering', 'Multidisciplinaire_Samenwerking', 'Empathie',
                                    'Communicatieve_Vaardigheden', 'Besluitvaardigheid', 'Stressbestendigheid', 'Ethiek_En_Integriteit',
                                    'Luistervaardigheid', 'Uitleggen_Van_Complexe_Informatie', 'Samenwerken', 'Leiderschap_In_Behandelteam', 'Reflectievermogen',
                                ],
                                'Docent' => [
                                    'Lesvoorbereiding', 'Didactische_Vaardigheden', 'Klassenmanagement', 'Toetsen_Ontwerpen', 'Toetsen_Nakijken',
                                    'Digitale_Lesmiddelen_Gebruik', 'Differentiatie_In_De_Klas', 'Leerdoelen_Formuleren', 'Evalueren_Van_Leren',
                                    'Basis_Administratie_Resultaten', 'Communicatieve_Vaardigheden', 'Geduld', 'Klassenleiderschap',
                                    'Motiveren_Van_Leerlingen', 'Empathie', 'Creativiteit', 'Flexibiliteit', 'Consequent_Handelen',
                                    'Samenwerken_Met_Collegas', 'Contact_Met_Ouders',
                                ],
                                'Grafisch_Ontwerper' => [
                                    'Adobe_Photoshop', 'Adobe_Illustrator', 'Adobe_InDesign', 'Typografie', 'Kleurgebruik', 'Layout_Design',
                                    'Branding_En_Huisstijl', 'Bestandsvoorbereiding_Drukwerk', 'Wireframing_Basis', 'Digitale_Assets_Maken_Web_Social',
                                    'Creatief_Denken', 'Oog_Voor_Detail', 'Tijdsbeheer', 'Feedback_Verwerken', 'Communicatie_Met_Opdrachtgevers',
                                    'Samenwerken_Met_Team', 'Probleemoplossend_Denken', 'Aanpassingsvermogen', 'Zelfstandigheid',
                                ],
                                'Product_Owner' => [
                                    'Product_Backlog_Management', 'User_Stories_Schrijven', 'Prioriteren_Volgens_Waarde', 'Stakeholdermanagement',
                                    'Agile_Scrum_Kennis', 'Roadmap_Planning', 'Basis_Datagedreven_Beslissen', 'Release_Planning',
                                    'Acceptatiecriteria_Formuleren', 'Communicatieve_Vaardigheden', 'Stakeholder_Afstemming', 'Besluitvaardigheid',
                                    'Visionair_Denken', 'Prioriteiten_Stellen', 'Samenwerken_Met_Development_Team', 'Luistervaardigheid',
                                    'Overtuigingskracht', 'Flexibiliteit', 'Resultaatgerichtheid',
                                ],
                                'Logistiek_Medewerker' => [
                                    'Orderpicking', 'Voorraadbeheer_Basis', 'Magazijnsystemen_WMS', 'Scannen_En_Inboeken', 'Verzendklaar_Maken',
                                    'In_En_Uitpakken', 'Basis_Veiligheidsvoorschriften', 'Eventueel_Heftruck_Rijden', 'Retourenverwerking',
                                    'Nauwkeurigheid', 'Fysieke_Belastbaarheid', 'Teamwork', 'Tijdsdruk_Aankunnen', 'Discipline',
                                    'Zelfstandigheid', 'Verantwoordelijkheidsgevoel', 'Communicatie_Met_Collegas',
                                ],
                            ];

                            foreach ($functionSkillsMap as $functionName => $skills) {
                                // Vind alle functies met deze naam (in alle branches)
                                $functions = $connection->table('branch_functions')
                                    ->where('name', $functionName)
                                    ->get();

                                foreach ($functions as $function) {
                                    // Check hoeveel skills deze functie al heeft
                                    $existingCount = $connection->table('branch_function_skills')
                                        ->where('branch_function_id', $function->id)
                                        ->count();

                                    // Alleen toevoegen als er nog geen skills zijn
                                    if ($existingCount === 0) {
                                        foreach ($skills as $skillName) {
                                            $connection->table('branch_function_skills')->updateOrInsert(
                                                [
                                                    'branch_function_id' => $function->id,
                                                    'name' => $skillName,
                                                ],
                                                [
                                                    'created_at' => $now,
                                                    'updated_at' => $now,
                                                ]
                                            );
                                        }
                                    }
                                }
                            }
                        }

                        public function down(): void
                        {
                            // Intentionally left blank (data migration).
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_12_14_130000_fix_function_branches_and_add_skills.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            if (! Schema::hasTable('branch_functions') || ! Schema::hasTable('branch_function_skills') || ! Schema::hasTable('branches')) {
                                return;
                            }

                            $connection = DB::connection('pgsql');
                            $now = now();

                            // Mapping van functies naar branches en hun skills
                            $functionData = [
                                'Software_Engineer' => [
                                    'branch' => 'IT',
                                    'skills' => [
                                        'Programmeren_JavaScript', 'Programmeren_PHP', 'Programmeren_Python', 'Object_Oriented_Programming',
                                        'REST_API_Design', 'Databases_SQL', 'Versiebeheer_Git', 'Design_Patterns', 'Unit_Tests_Schrijven',
                                        'Debuggen', 'CI_CD_Pipelines', 'Cloud_Basics_AWS_Azure_GCP', 'Security_Basics_OWASP',
                                        'Laravel_Of_Ander_Framework', 'Code_Reviews', 'Probleemoplossend_Vermogen', 'Analytisch_Denken',
                                        'Samenwerken', 'Communicatieve_Vaardigheden', 'Zelfstandigheid', 'Plannen_En_Organiseren',
                                        'Nauwkeurigheid', 'Lerend_Vermogen', 'Omgaan_Met_Feedback', 'Proactieve_Houding',
                                    ],
                                ],
                                'Projectmanager' => [
                                    'branch' => 'Consulting',
                                    'skills' => [
                                        'Projectplanning', 'Scope_Management', 'Risicomanagement', 'Budgetbeheer', 'Stakeholdermanagement',
                                        'Resource_Planning', 'Scrum_Kennis', 'Agile_Methodieken', 'Prince2_Of_PMP_Basis', 'Rapportage_Opstellen',
                                        'MS_Project_Of_Alternatief', 'Change_Management', 'Kwaliteitsbewaking', 'Leiderschap', 'Besluitvaardigheid',
                                        'Communicatieve_Vaardigheden', 'Conflicthantering', 'Onderhandelingsvaardigheden', 'Resultaatgerichtheid',
                                        'Stressbestendigheid', 'Helikopterview', 'Organisatorisch_Vermogen', 'Samenwerken',
                                    ],
                                ],
                                'Accountmanager' => [
                                    'branch' => 'Sales',
                                    'skills' => [
                                        'Relatiebeheer', 'New_Business_Acquisitie', 'Sales_Funnels_Beheren', 'Offertes_Maken',
                                        'Contractonderhandelingen', 'CRM_Systeem_Gebruik', 'Marktanalyse', 'Pipeline_Management',
                                        'Presentatievaardigheden', 'Forecasting', 'Up_En_Cross_Selling', 'Communicatieve_Vaardigheden',
                                        'Luistervaardigheid', 'Overtuigingskracht', 'Klantgerichtheid', 'Netwerken', 'Resultaatgerichtheid',
                                        'Doorzettingsvermogen', 'Relatiegericht_Denken', 'Empathisch_Vermogen', 'Zelfdiscipline',
                                    ],
                                ],
                                'Marketingmanager' => [
                                    'branch' => 'Marketing',
                                    'skills' => [
                                        'Marketingstrategie_Ontwikkelen', 'Campagneplanning', 'Digital_Marketing', 'SEO_Basis', 'SEA_Basis',
                                        'Social_Media_Advertising', 'Contentstrategie', 'E_mailmarketing', 'Marketingautomatisering',
                                        'Data_Analyseren_Google_Analytics_Of_Similar', 'Budgetbeheer', 'Marktonderzoek', 'Brand_Management',
                                        'Creatief_Denken', 'Analytisch_Denken', 'Communicatieve_Vaardigheden', 'Leiderschap', 'Samenwerken',
                                        'Strategisch_Denken', 'Besluitvaardigheid', 'Organisatorisch_Vermogen', 'Resultaatgerichtheid', 'Aanpassingsvermogen',
                                    ],
                                ],
                                'Salesmanager' => [
                                    'branch' => 'Sales',
                                    'skills' => [
                                        'Salesstrategie_Ontwikkelen', 'Teamsturing', 'Targetsetting', 'Performance_Analyse', 'Forecasting',
                                        'Sales_Coaching', 'CRM_Gebruik', 'Key_Account_Management', 'Onderhandelingsstrategien', 'Rapportage_Opstellen',
                                        'Leiderschap', 'Motiveren_Van_Anderen', 'Resultaatgerichtheid', 'Communicatieve_Vaardigheden', 'Overtuigingskracht',
                                        'Conflicthantering', 'Besluitvaardigheid', 'Stressbestendigheid', 'Veranderingsbereidheid', 'Empathie',
                                    ],
                                ],
                                'HR_Manager' => [
                                    'branch' => 'HR',
                                    'skills' => [
                                        'HR_Beleid_Ontwikkelen', 'Werving_En_Selectie', 'Arbeidsrecht_Basis', 'Performance_Management',
                                        'Verzuimbegeleiding', 'Compensatie_En_Benefits', 'HR_Data_Analyse', 'Training_En_Ontwikkeling',
                                        'Functie_En_Salarishuis', 'Medewerkerstevredenheidsonderzoek', 'Communicatieve_Vaardigheden', 'Conflicthantering',
                                        'Coaching', 'Integer_Handelen', 'Organisatiesensitiviteit', 'Empathisch_Vermogen', 'Luistervaardigheid',
                                        'Besluitvaardigheid', 'Samenwerken', 'Adviesvaardigheden',
                                    ],
                                ],
                                'Recruiter' => [
                                    'branch' => 'HR',
                                    'skills' => [
                                        'Vacatureteksten_Schrijven', 'Candidate_Sourcing', 'LinkedIn_Recruitment', 'Interviewtechnieken',
                                        'Selectiecriteria_Opmaken', 'ATS_Systemen_Gebruik', 'Arbeidsmarktkennis', 'Screenen_Van_CVs',
                                        'Referentiechecks', 'Aanbod_En_Contractafhandeling', 'Communicatieve_Vaardigheden', 'Relatieopbouw',
                                        'Luistervaardigheid', 'Organisatorisch_Vermogen', 'Snel_Schakelen', 'Proactieve_Houding',
                                        'Resultaatgerichtheid', 'Empathie', 'Overtuigingskracht', 'Netwerken',
                                    ],
                                ],
                                'Klantenservice_Medewerker' => [
                                    'branch' => 'Customer_Service',
                                    'skills' => [
                                        'Telefoonvaardigheid', 'E_mail_En_Chat_Afhandeling', 'Ticketingsystemen', 'Basis_Administratie',
                                        'Product_Of_Dienstkennis', 'Klachtenafhandeling', 'Registratie_Van_Calls', 'Basis_IT_Vaardigheden',
                                        'Script_Volgen_En_Aanpassen', 'Klantgerichtheid', 'Geduld', 'Luistervaardigheid', 'Stressbestendigheid',
                                        'Empathie', 'Duidelijk_Formuleren', 'Oplossingsgericht_Denken', 'Teamwork', 'Flexibiliteit', 'Omgaan_Met_Weerstand',
                                    ],
                                ],
                                'Boekhouder' => [
                                    'branch' => 'Accounting',
                                    'skills' => [
                                        'Financiele_Administratie', 'Grootboekboekingen', 'Crediteurenbeheer', 'Debiteurenbeheer', 'BTW_Aangifte',
                                        'Jaarafsluiting_Ondersteuning', 'Excel_Gevorderd', 'Boekhoudpakketten_Exact_Of_Similar', 'Bankboekingen',
                                        'Kosten_En_Opbrengstenanalyse', 'Nauwkeurigheid', 'Structuur_En_Orde', 'Betrouwbaarheid', 'Discretie',
                                        'Analytisch_Denken', 'Tijdmanagement', 'Probleemoplossend_Vermogen', 'Communicatie_Met_Collegas', 'Verantwoordelijkheidsgevoel',
                                    ],
                                ],
                                'Data_Scientist' => [
                                    'branch' => 'IT',
                                    'skills' => [
                                        'Statistiek', 'Data_Analyse', 'Python_Of_R', 'Machine_Learning_Basics', 'SQL', 'Datavisualisatie',
                                        'Pandas_Of_Vergelijkbaar', 'Feature_Engineering', 'Model_Validatie', 'A_B_Testing', 'Data_Cleaning',
                                        'Dashboarding_Tools', 'Analytisch_Denken', 'Probleemoplossend_Vermogen', 'Nieuwsgierigheid',
                                        'Communicatieve_Vaardigheden', 'Complexe_Zaken_Eenvoudig_Uitleggen', 'Zelfstandigheid',
                                        'Samenwerken_Met_Niet_Technische_Stakeholders', 'Kritisch_Denken', 'Nauwkeurigheid',
                                    ],
                                ],
                                'Verpleegkundige' => [
                                    'branch' => 'Healthcare',
                                    'skills' => [
                                        'Verpleegtechnische_Handelingen', 'Medicatie_Toedienen', 'Observatie_Van_Patienten', 'Dossiervoering',
                                        'Triageren', 'Basis_Life_Support', 'Hygieneprotocollen', 'Wondzorg', 'Overdracht_Schrijven',
                                        'Samenwerken_Met_Artsen_En_Therapeuten', 'Empathie', 'Stressbestendigheid', 'Teamwork',
                                        'Communicatieve_Vaardigheden', 'Zorgvuldigheid', 'Flexibiliteit', 'Besluitvaardigheid_In_Druk',
                                        'Geduld', 'Verantwoordelijkheidsgevoel', 'Omgaan_Met_Emoties_Van_Anderen',
                                    ],
                                ],
                                'Arts' => [
                                    'branch' => 'Healthcare',
                                    'skills' => [
                                        'Medische_Diagnostiek', 'Anamnese_Afnemen', 'Lichamelijk_Onderzoek', 'Behandelplannen_Opmaken',
                                        'Voorschrijven_Van_Medicatie', 'Interpretatie_Van_Lab_Uitslagen', 'Kenntnis_Richtlijnen_En_Protocollen',
                                        'Acute_Zorg_Basis', 'Dossiervoering', 'Multidisciplinaire_Samenwerking', 'Empathie',
                                        'Communicatieve_Vaardigheden', 'Besluitvaardigheid', 'Stressbestendigheid', 'Ethiek_En_Integriteit',
                                        'Luistervaardigheid', 'Uitleggen_Van_Complexe_Informatie', 'Samenwerken', 'Leiderschap_In_Behandelteam', 'Reflectievermogen',
                                    ],
                                ],
                                'Docent' => [
                                    'branch' => 'Education',
                                    'skills' => [
                                        'Lesvoorbereiding', 'Didactische_Vaardigheden', 'Klassenmanagement', 'Toetsen_Ontwerpen', 'Toetsen_Nakijken',
                                        'Digitale_Lesmiddelen_Gebruik', 'Differentiatie_In_De_Klas', 'Leerdoelen_Formuleren', 'Evalueren_Van_Leren',
                                        'Basis_Administratie_Resultaten', 'Communicatieve_Vaardigheden', 'Geduld', 'Klassenleiderschap',
                                        'Motiveren_Van_Leerlingen', 'Empathie', 'Creativiteit', 'Flexibiliteit', 'Consequent_Handelen',
                                        'Samenwerken_Met_Collegas', 'Contact_Met_Ouders',
                                    ],
                                ],
                                'Grafisch_Ontwerper' => [
                                    'branch' => 'Kunst',
                                    'skills' => [
                                        'Adobe_Photoshop', 'Adobe_Illustrator', 'Adobe_InDesign', 'Typografie', 'Kleurgebruik', 'Layout_Design',
                                        'Branding_En_Huisstijl', 'Bestandsvoorbereiding_Drukwerk', 'Wireframing_Basis', 'Digitale_Assets_Maken_Web_Social',
                                        'Creatief_Denken', 'Oog_Voor_Detail', 'Tijdsbeheer', 'Feedback_Verwerken', 'Communicatie_Met_Opdrachtgevers',
                                        'Samenwerken_Met_Team', 'Probleemoplossend_Denken', 'Aanpassingsvermogen', 'Zelfstandigheid',
                                    ],
                                ],
                                'Product_Owner' => [
                                    'branch' => 'IT',
                                    'skills' => [
                                        'Product_Backlog_Management', 'User_Stories_Schrijven', 'Prioriteren_Volgens_Waarde', 'Stakeholdermanagement',
                                        'Agile_Scrum_Kennis', 'Roadmap_Planning', 'Basis_Datagedreven_Beslissen', 'Release_Planning',
                                        'Acceptatiecriteria_Formuleren', 'Communicatieve_Vaardigheden', 'Stakeholder_Afstemming', 'Besluitvaardigheid',
                                        'Visionair_Denken', 'Prioriteiten_Stellen', 'Samenwerken_Met_Development_Team', 'Luistervaardigheid',
                                        'Overtuigingskracht', 'Flexibiliteit', 'Resultaatgerichtheid',
                                    ],
                                ],
                                'Logistiek_Medewerker' => [
                                    'branch' => 'Transportation',
                                    'skills' => [
                                        'Orderpicking', 'Voorraadbeheer_Basis', 'Magazijnsystemen_WMS', 'Scannen_En_Inboeken', 'Verzendklaar_Maken',
                                        'In_En_Uitpakken', 'Basis_Veiligheidsvoorschriften', 'Eventueel_Heftruck_Rijden', 'Retourenverwerking',
                                        'Nauwkeurigheid', 'Fysieke_Belastbaarheid', 'Teamwork', 'Tijdsdruk_Aankunnen', 'Discipline',
                                        'Zelfstandigheid', 'Verantwoordelijkheidsgevoel', 'Communicatie_Met_Collegas',
                                    ],
                                ],
                            ];

                            // Helper functie om branch ID op te halen of aan te maken
                            $getOrCreateBranch = function ($branchName) use ($connection, $now) {
                                $branchSlug = Str::slug($branchName);
                                $branch = $connection->table('branches')
                                    ->where('slug', $branchSlug)
                                    ->orWhere('name', $branchName)
                                    ->first();

                                if (! $branch) {
                                    $branchId = $connection->table('branches')->insertGetId([
                                        'name' => $branchName,
                                        'slug' => $branchSlug,
                                        'description' => null,
                                        'color' => null,
                                        'icon' => null,
                                        'is_active' => true,
                                        'sort_order' => 0,
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                    ]);

                                    return $branchId;
                                }

                                return $branch->id;
                            };

                            foreach ($functionData as $functionName => $data) {
                                $targetBranchName = $data['branch'];
                                $targetBranchId = $getOrCreateBranch($targetBranchName);
                                $skills = $data['skills'];

                                // Vind alle functies met deze naam
                                $functions = $connection->table('branch_functions')
                                    ->where('name', $functionName)
                                    ->get();

                                // Check of er al een functie bestaat in de target branch
                                $existingInTarget = $connection->table('branch_functions')
                                    ->where('branch_id', $targetBranchId)
                                    ->where('name', $functionName)
                                    ->first();

                                $targetFunctionId = null;

                                if ($existingInTarget) {
                                    $targetFunctionId = $existingInTarget->id;
                                } else {
                                    // Maak nieuwe functie aan in target branch
                                    $targetFunctionId = $connection->table('branch_functions')->insertGetId([
                                        'branch_id' => $targetBranchId,
                                        'name' => $functionName,
                                        'created_at' => $now,
                                        'updated_at' => $now,
                                    ]);
                                }

                                // Verplaats skills van alle andere functies naar de target functie
                                foreach ($functions as $function) {
                                    if ($function->id == $targetFunctionId) {
                                        continue; // Skip de target functie zelf
                                    }

                                    // Verplaats skills (vermijd duplicaten)
                                    $oldSkills = $connection->table('branch_function_skills')
                                        ->where('branch_function_id', $function->id)
                                        ->get();

                                    foreach ($oldSkills as $oldSkill) {
                                        // Check of deze skill al bestaat in target functie
                                        $exists = $connection->table('branch_function_skills')
                                            ->where('branch_function_id', $targetFunctionId)
                                            ->where('name', $oldSkill->name)
                                            ->exists();

                                        if (! $exists) {
                                            // Verplaats de skill
                                            $connection->table('branch_function_skills')
                                                ->where('id', $oldSkill->id)
                                                ->update(['branch_function_id' => $targetFunctionId, 'updated_at' => $now]);
                                        } else {
                                            // Verwijder duplicaat
                                            $connection->table('branch_function_skills')
                                                ->where('id', $oldSkill->id)
                                                ->delete();
                                        }
                                    }

                                    // Verwijder de oude functie (als het niet de target is)
                                    $connection->table('branch_functions')
                                        ->where('id', $function->id)
                                        ->delete();
                                }

                                // Zorg dat alle skills aanwezig zijn in de target functie
                                foreach ($skills as $skillName) {
                                    $connection->table('branch_function_skills')->updateOrInsert(
                                        [
                                            'branch_function_id' => $targetFunctionId,
                                            'name' => $skillName,
                                        ],
                                        [
                                            'created_at' => $now,
                                            'updated_at' => $now,
                                        ]
                                    );
                                }

                            }
                        }

                        public function down(): void
                        {
                            // Intentionally left blank (data migration).
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_12_14_212020_add_role_and_permission_management_permissions.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            // Role management permissions
                            $rolePermissions = [
                                'view-roles',
                                'create-roles',
                                'edit-roles',
                                'delete-roles',
                            ];

                            // Permission management permissions
                            $permissionPermissions = [
                                'view-permissions',
                                'create-permissions',
                                'edit-permissions',
                                'delete-permissions',
                            ];

                            $allPermissions = array_merge($rolePermissions, $permissionPermissions);

                            // Create permissions for web guard
                            foreach ($allPermissions as $permissionName) {
                                Permission::firstOrCreate([
                                    'name' => $permissionName,
                                    'guard_name' => 'web',
                                ]);
                            }

                            // Create permissions for api guard
                            foreach ($allPermissions as $permissionName) {
                                Permission::firstOrCreate([
                                    'name' => $permissionName,
                                    'guard_name' => 'api',
                                ]);
                            }

                            // Assign all permissions to super-admin role (web)
                            $superAdmin = Role::where(['name' => 'super-admin', 'guard_name' => 'web'])->first();
                            if ($superAdmin) {
                                $webPermissions = Permission::where('guard_name', 'web')
                                    ->whereIn('name', $allPermissions)
                                    ->get();
                                $superAdmin->givePermissionTo($webPermissions);
                            }

                            // Assign all permissions to super-admin role (api)
                            $apiSuperAdmin = Role::where(['name' => 'super-admin', 'guard_name' => 'api'])->first();
                            if ($apiSuperAdmin) {
                                $apiPermissions = Permission::where('guard_name', 'api')
                                    ->whereIn('name', $allPermissions)
                                    ->get();
                                $apiSuperAdmin->givePermissionTo($apiPermissions);
                            }
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            // Remove permissions
                            $permissions = [
                                'view-roles',
                                'create-roles',
                                'edit-roles',
                                'delete-roles',
                                'view-permissions',
                                'create-permissions',
                                'edit-permissions',
                                'delete-permissions',
                            ];

                            Permission::whereIn('name', $permissions)->delete();
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_12_14_215120_add_is_active_to_roles_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            // Use raw SQL with IF NOT EXISTS for PostgreSQL compatibility
                            // The after() method doesn't work in PostgreSQL
                            if (! Schema::hasColumn('roles', 'is_active')) {
                                if (DB::getDriverName() === 'pgsql') {
                                    DB::statement('ALTER TABLE roles ADD COLUMN IF NOT EXISTS is_active BOOLEAN NOT NULL DEFAULT true');
                                } else {
                                    Schema::table('roles', function (Blueprint $table) {
                                        $table->boolean('is_active')->default(true)->after('description');
                                    });
                                }
                            }
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            // Only drop column if it exists
                            if (Schema::hasColumn('roles', 'is_active')) {
                                Schema::table('roles', function (Blueprint $table) {
                                    $table->dropColumn('is_active');
                                });
                            }
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_12_18_212448_change_user_id_to_candidate_id_in_matches_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('matches', function (Blueprint $table) {
                                // Drop the foreign key constraint on user_id
                                $table->dropForeign(['user_id']);

                                // Rename the column from user_id to candidate_id
                                $table->renameColumn('user_id', 'candidate_id');
                            });

                            // Add the new foreign key constraint on candidate_id
                            Schema::table('matches', function (Blueprint $table) {
                                $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('matches', function (Blueprint $table) {
                                // Drop the foreign key constraint on candidate_id
                                $table->dropForeign(['candidate_id']);

                                // Rename the column back from candidate_id to user_id
                                $table->renameColumn('candidate_id', 'user_id');
                            });

                            // Add the old foreign key constraint back on user_id
                            Schema::table('matches', function (Blueprint $table) {
                                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_12_19_120000_add_contact_person_fields_to_vacancies_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('vacancies', function (Blueprint $table) {
                                $table->string('contact_name')->nullable()->after('location');
                                $table->string('contact_email')->nullable()->after('contact_name');
                                $table->string('contact_phone')->nullable()->after('contact_email');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('vacancies', function (Blueprint $table) {
                                $table->dropColumn(['contact_name', 'contact_email', 'contact_phone']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_12_19_130000_add_contact_photo_to_vacancies_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('vacancies', function (Blueprint $table) {
                                $table->longText('contact_photo_blob')->nullable()->after('contact_phone');
                                $table->string('contact_photo_mime_type')->nullable()->after('contact_photo_blob');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('vacancies', function (Blueprint $table) {
                                $table->dropColumn(['contact_photo_blob', 'contact_photo_mime_type']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_12_19_140000_add_contact_user_id_to_vacancies_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('vacancies', function (Blueprint $table) {
                                $table->unsignedBigInteger('contact_user_id')->nullable()->after('contact_phone');
                                $table->foreign('contact_user_id')->references('id')->on('users')->onDelete('set null');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('vacancies', function (Blueprint $table) {
                                $table->dropForeign(['contact_user_id']);
                                $table->dropColumn('contact_user_id');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_12_25_214305_create_job_configuration_types_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::create('job_configuration_types', function (Blueprint $table) {
                                $table->id();
                                $table->string('name', 50)->unique(); // e.g., 'employment_type', 'working_hours', 'status'
                                $table->string('display_name', 100); // e.g., 'Dienstverband Type', 'Werkuren', 'Status'
                                $table->text('description')->nullable();
                                $table->boolean('is_active')->default(true);
                                $table->integer('sort_order')->default(0);
                                $table->timestamps();

                                $table->index('name');
                                $table->index('is_active');
                            });

                            // Insert default types
                            DB::table('job_configuration_types')->insert([
                                [
                                    'name' => 'employment_type',
                                    'display_name' => 'Dienstverband Type',
                                    'description' => 'Type dienstverband zoals Fulltime, Parttime, etc.',
                                    'is_active' => true,
                                    'sort_order' => 1,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ],
                                [
                                    'name' => 'working_hours',
                                    'display_name' => 'Werkuren',
                                    'description' => 'Werkuren zoals 32-40, 08:00-16:00, etc.',
                                    'is_active' => true,
                                    'sort_order' => 2,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ],
                                [
                                    'name' => 'status',
                                    'display_name' => 'Status',
                                    'description' => 'Status van vacatures zoals Open, Gesloten, etc.',
                                    'is_active' => true,
                                    'sort_order' => 3,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ],
                            ]);
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('job_configuration_types');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2025_12_25_214311_add_type_id_to_job_configurations_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::table('job_configurations', function (Blueprint $table) {
                                $table->foreignId('type_id')->nullable()->after('id')->constrained('job_configuration_types')->nullOnDelete();
                            });

                            // Migrate existing type strings to type_id
                            // This will match existing type strings to the new types table
                            $types = DB::table('job_configuration_types')->pluck('id', 'name');

                            foreach ($types as $typeName => $typeId) {
                                DB::table('job_configurations')
                                    ->where('type', $typeName)
                                    ->update(['type_id' => $typeId]);
                            }
                        }

                        public function down(): void
                        {
                            Schema::table('job_configurations', function (Blueprint $table) {
                                $table->dropForeign(['type_id']);
                                $table->dropColumn('type_id');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_12_26_223744_add_geo_coordinates_to_company_locations_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('company_locations', function (Blueprint $table) {
                                if (! Schema::hasColumn('company_locations', 'latitude')) {
                                    $table->decimal('latitude', 10, 8)->nullable()->after('country');
                                }
                                if (! Schema::hasColumn('company_locations', 'longitude')) {
                                    $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
                                }
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('company_locations', function (Blueprint $table) {
                                $table->dropColumn(['latitude', 'longitude']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_12_26_233237_add_geo_coordinates_to_companies_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('companies', function (Blueprint $table) {
                                if (! Schema::hasColumn('companies', 'latitude')) {
                                    $table->decimal('latitude', 10, 8)->nullable()->after('country');
                                }
                                if (! Schema::hasColumn('companies', 'longitude')) {
                                    $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
                                }
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('companies', function (Blueprint $table) {
                                $table->dropColumn(['latitude', 'longitude']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2025_12_27_170147_create_account_activation_tokens_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('account_activation_tokens', function (Blueprint $table) {
                                $table->id();
                                $table->timestamps();
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('account_activation_tokens');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2026_01_01_120819_add_location_and_user_fields_to_interviews_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('interviews', function (Blueprint $table) {
                                $table->foreignId('company_location_id')->nullable()->after('location')->constrained('company_locations')->nullOnDelete();
                                $table->foreignId('user_id')->nullable()->after('interviewer_email')->constrained('users')->nullOnDelete();
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('interviews', function (Blueprint $table) {
                                $table->dropForeign(['company_location_id']);
                                $table->dropForeign(['user_id']);
                                $table->dropColumn(['company_location_id', 'user_id']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2026_01_03_105639_create_candidate_activities_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('candidate_activities', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('candidate_id')->constrained('candidates')->onDelete('cascade');
                                $table->foreignId('vacancy_id')->constrained('vacancies')->onDelete('cascade');
                                $table->string('action'); // e.g., 'application_created', 'match_created', 'interview_scheduled', 'interview_cancelled', 'interview_reactivated', 'rejected', 'accepted'
                                $table->text('title'); // Human-readable title
                                $table->text('description')->nullable(); // Optional description
                                $table->string('icon')->nullable(); // Icon class name
                                $table->string('color')->nullable(); // Color class name

                                // Optional foreign keys to related entities
                                $table->foreignId('match_id')->nullable()->constrained('matches')->onDelete('set null');
                                $table->foreignId('application_id')->nullable()->constrained('applications')->onDelete('set null');
                                $table->foreignId('interview_id')->nullable()->constrained('interviews')->onDelete('set null');

                                // Metadata as JSON
                                $table->json('metadata')->nullable(); // Store additional data like changes, scores, etc.

                                // User who performed the action
                                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

                                // Timestamp of when the action occurred (can be different from created_at)
                                $table->timestamp('action_at')->useCurrent();

                                $table->timestamps();

                                // Indexes for performance
                                $table->index(['candidate_id', 'vacancy_id', 'action_at']);
                                $table->index(['candidate_id', 'vacancy_id']);
                                $table->index('action');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('candidate_activities');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_03_121632_create_stage_types_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('stage_types', function (Blueprint $table) {
                                $table->id();
                                $table->string('key')->unique(); // e.g., "INTAKE", "TEAM_INTERVIEW"
                                $table->string('default_label'); // e.g., "Intake gesprek"
                                $table->string('category')->default('interview'); // interview, offer, check, etc.
                                $table->integer('typical_duration_minutes')->nullable();
                                $table->boolean('can_schedule')->default(true);
                                $table->boolean('can_collect_feedback')->default(true);
                                $table->json('required_artifacts')->nullable(); // ["interviewers", "scorecard"]
                                $table->json('optional_artifacts')->nullable(); // ["notes", "rating"]
                                $table->json('outcomes')->nullable(); // ["PASS", "FAIL", "ON_HOLD"]
                                $table->json('allowed_next_stage_types')->nullable(); // ["TEAM_INTERVIEW", "REJECTION"]
                                $table->integer('sort_order')->default(0);
                                $table->boolean('is_active')->default(true);
                                $table->timestamps();
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('stage_types');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_03_121633_create_pipeline_templates_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('pipeline_templates', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('company_id')->nullable()->constrained('companies')->onDelete('cascade');
                                $table->string('name'); // e.g., "Standaard sollicitatieflow"
                                $table->string('key')->nullable(); // e.g., "default_general"
                                $table->integer('version')->default(1);
                                $table->boolean('is_default')->default(false);
                                $table->boolean('is_active')->default(true);
                                $table->json('stages'); // Array of stage definitions
                                $table->json('terminal_stages')->nullable(); // ["REJECTION", "WITHDRAWN"]
                                $table->text('description')->nullable();
                                $table->timestamps();

                                $table->index(['company_id', 'is_active']);
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('pipeline_templates');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2026_01_03_121634_create_stage_instances_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('stage_instances', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('application_id')->nullable()->constrained('applications')->onDelete('cascade');
                                $table->foreignId('match_id')->nullable()->constrained('matches')->onDelete('cascade');
                                $table->foreignId('pipeline_template_id')->nullable()->constrained('pipeline_templates')->onDelete('set null');
                                $table->string('stage_type_key'); // Reference to stage_types.key
                                $table->string('label'); // Custom label for this instance
                                $table->integer('sequence'); // Order in pipeline
                                $table->enum('status', ['PENDING', 'SCHEDULED', 'IN_PROGRESS', 'COMPLETED', 'SKIPPED', 'CANCELED'])->default('PENDING');
                                $table->string('outcome')->nullable(); // PASS, FAIL, ON_HOLD, ACCEPTED, DECLINED
                                $table->timestamp('scheduled_at')->nullable();
                                $table->timestamp('started_at')->nullable();
                                $table->timestamp('completed_at')->nullable();
                                $table->json('artifacts')->nullable(); // Store stage-specific data
                                $table->text('notes')->nullable();
                                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                                $table->timestamps();

                                $table->index(['application_id', 'status']);
                                $table->index(['match_id', 'status']);
                                $table->index(['stage_type_key', 'status']);
                                $table->index('sequence');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('stage_instances');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2026_01_04_150000_add_stage_instance_metadata.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::table('stage_instances', function (Blueprint $table) {
                                $table->string('type')->nullable()->after('outcome');
                                $table->unsignedSmallInteger('duration')->nullable()->after('type');
                                $table->string('location_type')->nullable()->after('notes');
                                $table->foreignId('company_location_id')->nullable()->constrained('company_locations')->onDelete('set null')->after('location_type');
                                $table->string('location')->nullable()->after('company_location_id');
                                $table->string('scheduled_time')->nullable()->after('location');
                                $table->foreignId('interviewer_id')->nullable()->constrained('users')->onDelete('set null')->after('scheduled_time');
                                $table->string('interviewer_name')->nullable()->after('interviewer_id');
                                $table->string('interviewer_email')->nullable()->after('interviewer_name');
                            });
                        }

                        public function down(): void
                        {
                            Schema::table('stage_instances', function (Blueprint $table) {
                                $table->dropForeign(['interviewer_id']);
                                $table->dropColumn([
                                    'interviewer_email',
                                    'interviewer_name',
                                    'interviewer_id',
                                    'scheduled_time',
                                    'location',
                                    'company_location_id',
                                    'location_type',
                                    'duration',
                                    'type',
                                ]);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_07_201654_create_chats_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('chats', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                                $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
                                $table->boolean('is_active')->default(true);
                                $table->timestamp('ended_at')->nullable();
                                $table->timestamps();
                                $table->index(['is_active', 'ended_at']);
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('chats');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_07_201701_create_chat_messages_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            if (! Schema::hasTable('chat_messages')) {
                                Schema::create('chat_messages', function (Blueprint $table) {
                                    $table->id();
                                    $table->foreignId('chat_id')->constrained('chats')->onDelete('cascade');
                                    $table->morphs('sender'); // sender_id and sender_type (user or candidate)
                                    $table->text('message');
                                    $table->timestamp('read_at')->nullable();
                                    $table->timestamps();

                                    $table->index(['chat_id', 'created_at']);
                                });
                            } else {
                                // Table exists, check if we need to add columns
                                Schema::table('chat_messages', function (Blueprint $table) {
                                    if (! Schema::hasColumn('chat_messages', 'sender_id')) {
                                        $table->morphs('sender');
                                    }
                                    if (! Schema::hasColumn('chat_messages', 'read_at')) {
                                        $table->timestamp('read_at')->nullable();
                                    }
                                });
                            }
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('chat_messages');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2026_01_07_202000_add_skillmatching_fks_to_chats_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Chats kunnen aan candidate/match/application gekoppeld worden; alleen in skillmatching-DB.
                         */
                        public function up(): void
                        {
                            Schema::table('chats', function (Blueprint $table) {
                                $table->foreignId('candidate_id')->after('company_id')->constrained('candidates')->onDelete('cascade');
                                $table->foreignId('match_id')->nullable()->after('candidate_id')->constrained('matches')->onDelete('cascade');
                                $table->foreignId('application_id')->nullable()->after('match_id')->constrained('applications')->onDelete('cascade');
                                $table->index(['user_id', 'candidate_id']);
                            });
                        }

                        public function down(): void
                        {
                            Schema::table('chats', function (Blueprint $table) {
                                $table->dropForeign(['candidate_id']);
                                $table->dropForeign(['match_id']);
                                $table->dropForeign(['application_id']);
                                $table->dropIndex(['user_id', 'candidate_id']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_07_214002_add_chat_id_to_chat_messages_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('chat_messages', function (Blueprint $table) {
                                if (! Schema::hasColumn('chat_messages', 'chat_id')) {
                                    $table->foreignId('chat_id')->nullable()->after('id')->constrained('chats')->onDelete('cascade');
                                }
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('chat_messages', function (Blueprint $table) {
                                if (Schema::hasColumn('chat_messages', 'chat_id')) {
                                    $table->dropForeign(['chat_id']);
                                    $table->dropColumn('chat_id');
                                }
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_08_214106_make_chat_room_id_nullable_in_chat_messages_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('chat_messages', function (Blueprint $table) {
                                // Drop foreign key constraint first
                                $table->dropForeign(['chat_room_id']);
                                // Make chat_room_id nullable
                                $table->foreignId('chat_room_id')->nullable()->change();
                                // Re-add foreign key constraint if chat_room_id is not null
                                // Note: PostgreSQL doesn't support conditional foreign keys easily, so we'll skip this
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('chat_messages', function (Blueprint $table) {
                                // Make chat_room_id not nullable again
                                $table->foreignId('chat_room_id')->nullable(false)->change();
                                // Re-add foreign key constraint
                                $table->foreign('chat_room_id')->references('id')->on('chat_rooms')->onDelete('cascade');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_08_214212_make_user_id_nullable_in_chat_messages_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('chat_messages', function (Blueprint $table) {
                                // Drop foreign key constraint first if it exists
                                $table->dropForeign(['user_id']);
                                // Make user_id nullable
                                $table->foreignId('user_id')->nullable()->change();
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('chat_messages', function (Blueprint $table) {
                                // Make user_id not nullable again
                                $table->foreignId('user_id')->nullable(false)->change();
                                // Re-add foreign key constraint
                                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_11_212712_create_general_settings_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('general_settings', function (Blueprint $table) {
                                $table->id();
                                $table->string('key')->unique();
                                $table->text('value')->nullable();
                                $table->timestamps();
                            });

                            // Insert default settings
                            DB::table('general_settings')->insert([
                                ['key' => 'logo', 'value' => null, 'created_at' => now(), 'updated_at' => now()],
                                ['key' => 'favicon', 'value' => null, 'created_at' => now(), 'updated_at' => now()],
                                ['key' => 'logo_size', 'value' => '26', 'created_at' => now(), 'updated_at' => now()],
                            ]);
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('general_settings');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_16_144552_add_ended_by_to_chats_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('chats', function (Blueprint $table) {
                                $table->string('ended_by_type')->nullable()->after('ended_at');
                                $table->unsignedBigInteger('ended_by_id')->nullable()->after('ended_by_type');
                                $table->index(['ended_by_type', 'ended_by_id']);
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('chats', function (Blueprint $table) {
                                $table->dropIndex(['ended_by_type', 'ended_by_id']);
                                $table->dropColumn(['ended_by_type', 'ended_by_id']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_16_150833_add_deleted_to_chats_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('chats', function (Blueprint $table) {
                                $table->timestamp('deleted_at')->nullable()->after('ended_by_id');
                                $table->string('deleted_by_type')->nullable()->after('deleted_at');
                                $table->unsignedBigInteger('deleted_by_id')->nullable()->after('deleted_by_type');
                                $table->index(['deleted_by_type', 'deleted_by_id']);
                                $table->index('deleted_at');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('chats', function (Blueprint $table) {
                                $table->dropIndex(['deleted_by_type', 'deleted_by_id']);
                                $table->dropIndex(['deleted_at']);
                                $table->dropColumn(['deleted_at', 'deleted_by_type', 'deleted_by_id']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_17_213533_add_category_to_notifications_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('notifications', function (Blueprint $table) {
                                $table->string('category', 50)->default('info')->after('type');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('notifications', function (Blueprint $table) {
                                $table->dropColumn('category');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_17_220202_add_file_path_to_notifications_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('notifications', function (Blueprint $table) {
                                $table->string('file_path', 500)->nullable()->after('action_url');
                                $table->string('file_name', 255)->nullable()->after('file_path');
                                $table->string('file_size', 50)->nullable()->after('file_name');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('notifications', function (Blueprint $table) {
                                $table->dropColumn(['file_path', 'file_name', 'file_size']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_17_231903_add_location_id_to_notifications_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('notifications', function (Blueprint $table) {
                                $table->unsignedBigInteger('location_id')->nullable()->after('scheduled_at');
                                $table->foreign('location_id')->references('id')->on('company_locations')->onDelete('set null');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('notifications', function (Blueprint $table) {
                                $table->dropForeign(['location_id']);
                                $table->dropColumn('location_id');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_17_234725_modify_notifications_location_id_foreign_key.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('notifications', function (Blueprint $table) {
                                // Drop the existing foreign key constraint
                                // This allows location_id = 0 to be used as a special value for main address
                                $table->dropForeign(['location_id']);
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            // Restore the original foreign key constraint
                            Schema::table('notifications', function (Blueprint $table) {
                                $table->foreign('location_id')->references('id')->on('company_locations')->onDelete('set null');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_23_090122_add_original_notification_id_to_notifications_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('notifications', function (Blueprint $table) {
                                // Add original_notification_id to link related notifications
                                // This links response notifications and confirmation notifications to their original notification
                                $table->unsignedBigInteger('original_notification_id')->nullable()->after('location_id');
                                $table->foreign('original_notification_id')->references('id')->on('notifications')->onDelete('cascade');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('notifications', function (Blueprint $table) {
                                $table->dropForeign(['original_notification_id']);
                                $table->dropColumn('original_notification_id');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2026_01_23_221433_add_interviewer_user_id_to_interviews_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('interviews', function (Blueprint $table) {
                                $table->foreignId('interviewer_user_id')->nullable()->after('interviewer_email')->constrained('users')->nullOnDelete();
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('interviews', function (Blueprint $table) {
                                $table->dropForeign(['interviewer_user_id']);
                                $table->dropColumn('interviewer_user_id');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2026_01_23_221932_populate_interviewer_user_id_from_email.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            // Get all interviews where interviewer_email is set but interviewer_user_id is null
                            $interviews = Interview::whereNotNull('interviewer_email')
                                ->whereNull('interviewer_user_id')
                                ->get();

                            $updated = 0;
                            $notFound = 0;

                            foreach ($interviews as $interview) {
                                // Find user by email
                                $user = User::where('email', $interview->interviewer_email)->first();

                                if ($user) {
                                    // Update interviewer_user_id
                                    $interview->interviewer_user_id = $user->id;

                                    // Also update user_id for backward compatibility if it's null
                                    if (is_null($interview->user_id)) {
                                        $interview->user_id = $user->id;
                                    }

                                    $interview->save();
                                    $updated++;
                                } else {
                                    $notFound++;
                                    \Log::warning('Interview interviewer user not found by email', [
                                        'interview_id' => $interview->id,
                                        'interviewer_email' => $interview->interviewer_email,
                                    ]);
                                }
                            }

                            \Log::info('Populated interviewer_user_id from email', [
                                'total_interviews' => $interviews->count(),
                                'updated' => $updated,
                                'not_found' => $notFound,
                            ]);
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            // Optionally clear interviewer_user_id if needed
                            // Interview::whereNotNull('interviewer_user_id')->update(['interviewer_user_id' => null]);
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_28_155518_add_email_template_id_to_notifications_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('notifications', function (Blueprint $table) {
                                $table->unsignedBigInteger('email_template_id')->nullable()->after('type');
                                $table->foreign('email_template_id')->references('id')->on('email_templates')->onDelete('set null');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('notifications', function (Blueprint $table) {
                                $table->dropForeign(['email_template_id']);
                                $table->dropColumn('email_template_id');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_28_221358_ensure_all_branches_have_slug.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            // Update alle branches zonder slug
                            $branches = DB::table('branches')->whereNull('slug')->orWhere('slug', '')->get();

                            foreach ($branches as $branch) {
                                $slug = Str::slug($branch->name);
                                $baseSlug = $slug;
                                $counter = 1;

                                // Zorg dat slug uniek is
                                while (DB::table('branches')->where('slug', $slug)->where('id', '!=', $branch->id)->exists()) {
                                    $slug = $baseSlug.'-'.$counter;
                                    $counter++;
                                }

                                DB::table('branches')
                                    ->where('id', $branch->id)
                                    ->update(['slug' => $slug]);
                            }
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            // Geen rollback nodig - slugs kunnen blijven bestaan
                        }
                    })->up();
                },
            ],
            [
                'set' => 'skillmatching',
                'basename' => '2026_01_28_230000_add_user_id_to_applications_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         * Frontend users (User) can have applications; user_id links to the logged-in applicant.
                         */
                        public function up(): void
                        {
                            Schema::table('applications', function (Blueprint $table) {
                                $table->foreignId('user_id')->nullable()->after('vacancy_id')->constrained('users')->nullOnDelete();
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('applications', function (Blueprint $table) {
                                $table->dropForeign(['user_id']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_29_120000_create_frontend_themes_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::create('frontend_themes', function (Blueprint $table) {
                                $table->id();
                                $table->string('slug')->unique();
                                $table->string('name');
                                $table->string('description')->nullable();
                                $table->boolean('is_active')->default(false);
                                $table->json('settings')->nullable();
                                $table->timestamps();
                            });
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('frontend_themes');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_29_120001_create_website_pages_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::create('website_pages', function (Blueprint $table) {
                                $table->id();
                                $table->string('slug')->unique();
                                $table->string('title');
                                $table->longText('content')->nullable();
                                $table->string('meta_description')->nullable();
                                $table->string('page_type'); // home, about, contact, custom, module
                                $table->string('module_name')->nullable();
                                $table->foreignId('frontend_theme_id')->nullable()->constrained('frontend_themes')->nullOnDelete();
                                $table->boolean('is_active')->default(true);
                                $table->unsignedInteger('sort_order')->default(0);
                                $table->timestamps();

                                $table->index(['page_type', 'is_active']);
                                $table->index(['module_name', 'is_active']);
                            });
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('website_pages');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_29_140000_create_website_media_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::create('website_media', function (Blueprint $table) {
                                $table->id();
                                $table->uuid('uuid')->unique();
                                $table->string('original_filename');
                                $table->string('mime_type', 100);
                                $table->string('encrypted_path'); // path relative to storage disk
                                $table->unsignedBigInteger('size')->default(0);
                                $table->timestamps();
                            });
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('website_media');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_30_100000_add_default_blocks_to_frontend_themes_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::table('frontend_themes', function (Blueprint $table) {
                                $table->json('default_blocks')->nullable()->after('settings');
                            });
                        }

                        public function down(): void
                        {
                            Schema::table('frontend_themes', function (Blueprint $table) {
                                $table->dropColumn('default_blocks');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_01_30_140000_add_home_sections_to_website_pages_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::table('website_pages', function (Blueprint $table) {
                                $table->json('home_sections')->nullable()->after('content');
                            });
                        }

                        public function down(): void
                        {
                            Schema::table('website_pages', function (Blueprint $table) {
                                $table->dropColumn('home_sections');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_02_02_220000_add_frontend_theme_id_to_modules_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::table('modules', function (Blueprint $table) {
                                $table->foreignId('frontend_theme_id')->nullable()->after('configuration')->constrained('frontend_themes')->nullOnDelete();
                            });
                        }

                        public function down(): void
                        {
                            Schema::table('modules', function (Blueprint $table) {
                                $table->dropForeign(['frontend_theme_id']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_02_03_100000_add_preview_path_to_frontend_themes_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::table('frontend_themes', function (Blueprint $table) {
                                $table->string('preview_path', 500)->nullable()->after('description');
                            });
                        }

                        public function down(): void
                        {
                            Schema::table('frontend_themes', function (Blueprint $table) {
                                $table->dropColumn('preview_path');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_02_09_230000_make_website_pages_slug_unique_per_theme.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Slug uniek per thema: verwijder globale unique op slug,
                         * voeg uniekheid toe op (frontend_theme_id, slug).
                         */
                        public function up(): void
                        {
                            $driver = Schema::getConnection()->getDriverName();

                            if ($driver === 'pgsql') {
                                Schema::table('website_pages', function (Blueprint $table) {
                                    $table->dropUnique(['slug']);
                                });
                                // Eén slug per thema; bij frontend_theme_id IS NULL maximaal één per slug (core-pagina's)
                                DB::statement('CREATE UNIQUE INDEX website_pages_slug_unique_null_theme ON website_pages (slug) WHERE frontend_theme_id IS NULL');
                                DB::statement('CREATE UNIQUE INDEX website_pages_theme_slug_unique ON website_pages (frontend_theme_id, slug) WHERE frontend_theme_id IS NOT NULL');
                            } else {
                                Schema::table('website_pages', function (Blueprint $table) {
                                    $table->dropUnique(['slug']);
                                    $table->unique(['frontend_theme_id', 'slug'], 'website_pages_theme_slug_unique');
                                });
                            }
                        }

                        public function down(): void
                        {
                            $driver = Schema::getConnection()->getDriverName();

                            if ($driver === 'pgsql') {
                                DB::statement('DROP INDEX IF EXISTS website_pages_slug_unique_null_theme');
                                DB::statement('DROP INDEX IF EXISTS website_pages_theme_slug_unique');
                                Schema::table('website_pages', function (Blueprint $table) {
                                    $table->unique('slug', 'website_pages_slug_unique');
                                });
                            } else {
                                Schema::table('website_pages', function (Blueprint $table) {
                                    $table->dropUnique('website_pages_theme_slug_unique');
                                    $table->unique('slug');
                                });
                            }
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_02_16_120000_rename_modern_theme_to_metronic.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         * Hernoem weergavenaam "Modern" naar "Metronic" voor het thema met slug 'modern'.
                         */
                        public function up(): void
                        {
                            DB::table('frontend_themes')
                                ->where('slug', 'modern')
                                ->update([
                                    'name' => 'Metronic',
                                    'description' => 'Strak Metronic-design met veel witruimte. Huidige website-layout (Home-pagina).',
                                ]);
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            DB::table('frontend_themes')
                                ->where('slug', 'modern')
                                ->update([
                                    'name' => 'Modern',
                                    'description' => 'Strak en modern design met veel witruimte. Huidige website-layout (Home-pagina).',
                                ]);
                        }
                    })->up();
                },
            ],
            [
                'set' => 'taxiroyaal',
                'basename' => '2026_02_16_140000_create_vehicles_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         * Voertuigen voor Nexa Taxi (per bedrijf).
                         */
                        public function up(): void
                        {
                            if (Schema::hasTable('vehicles')) {
                                return;
                            }
                            Schema::create('vehicles', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
                                $table->string('name'); // bijv. "Auto 1", "Busje"
                                $table->string('type', 20)->default('car'); // car, van, bus
                                $table->string('license_plate')->nullable();
                                $table->unsignedSmallInteger('seats')->default(4); // capaciteit
                                $table->boolean('active')->default(true);
                                $table->decimal('base_fare', 10, 2)->nullable();
                                $table->decimal('price_per_km', 10, 2)->default(0);
                                $table->decimal('price_per_min', 10, 2)->default(0);
                                $table->decimal('min_fare', 10, 2)->default(0);
                                $table->text('notes')->nullable();
                                $table->string('image_url', 500)->nullable();
                                $table->timestamps();
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('vehicles');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'taxiroyaal',
                'basename' => '2026_02_16_140001_create_ride_requests_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         * Rit/aanvragen voor Nexa Taxi (koppeling voertuig + chauffeur).
                         */
                        public function up(): void
                        {
                            if (Schema::hasTable('ride_requests')) {
                                return;
                            }
                            Schema::create('ride_requests', function (Blueprint $table) {
                                $table->id();
                                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                                $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
                                $table->foreignId('driver_id')->nullable()->constrained('users')->nullOnDelete();
                                $table->string('status', 20)->default('draft'); // draft, quoted, accepted, assigned, completed, cancelled
                                $table->string('pickup_address');
                                $table->string('dropoff_address');
                                $table->decimal('pickup_lat', 10, 7)->nullable();
                                $table->decimal('pickup_lng', 10, 7)->nullable();
                                $table->decimal('dropoff_lat', 10, 7)->nullable();
                                $table->decimal('dropoff_lng', 10, 7)->nullable();
                                $table->unsignedInteger('distance_meters')->nullable();
                                $table->unsignedInteger('duration_seconds')->nullable();
                                $table->unsignedSmallInteger('passengers')->default(1);
                                $table->dateTime('pickup_at');
                                $table->decimal('quoted_price', 10, 2)->nullable();
                                $table->string('customer_name');
                                $table->string('customer_email')->nullable();
                                $table->string('customer_phone')->nullable();
                                $table->text('customer_note')->nullable();
                                $table->dateTime('quote_expires_at')->nullable();
                                $table->timestamps();
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('ride_requests');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'taxiroyaal',
                'basename' => '2026_02_16_150000_add_company_id_to_ride_requests_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            if (! Schema::hasTable('ride_requests')) {
                                return;
                            }
                            if (Schema::hasColumn('ride_requests', 'company_id')) {
                                return;
                            }
                            Schema::table('ride_requests', function (Blueprint $table) {
                                $table->foreignId('company_id')->nullable()->after('id')->constrained('companies')->nullOnDelete();
                            });
                        }

                        public function down(): void
                        {
                            Schema::table('ride_requests', function (Blueprint $table) {
                                $table->dropForeign(['company_id']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'taxiroyaal',
                'basename' => '2026_02_16_160000_add_missing_columns_to_vehicles_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Add columns to vehicles table if they were missing (e.g. table existed before Nexa Taxi migration).
                         */
                        public function up(): void
                        {
                            if (! Schema::hasTable('vehicles')) {
                                return;
                            }

                            Schema::table('vehicles', function (Blueprint $table) {
                                if (! Schema::hasColumn('vehicles', 'name')) {
                                    $table->string('name')->nullable()->after('company_id');
                                }
                                if (! Schema::hasColumn('vehicles', 'type')) {
                                    $table->string('type', 20)->default('car')->after('name');
                                }
                                if (! Schema::hasColumn('vehicles', 'license_plate')) {
                                    $table->string('license_plate')->nullable()->after('type');
                                }
                                if (! Schema::hasColumn('vehicles', 'seats')) {
                                    $table->unsignedSmallInteger('seats')->default(4)->after('license_plate');
                                }
                                if (! Schema::hasColumn('vehicles', 'active')) {
                                    $table->boolean('active')->default(true)->after('seats');
                                }
                                if (! Schema::hasColumn('vehicles', 'base_fare')) {
                                    $table->decimal('base_fare', 10, 2)->nullable()->after('active');
                                }
                                if (! Schema::hasColumn('vehicles', 'price_per_km')) {
                                    $table->decimal('price_per_km', 10, 2)->default(0)->after('base_fare');
                                }
                                if (! Schema::hasColumn('vehicles', 'price_per_min')) {
                                    $table->decimal('price_per_min', 10, 2)->default(0)->after('price_per_km');
                                }
                                if (! Schema::hasColumn('vehicles', 'min_fare')) {
                                    $table->decimal('min_fare', 10, 2)->default(0)->after('price_per_min');
                                }
                                if (! Schema::hasColumn('vehicles', 'notes')) {
                                    $table->text('notes')->nullable()->after('min_fare');
                                }
                                if (! Schema::hasColumn('vehicles', 'image_url')) {
                                    $table->string('image_url', 500)->nullable()->after('notes');
                                }
                            });
                        }

                        public function down(): void
                        {
                            // Optional: drop added columns. Skipping to avoid data loss if table was mixed-use.
                        }
                    })->up();
                },
            ],
            [
                'set' => 'taxiroyaal',
                'basename' => '2026_02_16_170000_add_image_url_to_vehicles_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            if (! Schema::hasTable('vehicles') || Schema::hasColumn('vehicles', 'image_url')) {
                                return;
                            }
                            Schema::table('vehicles', function (Blueprint $table) {
                                $table->string('image_url', 500)->nullable()->after('notes');
                            });
                        }

                        public function down(): void
                        {
                            if (Schema::hasColumn('vehicles', 'image_url')) {
                                Schema::table('vehicles', function (Blueprint $table) {
                                    $table->dropColumn('image_url');
                                });
                            }
                        }
                    })->up();
                },
            ],
            [
                'set' => 'taxiroyaal',
                'basename' => '2026_02_16_180000_create_default_rates_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Algemene standaardtarieven (één rij, niet per voertuig).
                         */
                        public function up(): void
                        {
                            if (Schema::hasTable('default_rates')) {
                                return;
                            }
                            Schema::create('default_rates', function (Blueprint $table) {
                                $table->id();
                                $table->decimal('base_fare', 10, 2)->nullable();
                                $table->decimal('min_fare', 10, 2)->default(0);
                                $table->decimal('price_per_km', 10, 2)->default(0);
                                $table->decimal('price_per_min', 10, 2)->default(0);
                                $table->timestamps();
                            });
                            // Eerste rij aanmaken op dezelfde connection als de migratie
                            Schema::getConnection()->table('default_rates')->insert([
                                'base_fare' => null,
                                'min_fare' => 0,
                                'price_per_km' => 0,
                                'price_per_min' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }

                        public function down(): void
                        {
                            Schema::dropIfExists('default_rates');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'taxiroyaal',
                'basename' => '2026_02_16_190000_add_cleaning_costs_to_rates_and_vehicles.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Reinigingskosten toevoegen aan standaardtarieven en voertuigen.
                         */
                        public function up(): void
                        {
                            if (Schema::hasTable('default_rates') && ! Schema::hasColumn('default_rates', 'cleaning_costs')) {
                                Schema::table('default_rates', function (Blueprint $table) {
                                    $table->decimal('cleaning_costs', 10, 2)->nullable()->after('price_per_min');
                                });
                            }
                            if (Schema::hasTable('vehicles') && ! Schema::hasColumn('vehicles', 'cleaning_costs')) {
                                Schema::table('vehicles', function (Blueprint $table) {
                                    $table->decimal('cleaning_costs', 10, 2)->nullable()->after('price_per_min');
                                });
                            }
                        }

                        public function down(): void
                        {
                            if (Schema::hasTable('default_rates') && Schema::hasColumn('default_rates', 'cleaning_costs')) {
                                Schema::table('default_rates', fn (Blueprint $table) => $table->dropColumn('cleaning_costs'));
                            }
                            if (Schema::hasTable('vehicles') && Schema::hasColumn('vehicles', 'cleaning_costs')) {
                                Schema::table('vehicles', fn (Blueprint $table) => $table->dropColumn('cleaning_costs'));
                            }
                        }
                    })->up();
                },
            ],
            [
                'set' => 'taxiroyaal',
                'basename' => '2026_02_16_200000_add_person_range_to_default_rates.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Personenbereik (1-4 en 5-8) voor standaardtarieven.
                         */
                        public function up(): void
                        {
                            if (! Schema::hasTable('default_rates')) {
                                return;
                            }

                            if (! Schema::hasColumn('default_rates', 'person_range')) {
                                Schema::table('default_rates', function (Blueprint $table) {
                                    $table->string('person_range', 10)->default('1-4')->after('id');
                                });
                                Schema::getConnection()->table('default_rates')->update(['person_range' => '1-4']);
                            }

                            $exists = Schema::getConnection()->table('default_rates')->where('person_range', '5-8')->exists();
                            if (! $exists) {
                                Schema::getConnection()->table('default_rates')->insert([
                                    'person_range' => '5-8',
                                    'base_fare' => null,
                                    'min_fare' => 0,
                                    'price_per_km' => 0,
                                    'price_per_min' => 0,
                                    'cleaning_costs' => null,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }

                        public function down(): void
                        {
                            if (! Schema::hasTable('default_rates') || ! Schema::hasColumn('default_rates', 'person_range')) {
                                return;
                            }
                            Schema::getConnection()->table('default_rates')->where('person_range', '5-8')->delete();
                            Schema::table('default_rates', fn (Blueprint $table) => $table->dropColumn('person_range'));
                        }
                    })->up();
                },
            ],
            [
                'set' => 'taxiroyaal',
                'basename' => '2026_02_16_210000_add_booking_payload_to_ride_requests_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            if (! Schema::hasTable('ride_requests')) {
                                return;
                            }
                            Schema::table('ride_requests', function (Blueprint $table) {
                                if (! Schema::hasColumn('ride_requests', 'booking_payload')) {
                                    $table->json('booking_payload')->nullable()->after('quote_expires_at');
                                }
                                if (! Schema::hasColumn('ride_requests', 'selected_offer_payload')) {
                                    $table->json('selected_offer_payload')->nullable()->after('booking_payload');
                                }
                            });
                        }

                        public function down(): void
                        {
                            if (! Schema::hasTable('ride_requests')) {
                                return;
                            }
                            Schema::table('ride_requests', function (Blueprint $table) {
                                if (Schema::hasColumn('ride_requests', 'selected_offer_payload')) {
                                    $table->dropColumn('selected_offer_payload');
                                }
                                if (Schema::hasColumn('ride_requests', 'booking_payload')) {
                                    $table->dropColumn('booking_payload');
                                }
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'taxiroyaal',
                'basename' => '2026_02_24_120000_add_person_range_to_vehicles_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            if (! Schema::hasTable('vehicles') || Schema::hasColumn('vehicles', 'person_range')) {
                                return;
                            }

                            Schema::table('vehicles', function (Blueprint $table) {
                                $table->string('person_range', 10)
                                    ->default('1-4')
                                    ->after('seats');
                            });
                        }

                        public function down(): void
                        {
                            if (! Schema::hasTable('vehicles') || ! Schema::hasColumn('vehicles', 'person_range')) {
                                return;
                            }

                            Schema::table('vehicles', function (Blueprint $table) {
                                $table->dropColumn('person_range');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'taxiroyaal',
                'basename' => '2026_02_24_223000_add_show_photo_to_vehicles_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            if (! Schema::hasTable('vehicles') || Schema::hasColumn('vehicles', 'show_photo')) {
                                return;
                            }

                            Schema::table('vehicles', function (Blueprint $table) {
                                $table->boolean('show_photo')->default(false)->after('image_url');
                            });
                        }

                        public function down(): void
                        {
                            if (! Schema::hasTable('vehicles') || ! Schema::hasColumn('vehicles', 'show_photo')) {
                                return;
                            }

                            Schema::table('vehicles', function (Blueprint $table) {
                                $table->dropColumn('show_photo');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_02_25_120000_website_pages_slug_unique_per_theme_and_module.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Slug uniek per thema én per module: (frontend_theme_id, module_name, slug).
                         * Vervangt de bestaande index (frontend_theme_id, slug) zodat dezelfde slug
                         * in verschillende modules binnen hetzelfde thema toegestaan is.
                         */
                        public function up(): void
                        {
                            $driver = Schema::getConnection()->getDriverName();

                            if ($driver === 'pgsql') {
                                DB::statement('DROP INDEX IF EXISTS website_pages_slug_unique_null_theme');
                                DB::statement('DROP INDEX IF EXISTS website_pages_theme_slug_unique');
                                // Core-pagina's: slug uniek waar theme en module beide null
                                DB::statement('CREATE UNIQUE INDEX website_pages_core_slug_unique ON website_pages (slug) WHERE frontend_theme_id IS NULL AND module_name IS NULL');
                                // Thema + module: (theme_id, module_name, slug); COALESCE zodat NULL module_name één waarde is
                                DB::statement('CREATE UNIQUE INDEX website_pages_theme_module_slug_unique ON website_pages (frontend_theme_id, COALESCE(module_name, \'\'), slug) WHERE frontend_theme_id IS NOT NULL');
                            } else {
                                Schema::table('website_pages', function (Blueprint $table) {
                                    $table->dropUnique('website_pages_theme_slug_unique');
                                });
                                // MySQL: gegenereerde kolommen zodat NULL (theme/module) één waarde wordt in de unique index
                                DB::statement('ALTER TABLE website_pages ADD COLUMN frontend_theme_id_for_unique BIGINT UNSIGNED GENERATED ALWAYS AS (COALESCE(frontend_theme_id, 0)) STORED');
                                DB::statement("ALTER TABLE website_pages ADD COLUMN module_name_for_unique VARCHAR(255) GENERATED ALWAYS AS (COALESCE(module_name, '')) STORED");
                                Schema::table('website_pages', function (Blueprint $table) {
                                    $table->unique(['frontend_theme_id_for_unique', 'module_name_for_unique', 'slug'], 'website_pages_theme_module_slug_unique');
                                });
                            }
                        }

                        public function down(): void
                        {
                            $driver = Schema::getConnection()->getDriverName();

                            if ($driver === 'pgsql') {
                                DB::statement('DROP INDEX IF EXISTS website_pages_core_slug_unique');
                                DB::statement('DROP INDEX IF EXISTS website_pages_theme_module_slug_unique');
                                DB::statement('CREATE UNIQUE INDEX website_pages_slug_unique_null_theme ON website_pages (slug) WHERE frontend_theme_id IS NULL');
                                DB::statement('CREATE UNIQUE INDEX website_pages_theme_slug_unique ON website_pages (frontend_theme_id, slug) WHERE frontend_theme_id IS NOT NULL');
                            } else {
                                Schema::table('website_pages', function (Blueprint $table) {
                                    $table->dropUnique('website_pages_theme_module_slug_unique');
                                });
                                Schema::table('website_pages', function (Blueprint $table) {
                                    $table->dropColumn(['frontend_theme_id_for_unique', 'module_name_for_unique']);
                                });
                                Schema::table('website_pages', function (Blueprint $table) {
                                    $table->unique(['frontend_theme_id', 'slug'], 'website_pages_theme_slug_unique');
                                });
                            }
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_03_08_120000_add_recipient_to_email_templates_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::table('email_templates', function (Blueprint $table) {
                                $table->string('recipient_type', 20)->nullable()->after('company_id')->comment('user or email');
                                $table->unsignedBigInteger('recipient_user_id')->nullable()->after('recipient_type');
                                $table->string('recipient_email')->nullable()->after('recipient_user_id');
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::table('email_templates', function (Blueprint $table) {
                                $table->dropColumn(['recipient_type', 'recipient_user_id', 'recipient_email']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_03_08_140000_create_info_request_form_fields_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Run the migrations.
                         */
                        public function up(): void
                        {
                            Schema::create('info_request_form_fields', function (Blueprint $table) {
                                $table->id();
                                $table->string('name', 100)->comment('Slug/name voor request (voornaam, achternaam, email, etc.)');
                                $table->string('label')->comment('Label op het formulier');
                                $table->boolean('is_required')->default(false);
                                $table->string('validation_rule', 100)->nullable()->comment('email, tel, number, of regex:...');
                                $table->unsignedInteger('sort_order')->default(0);
                                $table->timestamps();
                            });
                        }

                        /**
                         * Reverse the migrations.
                         */
                        public function down(): void
                        {
                            Schema::dropIfExists('info_request_form_fields');
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_03_12_140000_add_border_to_email_template_card.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Add border to the card table in email template HTML (informatieaanvraag and others)
                         * so the card is clearly visible in dark-themed mail clients.
                         */
                        public function up(): void
                        {
                            $old = 'width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
                            $new = 'width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';

                            DB::table('email_templates')
                                ->whereNotNull('html_content')
                                ->where('html_content', 'like', '%'.$old.'%')
                                ->where('html_content', 'not like', '%border: 1px solid #e5e7eb%')
                                ->update([
                                    'html_content' => DB::raw("REPLACE(html_content, '".str_replace("'", "''", $old)."', '".str_replace("'", "''", $new)."')"),
                                ]);
                        }

                        public function down(): void
                        {
                            $new = 'width: 600px; margin: 0 auto; background-color: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';
                            $old = 'width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);';

                            DB::table('email_templates')
                                ->whereNotNull('html_content')
                                ->where('html_content', 'like', '%'.$new.'%')
                                ->update([
                                    'html_content' => DB::raw("REPLACE(html_content, '".str_replace("'", "''", $new)."', '".str_replace("'", "''", $old)."')"),
                                ]);
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_03_12_160000_rename_form_field_slugs_to_match_template_vars.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Hernoem slug 'email' -> 'email_aanvraag' en 'telefoon' -> 'telefoonnummer'
                         * zodat template-variabelen ({{ EMAIL_AANVRAAG }}, {{ TELEFOONNUMMER }}) overeenkomen met de slug.
                         */
                        public function up(): void
                        {
                            DB::table('info_request_form_fields')
                                ->where('name', 'email')
                                ->update(['name' => 'email_aanvraag']);
                            DB::table('info_request_form_fields')
                                ->where('name', 'telefoon')
                                ->update(['name' => 'telefoonnummer']);
                        }

                        public function down(): void
                        {
                            DB::table('info_request_form_fields')
                                ->where('name', 'email_aanvraag')
                                ->update(['name' => 'email']);
                            DB::table('info_request_form_fields')
                                ->where('name', 'telefoonnummer')
                                ->update(['name' => 'telefoon']);
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_03_13_100000_add_form_field_order_to_email_templates.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        /**
                         * Volgorde en selectie van formuliervelden per template (type informatieaanvraag).
                         * JSON array van info_request_form_field ids, bijv. [1,3,2,5,4]. Null = alle velden in standaard volgorde.
                         */
                        public function up(): void
                        {
                            Schema::table('email_templates', function (Blueprint $table) {
                                $table->json('form_field_order')->nullable()->after('recipient_email');
                            });
                        }

                        public function down(): void
                        {
                            Schema::table('email_templates', function (Blueprint $table) {
                                $table->dropColumn('form_field_order');
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_03_14_120000_add_active_module_id_to_frontend_themes_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::table('frontend_themes', function (Blueprint $table) {
                                $table->foreignId('active_module_id')->nullable()->after('is_active')->constrained('modules')->nullOnDelete();
                            });
                        }

                        public function down(): void
                        {
                            Schema::table('frontend_themes', function (Blueprint $table) {
                                $table->dropForeign(['active_module_id']);
                            });
                        }
                    })->up();
                },
            ],
            [
                'set' => 'shared',
                'basename' => '2026_03_17_120000_add_show_in_menu_to_website_pages_table.php',
                'run' => static function (): void {
                    (new class extends Migration
                    {
                        public function up(): void
                        {
                            Schema::table('website_pages', function (Blueprint $table) {
                                $table->boolean('show_in_menu')->default(true)->after('is_active');
                            });
                        }

                        public function down(): void
                        {
                            Schema::table('website_pages', function (Blueprint $table) {
                                $table->dropColumn('show_in_menu');
                            });
                        }
                    })->up();
                },
            ],
        ];
    }

    public static function runFull(): void
    {
        $skipModuleSpecificSetsOnMain = self::shouldSkipModuleSpecificSetsOnMainDatabase();
        foreach (self::steps() as $step) {
            if ($skipModuleSpecificSetsOnMain && in_array($step['set'], ['taxiroyaal', 'skillmatching'], true)) {
                continue;
            }
            ($step['run'])();
        }
    }

    /**
     * Bij strategy=schema of strategy=database hoort de hoofd-DB (public) alleen core+shared:
     * geen taxiroyaal- of skillmatching-tabellen (die staan in eigen schema of eigen database).
     * Alleen bij strategy=single draait de volledige baseline op de hoofd-DB.
     */
    protected static function shouldSkipModuleSpecificSetsOnMainDatabase(): bool
    {
        if (config('database.default') === 'sqlite') {
            return true;
        }

        $strategy = config('module_database.strategy', 'schema');

        return $strategy !== 'single';
    }

    /**
     * @param  list<string>  $allowedSets  bv. ['core','shared','taxiroyaal']
     * @param  bool  $tolerateErrors  Bij schema-only mode: vang fouten op (bv. kolom bestaat al op public-tabel)
     * @return list<array{basename: string, error: string}>  Lijst van overgeslagen stappen (leeg als alles slaagde)
     */
    public static function runForSetsOnConnection(array $allowedSets, string $connectionName, bool $tolerateErrors = false): array
    {
        $allowedSets = array_values(array_unique(array_map('strtolower', $allowedSets)));
        $previous = Config::get('database.default');
        Config::set('database.default', $connectionName);
        $skipped = [];
        try {
            foreach (self::steps() as $step) {
                if (! in_array($step['set'], $allowedSets, true)) {
                    continue;
                }
                if ($tolerateErrors) {
                    try {
                        ($step['run'])();
                    } catch (\Throwable $e) {
                        $skipped[] = ['basename' => $step['basename'], 'error' => $e->getMessage()];
                        Log::warning("Pre2026Baseline step skipped ({$connectionName}): {$step['basename']} — {$e->getMessage()}");
                    }
                } else {
                    ($step['run'])();
                }
            }
        } finally {
            Config::set('database.default', $previous);
        }

        return $skipped;
    }
}
