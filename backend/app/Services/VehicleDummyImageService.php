<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Modules\NexaTaxi\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Platform-brede dummy/placeholder-afbeeldingen per voertuigtype (Auto / Busje / Bus).
 */
class VehicleDummyImageService
{
    public const SETTING_BACKGROUND = 'vehicle_dummy_background_color';

    public const SETTING_SELECTED = 'vehicle_dummy_selected';

    public const SETTING_CUSTOM = 'vehicle_dummy_custom_images';

    public const TYPES = [
        Vehicle::TYPE_CAR,
        Vehicle::TYPE_VAN,
        Vehicle::TYPE_BUS,
    ];

    /**
     * @return list<array{id: string, type: string, url: string, path: string|null, builtin: bool, label: string}>
     */
    public function builtins(): array
    {
        return [
            [
                'id' => 'builtin-car-yellow',
                'type' => Vehicle::TYPE_CAR,
                'url' => asset('modules/nexa-taxi/vehicle-dummy-car-yellow.png'),
                'path' => null,
                'builtin' => true,
                'label' => __('Gele taxi (auto)'),
            ],
            [
                'id' => 'builtin-car-black',
                'type' => Vehicle::TYPE_CAR,
                'url' => asset('modules/nexa-taxi/vehicle-dummy-car-black.png'),
                'path' => null,
                'builtin' => true,
                'label' => __('Zwarte taxi (auto)'),
            ],
            [
                'id' => 'builtin-van-yellow',
                'type' => Vehicle::TYPE_VAN,
                'url' => asset('modules/nexa-taxi/vehicle-dummy-van-yellow.png'),
                'path' => null,
                'builtin' => true,
                'label' => __('Gele taxi (busje)'),
            ],
            [
                'id' => 'builtin-van-black',
                'type' => Vehicle::TYPE_VAN,
                'url' => asset('modules/nexa-taxi/vehicle-dummy-van-black.png'),
                'path' => null,
                'builtin' => true,
                'label' => __('Zwarte taxi (busje)'),
            ],
            [
                'id' => 'builtin-bus',
                'type' => Vehicle::TYPE_BUS,
                'url' => asset('modules/nexa-taxi/vehicle-dummy-bus.png'),
                'path' => null,
                'builtin' => true,
                'label' => __('Touringbus'),
            ],
        ];
    }

    /**
     * @return array{car: string, van: string, bus: string}
     */
    public function defaultSelectedIds(): array
    {
        return [
            Vehicle::TYPE_CAR => 'builtin-car-yellow',
            Vehicle::TYPE_VAN => 'builtin-van-yellow',
            Vehicle::TYPE_BUS => 'builtin-bus',
        ];
    }

    /**
     * Achtergrondkleur voor het dummy-vlak; null = transparant.
     */
    public function backgroundColor(): ?string
    {
        $raw = trim((string) GeneralSetting::get(self::SETTING_BACKGROUND, ''));
        if ($raw === '' || strcasecmp($raw, 'transparent') === 0) {
            return null;
        }
        if (preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $raw) !== 1) {
            return null;
        }

