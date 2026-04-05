<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    use HasRoles {
        HasRoles::hasRole as spatieHasRole;
        HasRoles::hasAllRoles as spatieHasAllRoles;
    }

    /**
     * The guard name for the permissions.
     */
    protected $guard_name = 'web';

    /**
     * Cache per request instantie (Gate::before roept isSuperAdmin vaak aan).
     */
    protected ?bool $isSuperAdminCache = null;

    /**
     * PK van de rol `super-admin` (zelfde als Spatie {@see Role::findByName()}, gecached).
     */
    protected static ?int $superAdminRoleIdCache = null;

    /**
     * Super-admin volgens Spatie-data (zelfde tabellen als {@see HasRoles}), maar zonder de
     * team-filter op {@see HasRoles::roles()} (config `permission.teams` + `company_id`).
     *
     * Zie: https://spatie.be/docs/laravel-permission/v6/basic-usage/teams-permissions
     * Globale super-admin zit vaak op `model_has_roles.company_id` = null; bij een gezet
     * {@see setPermissionsTeamId()} ziet Spatie’s relatie die koppeling dan niet meer. Deze
     * query blijft daarom bewust team-agnostisch — alleen voor de vaste rolnaam `super-admin`.
     */
    public function isSuperAdmin(): bool
    {
        if ($this->isSuperAdminCache !== null) {
            return $this->isSuperAdminCache;
        }

        $pivotTable = config('permission.table_names.model_has_roles');
        $rolesTable = config('permission.table_names.roles');
        $rolePivotKey = config('permission.column_names.role_pivot_key') ?: 'role_id';
        $morphKey = config('permission.column_names.model_morph_key') ?: 'model_id';

        $morphTypes = array_unique(array_filter([
            $this->getMorphClass(),
            static::class,
            'App\\Models\\User',
        ]));

        $this->isSuperAdminCache = DB::table($pivotTable)
            ->join($rolesTable, "{$rolesTable}.id", '=', "{$pivotTable}.{$rolePivotKey}")
            ->where("{$pivotTable}.{$morphKey}", $this->getKey())
            ->whereIn("{$pivotTable}.model_type", $morphTypes)
            ->where("{$rolesTable}.name", 'super-admin')
            ->whereIn("{$rolesTable}.guard_name", ['web', 'api'])
            ->exists();

        return $this->isSuperAdminCache;
    }

    /**
     * Spatie teams: als de super-admin-rol niet via de gefilterde relatie matcht, alsnog true
     * wanneer {@see isSuperAdmin()} dat aangeeft — gelijk aan {@see hasRole()} met rolnaam `super-admin`.
     */
    public function hasRole($roles, ?string $guard = null): bool
    {
        if ($this->isSuperAdmin() && $this->roleArgumentIncludesSuperAdmin($roles)) {
            return true;
        }

        if ($this->isSuperAdmin() && (is_int($roles) || PermissionRegistrar::isUid($roles))) {
            $superAdminId = $this->getSuperAdminRoleId();
            if ($superAdminId !== null && (int) $roles === $superAdminId) {
                return true;
            }
        }

        return $this->spatieHasRole($roles, $guard);
    }

    /**
     * Hetzelfde als Spatie, maar: als `super-admin` in de vereiste set zit en {@see isSuperAdmin()}
     * true is, tellen we die rol mee zonder alleen op de gefilterde {@see roles()}-relatie te vertrouwen.
     *
     * @param  string|array|\Spatie\Permission\Contracts\Role|\Illuminate\Support\Collection|\BackedEnum  $roles
     */
    public function hasAllRoles($roles, ?string $guard = null): bool
    {
        if (! $this->isSuperAdmin()) {
            return $this->spatieHasAllRoles($roles, $guard);
        }

        if ($roles instanceof \BackedEnum) {
            $roles = $roles->value;
        }

        if (is_string($roles) && strpos($roles, '|') !== false) {
            $roles = $this->convertPipeToArray($roles);
        }

        if (is_string($roles)) {
            return $this->hasRole($roles, $guard);
        }

        if ($roles instanceof SpatieRole) {
            return $this->hasRole($roles, $guard);
        }

        $roles = collect()->make($roles)->map(function ($role) {
            if ($role instanceof \BackedEnum) {
                return $role->value;
            }

            return $role instanceof SpatieRole ? $role->name : $role;
        });

        if ($roles->contains('super-admin')) {
            $roles = $roles->filter(fn ($name) => $name !== 'super-admin')->values();
            if ($roles->isEmpty()) {
                return true;
            }
        }

        return $this->spatieHasAllRoles($roles->all(), $guard);
    }

    /**
     * @return ?int Primary key van rol `super-admin` in `roles`, of null als die rol ontbreekt.
     */
    protected function getSuperAdminRoleId(): ?int
    {
        if (self::$superAdminRoleIdCache !== null) {
            return self::$superAdminRoleIdCache;
        }

        $class = config('permission.models.role');
        $role = $class::findByName('super-admin', $this->getDefaultGuardName());
        self::$superAdminRoleIdCache = $role ? (int) $role->getKey() : null;

        return self::$superAdminRoleIdCache;
    }

    /**
     * Mag bedrijven/tenants aanmaken (wizard of formulier).
     */
    public function canCreateCompanies(): bool
    {
        return $this->can('create-companies');
    }

    /**
     * @param  string|int|array|\Illuminate\Support\Collection|\Spatie\Permission\Models\Role|\BackedEnum  $roles
     */
    private function roleArgumentIncludesSuperAdmin(mixed $roles): bool
    {
        if (is_string($roles)) {
            if ($roles === 'super-admin') {
                return true;
            }
            if (strpos($roles, '|') !== false) {
                foreach (explode('|', $roles) as $segment) {
                    if (trim($segment) === 'super-admin') {
                        return true;
                    }
                }
            }

            return false;
        }

        if (is_array($roles)) {
            foreach ($roles as $role) {
                if ($role === 'super-admin') {
                    return true;
                }
                if ($role instanceof SpatieRole && $role->name === 'super-admin') {
                    return true;
                }
                if ($role instanceof \BackedEnum && $role->value === 'super-admin') {
                    return true;
                }
            }

            return false;
        }

        if ($roles instanceof Collection) {
            return $roles->contains('name', 'super-admin')
                || $roles->contains(fn ($r) => $r === 'super-admin')
                || $roles->contains(fn ($r) => $r instanceof SpatieRole && $r->name === 'super-admin');
        }

        if ($roles instanceof SpatieRole) {
            return $roles->name === 'super-admin';
        }

        if ($roles instanceof \BackedEnum) {
            return $roles->value === 'super-admin';
        }

        return false;
    }

    /**
     * Get the favorites for the user.
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Get the CV files for the user.
     */
    public function cvFiles()
    {
        return $this->hasMany(CVFile::class);
    }

    /**
     * Get the favorited vacancies for the user.
     */
    public function favoriteVacancies()
    {
        return $this->belongsToMany(Vacancy::class, 'favorites', 'user_id', 'vacancy_id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'date_of_birth',
        'email_verified_at',
        'phone_verified_at',
        'phone',
        'company_id',
        'is_active',
        'cv_path',
        'cv_original_name',
        'location',
        'bio',
        'photo',
        'photo_blob',
        'photo_mime_type',
        'preferred_location',
        'max_distance',
        'contract_type',
        'work_hours',
        'min_salary',
        'email_notifications',
        'sms_notifications',
        'push_notifications',
        'profile_visible',
        'cv_downloadable',
        'function',
        'job_title_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Generate a secure token for photo access
     */
    public function getPhotoToken(): string
    {
        // Include updated_at timestamp to ensure token changes when photo is updated
        $hash = hash('sha256', $this->id.$this->updated_at.config('app.key'));
        $token = base64_encode($this->id.'|'.$hash);

        return $token;
    }

    /**
     * Generate a secure token for company photo access
     */
    public function getCompanyPhotoToken($companyId): string
    {
        $hash = hash('sha256', $this->id.$companyId.config('app.key'));
        $token = base64_encode($this->id.'|'.$companyId.'|'.$hash);

        return $token;
    }

    /**
     * Get the company that owns the user.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Standaardavatar (geen eigen foto) iets transparant tonen zolang de gekoppelde tenant recent is aangemaakt.
     */
    public function defaultAvatarShouldAppearTransparent(): bool
    {
        if ($this->photo_blob) {
            return false;
        }

        $company = $this->company;
        if (! $company || ! $company->created_at) {
            return false;
        }

        $hours = max(1, (int) config('nexa.default_avatar_fade_new_company_hours', 72));

        return $company->created_at->isAfter(now()->subHours($hours));
    }

    /**
     * Get the job title for the user
     */
    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class);
    }

    /**
     * Get the skills for the user.
     */
    public function skills()
    {
        return $this->hasMany(Skill::class);
    }

    /**
     * Get the experiences for the user.
     */
    public function experiences()
    {
        return $this->hasMany(Experience::class);
    }

    /**
     * Get the custom notifications for the user.
     * This overrides Laravel's default notifications() method from Notifiable trait.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }
}
