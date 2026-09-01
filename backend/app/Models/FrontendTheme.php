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

    public function getSettings(): array
    {
        return $this->settings ?? [];
    }
}
