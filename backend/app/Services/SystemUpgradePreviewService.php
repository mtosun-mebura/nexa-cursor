<?php

namespace App\Services;

use Symfony\Component\Process\Process;

class SystemUpgradePreviewService
{
    public function __construct(
        protected SystemStackSnapshotService $snapshots,
    ) {}

    /**
     * @return array{
     *     release: array{current: string, after_success: string},
     *     items: list<array{
     *         id: string,
     *         group: string,
     *         label: string,
     *         current: string,
     *         target: string|null,
     *         upgradable: bool,
     *         default_selected: bool,
     *         selectable: bool,
     *         status: string
     *     }>
     * }
     */
    public function preview(): array
    {
        $currentRelease = $this->snapshots->currentReleaseVersion();

        $items = $this->finalizeItems(array_merge(
            $this->composerItems(),
            $this->runtimeItems(),
            $this->npmItems(),
            $this->stepItems(),
        ));

        return [
            'release' => [
                'current' => $currentRelease,
                'after_success' => $this->snapshots->bumpReleasePatch($currentRelease),
            ],
            'items' => $items,
        ];
    }

    /**
     * @return list<array{id: string, group: string, label: string, current: string, target: string|null, upgradable: bool, default_selected: bool}>
     */
    private function composerItems(): array
    {
        if (! $this->commandExists('composer')) {
            return [$this->item(
                id: 'step:composer',
                group: 'composer',
                label: 'Composer dependencies',
                current: '—',
                target: null,
                selectable: false,
                status: 'Niet beschikbaar',
            )];
        }

        $process = Process::fromShellCommandline(
            'composer outdated --direct --format=json --no-ansi',
            base_path(),
            null,
            null,
            120
        );
        $process->run();

        if (! $process->isSuccessful()) {
            return [$this->item(
                id: 'step:composer',
                group: 'composer',
                label: 'Composer dependencies',
                current: 'Onbekend',
                target: 'Kon niet controleren',
                selectable: true,
                status: '',
                defaultSelected: true,
            )];
        }

        $data = json_decode($process->getOutput(), true);
        $installed = is_array($data['installed'] ?? null) ? $data['installed'] : [];
        $items = [];

        foreach ($installed as $package) {
            $name = (string) ($package['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $current = $this->normalizeVersion((string) ($package['version'] ?? ''));
            $latest = $this->normalizeVersion((string) ($package['latest'] ?? ''));
            $hasUpdate = $latest !== '' && $latest !== $current;

            $items[] = $this->item(
                id: 'pkg:composer:'.$name,
                group: 'composer',
                label: $this->humanPackageLabel($name),
                current: $current !== '' ? $current : '—',
                target: $hasUpdate ? $latest : null,
                selectable: $hasUpdate,
                status: $hasUpdate ? '' : 'Actueel',
                defaultSelected: $hasUpdate,
            );
        }

        if ($items === []) {
            $items[] = $this->item(
                id: 'step:composer',
                group: 'composer',
                label: 'Composer dependencies',
                current: 'Actueel',
                target: null,
                selectable: false,
                status: 'Actueel',
            );
        }

        return $items;
    }

    /**
     * @return list<array{id: string, group: string, label: string, current: string, target: string|null, upgradable: bool, default_selected: bool}>
     */
    private function runtimeItems(): array
    {
        $stack = $this->snapshots->capture();

        return [
            $this->item(
                id: 'runtime:php',
                group: 'runtime',
                label: 'PHP',
                current: $this->displayVersion($stack['php'] ?? '—'),
                target: 'Rebuild Docker-image',
                selectable: false,
                status: 'Via Docker',
            ),
            $this->item(
                id: 'runtime:postgresql',
                group: 'runtime',
                label: 'PostgreSQL',
                current: $this->displayVersion($this->extractPostgresVersion((string) ($stack['postgresql'] ?? '—'))),
                target: 'Rebuild + data-migratie',
                selectable: false,
                status: 'Via Docker',
            ),
            $this->item(
                id: 'runtime:node',
                group: 'runtime',
                label: 'Node.js',
                current: $this->displayVersion($stack['node'] ?? '—'),
                target: 'Rebuild Docker-image',
                selectable: false,
                status: 'Via Docker',
            ),
            $this->item(
                id: 'runtime:npm',
                group: 'runtime',
                label: 'NPM',
                current: $this->displayVersion($stack['npm'] ?? '—'),
                target: 'Rebuild Docker-image',
                selectable: false,
                status: 'Via Docker',
            ),
        ];
    }

    /**
     * @return list<array{id: string, group: string, label: string, current: string, target: string|null, upgradable: bool, default_selected: bool}>
     */
    private function npmItems(): array
    {
        $stack = $this->snapshots->capture();
        $trackedPackages = [
            'vue' => 'Vue.js',
            'vite' => 'Vite',
            'tailwindcss' => 'Tailwind CSS',
        ];

        $outdated = $this->npmOutdatedMap();
        $items = [];
        $seen = [];

        foreach ($trackedPackages as $name => $label) {
            $current = $this->normalizeVersion((string) ($stack[$name] ?? ''));
            if ($current === '' || $current === '—') {
                continue;
            }

            $latest = isset($outdated[$name])
                ? $this->normalizeVersion((string) ($outdated[$name]['latest'] ?? ''))
                : '';
            $hasUpdate = $latest !== '' && $latest !== $current;

            $items[] = $this->item(
                id: 'pkg:npm:'.$name,
                group: 'npm',
                label: $label,
                current: $current,
                target: $hasUpdate ? $latest : null,
                selectable: $hasUpdate && $this->commandExists('npm'),
                status: $hasUpdate ? '' : 'Actueel',
                defaultSelected: $hasUpdate,
            );
            $seen[$name] = true;
        }

        foreach ($outdated as $name => $info) {
            if (isset($seen[$name]) || ! is_array($info)) {
                continue;
            }

            $current = $this->normalizeVersion((string) ($info['current'] ?? ''));
            $latest = $this->normalizeVersion((string) ($info['latest'] ?? ''));
            $hasUpdate = $latest !== '' && $latest !== $current;
            if (! $hasUpdate) {
                continue;
            }

            $items[] = $this->item(
                id: 'pkg:npm:'.$name,
                group: 'npm',
                label: $this->humanPackageLabel((string) $name),
                current: $current !== '' ? $current : '—',
                target: $latest,
                selectable: $this->commandExists('npm'),
                status: '',
                defaultSelected: true,
            );
        }

        if ($items === []) {
            if (! $this->commandExists('npm')) {
                return [$this->item(
                    id: 'step:npm',
                    group: 'npm',
                    label: 'NPM dependencies',
                    current: 'Niet beschikbaar in container',
                    target: null,
                    selectable: false,
                    status: 'Niet beschikbaar',
                )];
            }

            return [$this->item(
                id: 'step:npm',
                group: 'npm',
                label: 'NPM dependencies',
                current: 'Actueel',
                target: null,
                selectable: false,
                status: 'Actueel',
            )];
        }

        return $items;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function npmOutdatedMap(): array
    {
        if (! $this->commandExists('npm')) {
            return [];
        }

        $process = Process::fromShellCommandline(
            'npm outdated --json',
            base_path(),
            null,
            null,
            120
        );
        $process->run();

        $raw = trim($process->getOutput());
        if ($raw === '' || $raw === '{}') {
            return [];
        }

        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    /**
     * @return list<array{id: string, group: string, label: string, current: string, target: string|null, upgradable: bool, default_selected: bool}>
     */
    private function stepItems(): array
    {
        $pendingMigrations = $this->pendingMigrationCount();
        $npmAvailable = $this->commandExists('npm');

        return [
            $this->item(
                id: 'step:npm_build',
                group: 'build',
                label: 'Frontend assets bouwen',
                current: $npmAvailable ? 'npm run build' : 'Niet beschikbaar',
                target: 'Nieuwe build',
                selectable: false,
                status: $npmAvailable ? 'Niet nodig' : 'Niet beschikbaar',
            ),
            $this->item(
                id: 'step:migrations',
                group: 'database',
                label: 'Database migraties',
                current: $pendingMigrations > 0 ? $pendingMigrations.' openstaand' : 'Geen openstaande',
                target: $pendingMigrations > 0 ? 'Uitvoeren' : null,
                selectable: $pendingMigrations > 0,
                status: $pendingMigrations > 0 ? '' : 'Geen openstaande',
                defaultSelected: $pendingMigrations > 0,
            ),
            $this->item(
                id: 'step:tests',
                group: 'quality',
                label: 'Unit tests',
                current: 'Test suite',
                target: 'Uitvoeren na upgrade',
                selectable: false,
                status: 'Niet nodig',
            ),
        ];
    }

    /**
     * @param  list<array{id: string, group: string, label: string, current: string, target: string|null, upgradable: bool, default_selected: bool, selectable: bool, status: string}>  $items
     * @return list<array{id: string, group: string, label: string, current: string, target: string|null, upgradable: bool, default_selected: bool, selectable: bool, status: string}>
     */
    private function finalizeItems(array $items): array
    {
        $hasPackageUpdates = false;
        $hasNpmPackageUpdates = false;

        foreach ($items as $item) {
            if (! $item['selectable']) {
                continue;
            }

            if (str_starts_with($item['id'], 'pkg:composer:')) {
                $hasPackageUpdates = true;
            }

            if (str_starts_with($item['id'], 'pkg:npm:')) {
                $hasPackageUpdates = true;
                $hasNpmPackageUpdates = true;
            }

            if ($item['id'] === 'step:migrations') {
                $hasPackageUpdates = true;
            }
        }

        foreach ($items as $index => $item) {
            if ($item['id'] === 'step:npm_build') {
                $selectable = $hasNpmPackageUpdates && $this->commandExists('npm');
                $items[$index]['selectable'] = $selectable;
                $items[$index]['upgradable'] = $selectable;
                $items[$index]['default_selected'] = $selectable;
                $items[$index]['status'] = $selectable ? '' : ($this->commandExists('npm') ? 'Niet nodig' : 'Niet beschikbaar');
            }

            if ($item['id'] === 'step:tests') {
                $selectable = $hasPackageUpdates;
                $items[$index]['selectable'] = $selectable;
                $items[$index]['upgradable'] = $selectable;
                $items[$index]['default_selected'] = $selectable;
                $items[$index]['status'] = $selectable ? '' : 'Niet nodig';
            }
        }

        return $items;
    }

    /**
     * @return array{id: string, group: string, label: string, current: string, target: string|null, upgradable: bool, default_selected: bool, selectable: bool, status: string}
     */
    private function item(
        string $id,
        string $group,
        string $label,
        string $current,
        ?string $target,
        bool $selectable,
        string $status,
        bool $defaultSelected = false,
    ): array {
        return [
            'id' => $id,
            'group' => $group,
            'label' => $label,
            'current' => $current,
            'target' => $target,
            'upgradable' => $selectable,
            'default_selected' => $defaultSelected,
            'selectable' => $selectable,
            'status' => $status,
        ];
    }

    private function pendingMigrationCount(): int
    {
        try {
            $migrator = app('migrator');
            $paths = [database_path('migrations')];
            $files = $migrator->getMigrationFiles($paths);
            $ran = $migrator->getRepository()->getRan();

            return count(array_diff(array_keys($files), $ran));
        } catch (\Throwable) {
            return 0;
        }
    }

    private function humanPackageLabel(string $name): string
    {
        $map = [
            'laravel/framework' => 'Laravel',
            'livewire/livewire' => 'Livewire',
            'phpunit/phpunit' => 'PHPUnit',
            'vue' => 'Vue.js',
            'vite' => 'Vite',
            'tailwindcss' => 'Tailwind CSS',
        ];

        return $map[$name] ?? $name;
    }

    private function normalizeVersion(string $version): string
    {
        return ltrim(trim($version), 'v');
    }

    private function displayVersion(string $version): string
    {
        $version = trim($version);

        return $version !== '' ? $version : '—';
    }

    private function extractPostgresVersion(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '' || $raw === '—') {
            return $raw;
        }

        if (preg_match('/(\d+(?:\.\d+)*)/', $raw, $matches) === 1) {
            return $matches[1];
        }

        return $raw;
    }

    private function commandExists(string $binary): bool
    {
        if ($binary === '') {
            return false;
        }

        $process = Process::fromShellCommandline('command -v '.escapeshellarg($binary), base_path(), null, null, 10);
        $process->run();

        return $process->isSuccessful();
    }
}