        return strtoupper($raw);
    }

    public function setBackgroundColor(?string $color): void
    {
        $raw = trim((string) $color);
        if ($raw === '' || strcasecmp($raw, 'transparent') === 0) {
            GeneralSetting::set(self::SETTING_BACKGROUND, '');

            return;
        }
        if (preg_match('/^#?([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})$/', $raw, $m) !== 1) {
            GeneralSetting::set(self::SETTING_BACKGROUND, '');

            return;
        }
        $hex = $m[1];
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        GeneralSetting::set(self::SETTING_BACKGROUND, '#'.strtoupper($hex));
    }

    /**
     * @return array{car: string, van: string, bus: string}
     */
    public function selectedIds(): array
    {
        $defaults = $this->defaultSelectedIds();
        $raw = GeneralSetting::get(self::SETTING_SELECTED, '');
        if (! is_string($raw) || trim($raw) === '') {
            return $defaults;
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return $defaults;
        }
        $out = $defaults;
        foreach (self::TYPES as $type) {
            $id = isset($decoded[$type]) ? trim((string) $decoded[$type]) : '';
            if ($id !== '' && $this->findById($id) !== null) {
                $out[$type] = $id;
            }
        }

        return $out;
    }

    public function select(string $type, string $id): bool
    {
        $type = $this->normalizeType($type);
        if ($type === null) {
            return false;
        }
        $item = $this->findById($id);
        if ($item === null || ($item['type'] ?? '') !== $type) {
            return false;
        }
        $selected = $this->selectedIds();
        $selected[$type] = $id;
        GeneralSetting::set(self::SETTING_SELECTED, json_encode($selected, JSON_UNESCAPED_SLASHES));

        return true;
    }

    /**
     * @return list<array{id: string, type: string, url: string, path: string|null, builtin: bool, label: string}>
     */
    public function customImages(): array
    {
        $raw = GeneralSetting::get(self::SETTING_CUSTOM, '[]');
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return [];
        }
        $out = [];
        foreach ($decoded as $row) {
            if (! is_array($row)) {
                continue;
            }
            $id = trim((string) ($row['id'] ?? ''));
            $type = $this->normalizeType((string) ($row['type'] ?? ''));
            $path = trim((string) ($row['path'] ?? ''));
            if ($id === '' || $type === null || $path === '' || ! Storage::disk('public')->exists($path)) {
                continue;
            }
            $out[] = [
                'id' => $id,
                'type' => $type,
                'url' => $this->publicUrlForPath($path),
                'path' => $path,
                'builtin' => false,
                'label' => (string) ($row['label'] ?? __('Eigen upload')),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{id: string, type: string, url: string, path: string|null, builtin: bool, label: string, selected: bool}>
     */
    public function imagesForType(string $type): array
    {
        $type = $this->normalizeType($type) ?? Vehicle::TYPE_CAR;
        $selectedId = $this->selectedIds()[$type] ?? null;
        $items = [];
        foreach (array_merge($this->builtins(), $this->customImages()) as $item) {
            if (($item['type'] ?? '') !== $type) {
                continue;
            }
            $item['selected'] = $selectedId !== null && $item['id'] === $selectedId;
            $items[] = $item;
        }

        return $items;
    }

    /**
     * @return array{
     *   background_color: ?string,
     *   selected: array{car: string, van: string, bus: string},
     *   types: array<string, array{label: string, images: list<array{id: string, type: string, url: string, path: string|null, builtin: bool, label: string, selected: bool}>}>
     * }
     */
    public function settingsPayload(): array
    {
        $labels = Vehicle::typeLabels();
        $types = [];
        foreach (self::TYPES as $type) {
            $types[$type] = [
                'label' => $labels[$type] ?? $type,
                'images' => $this->imagesForType($type),
            ];
        }

        return [
            'background_color' => $this->backgroundColor(),
            'selected' => $this->selectedIds(),
            'types' => $types,
        ];
    }

    public function publicUrlForType(?string $type = null, ?string $personRange = null): string
    {
        $resolved = $this->normalizeType((string) $type);
        if ($resolved === null && $personRange !== null) {
            $resolved = Vehicle::typeForPersonRange($personRange);
        }
        $resolved ??= Vehicle::TYPE_CAR;

        $selectedId = $this->selectedIds()[$resolved] ?? null;
        if ($selectedId !== null) {
            $item = $this->findById($selectedId);
            if ($item !== null && ! empty($item['url'])) {
                return (string) $item['url'];
            }
        }

        foreach ($this->builtins() as $builtin) {
            if ($builtin['type'] === $resolved) {
                return $builtin['url'];
            }
        }

        return asset('modules/nexa-taxi/vehicle-dummy-car-yellow.png');
    }

    /**
     * Relatief asset-pad (zonder domain) voor legacy helper die asset() verwacht.
     */
    public function relativeAssetForType(?string $type = null, ?string $personRange = null): string
    {
        $url = $this->publicUrlForType($type, $personRange);
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return 'modules/nexa-taxi/vehicle-dummy-car-yellow.png';
        }
        $path = ltrim($path, '/');
        if (preg_match('#(?:^|/)(modules/nexa-taxi/vehicle-dummy-[a-z0-9-]+\.png)$#', $path, $m)) {
            return $m[1];
        }

        return $path;
    }

    public function addUpload(UploadedFile $file, string $type): array
    {
        $type = $this->normalizeType($type);
        if ($type === null) {
            throw new \InvalidArgumentException(__('Ongeldig voertuigtype.'));
        }

        $dir = 'settings/vehicle-dummies';
        if (! Storage::disk('public')->exists($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }
        $path = $file->store($dir, 'public');
        if (! is_string($path) || $path === '' || ! Storage::disk('public')->exists($path)) {
            throw new \RuntimeException(__('Afbeelding kon niet worden opgeslagen.'));
        }

        $id = 'custom-'.Str::lower(Str::random(10));
        $customs = $this->customImagesRaw();
        $customs[] = [
            'id' => $id,
            'type' => $type,
            'path' => $path,
            'label' => __('Eigen upload'),
        ];
        GeneralSetting::set(self::SETTING_CUSTOM, json_encode(array_values($customs), JSON_UNESCAPED_SLASHES));

        // Auto-select nieuw geüpload item voor dat type
        $this->select($type, $id);

        return [
            'id' => $id,
            'type' => $type,
            'url' => $this->publicUrlForPath($path),
            'path' => $path,
            'builtin' => false,
            'label' => __('Eigen upload'),
            'selected' => true,
        ];
    }

    public function remove(string $id): bool
    {
        $id = trim($id);
        if ($id === '' || str_starts_with($id, 'builtin-')) {
            return false;
        }

        $customs = $this->customImagesRaw();
        $kept = [];
        $removedPath = null;
        $removedType = null;
        foreach ($customs as $row) {
            if (! is_array($row)) {
                continue;
            }
            if (trim((string) ($row['id'] ?? '')) === $id) {
                $removedPath = trim((string) ($row['path'] ?? ''));
                $removedType = $this->normalizeType((string) ($row['type'] ?? ''));

                continue;
            }
            $kept[] = $row;
        }
        if ($removedPath === null && $removedType === null) {
            return false;
        }

        GeneralSetting::set(self::SETTING_CUSTOM, json_encode(array_values($kept), JSON_UNESCAPED_SLASHES));

        if ($removedPath !== '' && Storage::disk('public')->exists($removedPath)) {
            Storage::disk('public')->delete($removedPath);
        }

        if ($removedType !== null) {
            $selected = $this->selectedIds();
            if (($selected[$removedType] ?? '') === $id) {
                $selected[$removedType] = $this->defaultSelectedIds()[$removedType];
                GeneralSetting::set(self::SETTING_SELECTED, json_encode($selected, JSON_UNESCAPED_SLASHES));
            }
        }

        return true;
    }

    public function normalizeType(string $type): ?string
    {
        $type = strtolower(trim($type));
        if (in_array($type, self::TYPES, true)) {
            return $type;
        }
        // UI labels / aliases
        return match ($type) {
            'auto', 'car', 'personenauto' => Vehicle::TYPE_CAR,
            'busje', 'van' => Vehicle::TYPE_VAN,
            'bus' => Vehicle::TYPE_BUS,
            default => null,
        };
    }

    /**
     * @return array{id: string, type: string, url: string, path: string|null, builtin: bool, label: string}|null
     */
    public function findById(string $id): ?array
    {
        $id = trim($id);
        if ($id === '') {
            return null;
        }
        foreach ($this->builtins() as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }
        foreach ($this->customImages() as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return list<array{id: string, type: string, path: string, label?: string}>
     */
    private function customImagesRaw(): array
    {
        $raw = GeneralSetting::get(self::SETTING_CUSTOM, '[]');
        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function publicUrlForPath(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        try {
            $display = app(WebsiteBuilderService::class)->storageUrlToDisplayUrl('/storage/'.$path);
            if (is_string($display) && $display !== '') {
                return $display;
            }
        } catch (\Throwable) {
            // fall through
        }

        return Storage::disk('public')->url($path);
    }
}
