<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hernoemt module "taxiroyaal" naar "taxi" (Nexa Taxi), werkt website_pages bij en migreert
 * home_sections-componentkeys. PostgreSQL: hernoem database handmatig indien nodig:
 * ALTER DATABASE "nexa_taxiroyaal" RENAME TO "nexa_taxi";
 */
return new class extends Migration
{
    private function migrateHomeSectionsJson(?string $json): ?string
    {
        if ($json === null || $json === '') {
            return $json;
        }
        $data = json_decode($json, true);
        if (! is_array($data)) {
            return $json;
        }
        $map = [
            'component:taxiroyaal.tarieven' => 'component:taxi.tarieven',
            'component:taxiroyaal.boekingsmodule' => 'component:taxi.boekingsmodule',
        ];
        $out = [];
        foreach ($data as $key => $value) {
            $newKey = is_string($key) && isset($map[$key]) ? $map[$key] : $key;
            if (array_key_exists($newKey, $out) && is_array($out[$newKey]) && is_array($value)) {
                $out[$newKey] = array_replace_recursive($out[$newKey], $value);
            } else {
                $out[$newKey] = $value;
            }
        }
        if (isset($out['section_order']) && is_array($out['section_order'])) {
            $out['section_order'] = array_values(array_unique(array_map(
                static fn ($k) => is_string($k) ? ($map[$k] ?? $k) : $k,
                $out['section_order']
            ), SORT_REGULAR));
        }
        if (isset($out['visibility']) && is_array($out['visibility'])) {
            $vis = [];
            foreach ($out['visibility'] as $vk => $vv) {
                $nvk = is_string($vk) && isset($map[$vk]) ? $map[$vk] : $vk;
                $vis[$nvk] = $vv;
            }
            $out['visibility'] = $vis;
        }

        return json_encode($out, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function up(): void
    {
        if (Schema::hasTable('modules')) {
            DB::table('modules')->where('name', 'taxiroyaal')->update([
                'name' => 'taxi',
                'display_name' => 'Nexa Taxi',
                'description' => 'Boek eenvoudig een taxi met Nexa Taxi.',
                'updated_at' => now(),
            ]);
        }
        if (Schema::hasTable('website_pages') && Schema::hasColumn('website_pages', 'module_name')) {
            DB::table('website_pages')->whereRaw('LOWER(module_name) = ?', ['taxiroyaal'])->update(['module_name' => 'taxi']);
        }
        if (Schema::hasTable('website_pages') && Schema::hasColumn('website_pages', 'home_sections')) {
            DB::table('website_pages')->orderBy('id')->chunkById(50, function ($rows): void {
                foreach ($rows as $row) {
                    $hs = $row->home_sections ?? null;
                    if ($hs === null || $hs === '') {
                        continue;
                    }
                    try {
                        $new = $this->migrateHomeSectionsJson(is_string($hs) ? $hs : json_encode($hs));
                    } catch (\Throwable) {
                        continue;
                    }
                    if ($new !== null && $new !== $hs) {
                        DB::table('website_pages')->where('id', $row->id)->update(['home_sections' => $new]);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('website_pages') && Schema::hasColumn('website_pages', 'module_name')) {
            DB::table('website_pages')->whereRaw('LOWER(module_name) = ?', ['taxi'])->update(['module_name' => 'taxiroyaal']);
        }
        if (Schema::hasTable('modules')) {
            DB::table('modules')->where('name', 'taxi')->update([
                'name' => 'taxiroyaal',
                'display_name' => 'Taxi Royaal',
                'description' => 'Boek je taxi bij Taxi Royaal!',
                'updated_at' => now(),
            ]);
        }
    }
};
