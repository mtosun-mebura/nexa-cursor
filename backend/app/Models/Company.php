<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'department', 'industry', 'kvk_number',
        'street', 'house_number', 'house_number_extension', 'postal_code', 'city', 'country',
        'latitude', 'longitude',
        'website', 'email', 'phone',
        'contact_first_name', 'contact_middle_name', 'contact_last_name', 'contact_email',
        'is_active', 'is_intermediary', 'is_main', 'logo_path', 'logo_blob', 'logo_mime_type',
        'logo_dark_blob', 'logo_dark_mime_type', 'building_image',
        'frontend_theme_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_intermediary' => 'boolean',
        'is_main' => 'boolean',
        'building_image' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
        });

        static::updating(function ($company) {
            if ($company->isDirty('name') && empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
        });

        $forgetTenantSwitcherCache = static function () {
            Cache::forget('admin.tenant_switcher.companies');
        };
        static::saved($forgetTenantSwitcherCache);
        static::deleted($forgetTenantSwitcherCache);
    }

    /**
     * Get the users for the company.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Generate a secure token to view a candidate's photo
     */
    public function getCandidatePhotoToken($userId): string
    {
        $user = User::find($userId);
        if (! $user) {
            return '';
        }

        return $user->getCompanyPhotoToken($this->id);
    }

    /**
     * Get the vacancies for the company.
     */
    public function vacancies()
    {
        return $this->hasMany(Vacancy::class);
    }

    /**
     * Get the email templates for the company.
     */
    public function emailTemplates()
    {
        return $this->hasMany(EmailTemplate::class);
    }

    /**
     * Get the locations for the company.
     */
    public function locations()
    {
        return $this->hasMany(CompanyLocation::class);
    }

    /**
     * Get the main location (hoofdkantoor) for the company.
     */
    public function mainLocation()
    {
        return $this->hasOne(CompanyLocation::class)->where('is_main', true);
    }

    /**
     * Aantal vestigingen voor samenvattingen: aparte locatieregels, of 1 als alleen het bedrijfsadres
     * (stap Bedrijf & logo) is ingevuld en er nog geen vestigingen in de tabel staan.
     */
    public function vestigingenDisplayCount(): int
    {
        $n = $this->locations()->count();
        if ($n > 0) {
            return $n;
        }

        $street = trim((string) ($this->street ?? ''));
        $city = trim((string) ($this->city ?? ''));
        $postal = trim((string) ($this->postal_code ?? ''));

        if ($street !== '' && $city !== '' && $postal !== '') {
            return 1;
        }

        return 0;
    }

    /**
     * Hoofdkantoor staat alleen op het bedrijf (stap Bedrijf & logo), niet als CompanyLocation met is_main.
     * Dan tonen we op het company-profiel een aparte kaart met dat adres en het logo.
     */
    public function shouldShowHoofdkantoorCardFromCompany(): bool
    {
        if ($this->mainLocation) {
            return false;
        }

        $street = trim((string) ($this->street ?? ''));
        $city = trim((string) ($this->city ?? ''));
        $postal = trim((string) ($this->postal_code ?? ''));

        return $street !== '' && $city !== '' && $postal !== '';
    }

    /**
     * Custom hostnames that map this company to a tenant context (see ResolveTenantFromHost).
     */
    public function domains()
    {
        return $this->hasMany(CompanyDomain::class);
    }

    /**
     * Installed/enabled modules for this tenant (Taxi, Garage, …).
     */
    public function modules()
    {
        return $this->belongsToMany(Module::class, 'company_module')
            ->withPivot('settings')
            ->withTimestamps();
    }

    /**
     * Frontend-thema voor deze tenant (website-styling en standaard bij website-pagina's).
     */
    public function frontendTheme()
    {
        return $this->belongsTo(FrontendTheme::class, 'frontend_theme_id');
    }

    /**
     * Whether this tenant has the given module attached (`modules.name`, e.g. skillmatching).
     */
    public function hasModuleNamed(string $name): bool
    {
        return $this->modules()->where('name', $name)->exists();
    }

    /**
     * Nexa Skillmatching (vacatures, matches, interviews, pipeline templates).
     */
    public function hasSkillmatchingModule(): bool
    {
        return $this->hasModuleNamed('skillmatching');
    }

    /**
     * Vaste illustratie voor “gebouw” (wizard keuze 1–3), voor o.a. hoofdkantoor op het profiel.
     */
    public function buildingImageAssetUrl(): ?string
    {
        $n = (int) ($this->building_image ?? 0);

        if (! in_array($n, [1, 2, 3], true)) {
            return null;
        }

        return asset('assets/media/company-buildings/'.$n.'.png');
    }
}
