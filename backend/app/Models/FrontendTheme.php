<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** @property-read \App\Models\Module|null $activeModule */
class FrontendTheme extends Model
{
    /**
     * Thema's die de website-bouwer (home-secties) ondersteunen.
     *
     * @var list<string>
     */
    public const HOME_SECTION_SLUGS = [
        'modern',
        'atom-v2',
        'nextly-template',
        'next-landing-vpn',
        'landwind',
        'play-tailwind',
        'vue-material-kit',
    ];

    /**
     * Thema's waarvan de bronbestanden in backend/themas/ staan.
     *
     * @var list<string>
     */
    public const PACKAGED_SOURCE_SLUGS = [
        'atom-v2',
        'nextly-template',
        'next-landing-vpn',
        'landwind',
        'play-tailwind',
        'vue-material-kit',
    ];

    public static function usesHomeSections(?string $slug): bool
    {
        return in_array(strtolower(trim((string) $slug)), self::HOME_SECTION_SLUGS, true);
    }

    protected $fillable = [
        'slug',
        'name',
        'description',
        'preview_path',
        'is_active',
        'active_module_id',
        'settings',
        'default_blocks',
    ];

    public function activeModule()
    {
        return $this->belongsTo(Module::class, 'active_module_id');
    }

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'default_blocks' => 'array',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Eerste gepubliceerde (beschikbare) thema — fallback voor centraal domein zonder tenant.
     */
    public static function getActive(): ?self
    {
        return static::active()->orderBy('id')->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function getAllActive(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->orderBy('slug')->get();
    }

    /**
     * Thema-instellingen, met optionele tenant-kleuren (AI-generator / merkkleuren).
     *
     * @return array<string, mixed>
     */
    public function getSettings(?Company $company = null): array
    {
        return self::mergeTenantColors(is_array($this->settings) ? $this->settings : [], $company);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    public static function mergeTenantColors(array $settings, ?Company $company): array
    {
        $overrides = is_array($company?->website_theme_settings) ? $company->website_theme_settings : [];
        foreach (['primary_color', 'secondary_color'] as $key) {
            $hex = self::normalizeHexColor((string) ($overrides[$key] ?? ''));
            if ($hex !== '') {
                $settings[$key] = $hex;
            }
        }

        return $settings;
    }

    public static function normalizeHexColor(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^#([A-Fa-f0-9]{6})$/', $value)) {
            return strtolower($value);
        }
        if (preg_match('/^#([A-Fa-f0-9]{3})$/', $value, $m)) {
            $h = $m[1];

            return strtolower('#'.$h[0].$h[0].$h[1].$h[1].$h[2].$h[2]);
        }

        return '';
    }

    /**
     * Standaard secundaire kleur bij een primaire merkkleur.
     */
    public static function defaultSecondaryFor(string $primary): string
    {
        $primary = self::normalizeHexColor($primary);
        $map = [
            '#2563eb' => '#0f172a',
            '#5540af' => '#1e1b4b',
            '#4f46e5' => '#1e1b4b',
            '#7e3af2' => '#1e1b4b',
            '#4a6cf7' => '#0f172a',
            '#e91e63' => '#1a1a2e',
            '#f97316' => '#171717',
        ];

        return $map[$primary] ?? '#0f172a';
    }
}
