<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SystemLaravelPackageCompatibility
{
    /**
     * @return array<string, string> package => constraint
     */
    public function rootPackagesFromComposerJson(?string $composerJson = null): array
    {
        $composerJson ??= $this->readFile(base_path('composer.json'));
        $data = json_decode($composerJson, true);
        if (! is_array($data)) {
            return [];
        }

        $packages = [];
        foreach (['require', 'require-dev'] as $section) {
            foreach ($data[$section] ?? [] as $name => $constraint) {
                if (! is_string($name) || ! is_string($constraint) || $this->shouldSkipRootPackage($name)) {
                    continue;
                }
                $packages[$name] = $constraint;
            }
        }

        return $packages;
    }

    /**
     * Root packages whose huidige lock-versie Laravel $major niet toelaat.
     *
     * @return array<string, array{constraint: string, installed: string|null, require: array<string, string>, conflict: array<string, string>}>
     */
    public function incompatibleRootPackages(int $major, ?string $composerJson = null, ?string $lockJson = null): array
    {
        $root = $this->rootPackagesFromComposerJson($composerJson);
        $locked = $this->lockPackagesByName($lockJson);
        $incompatible = [];

        foreach ($root as $name => $constraint) {
            $meta = $locked[$name] ?? null;
            $require = is_array($meta['require'] ?? null) ? $this->stringMap($meta['require']) : [];
            $conflict = is_array($meta['conflict'] ?? null) ? $this->stringMap($meta['conflict']) : [];
            if ($this->dependenciesAllowLaravelMajor($require, $conflict, $major)) {
                continue;
            }

            $incompatible[$name] = [
                'constraint' => $constraint,
                'installed' => isset($meta['version']) ? $this->normalizeVersion((string) $meta['version']) : null,
                'require' => $require,
                'conflict' => $conflict,
            ];
        }

        return $incompatible;
    }

    /**
     * @return array{
     *     blockers: list<array{package: string, installed: string|null, message: string}>,
     *     pre_upgrades: list<array{package: string, installed: string|null, constraint: string, new_constraint: string|null, target_version: string|null}>,
     *     joint_upgrades: list<array{package: string, installed: string|null, constraint: string, new_constraint: string|null, target_version: string|null}>
     * }
     */
    public function planForMajor(
        int $targetMajor,
        int $currentMajor,
        array $guideConstraints = [],
        ?string $composerJson = null,
        ?string $lockJson = null,
    ): array {
        $root = $this->rootPackagesFromComposerJson($composerJson);
        $incompatible = $this->incompatibleRootPackages($targetMajor, $composerJson, $lockJson);
        $names = array_values(array_unique(array_merge(
            array_keys($incompatible),
            array_keys(array_intersect_key($guideConstraints, $root)),
        )));

        $packagist = $this->packagistStableVersionsForPackages($names);

        $blockers = [];
        $pre = [];
        $joint = [];
        $seen = [];

        foreach ($incompatible as $name => $info) {
            $seen[$name] = true;
            if (! array_key_exists($name, $packagist) || $packagist[$name] === null) {
                $blockers[] = [
                    'package' => $name,
                    'installed' => $info['installed'],
                    'message' => 'Kon versies van '.$name.' niet ophalen bij Packagist. De Laravel-major wordt niet gestart.',
                ];
                continue;
            }

            $versions = $packagist[$name];
            $both = $this->latestCompatibleVersion($versions, $targetMajor, $currentMajor);
            $targetOnly = $both ?? $this->latestCompatibleVersion($versions, $targetMajor, null);
            $entry = $this->upgradeEntry($name, $info['constraint'], $info['installed'], $targetOnly);

            if ($targetOnly === null) {
                $blockers[] = [
                    'package' => $name,
                    'installed' => $info['installed'],
                    'message' => $name.' '.(string) ($info['installed'] ?? $info['constraint'])
                        .' ondersteunt Laravel '.$targetMajor.' niet, en Packagist heeft geen stabiele versie die dat wel doet.',
                ];
                continue;
            }

            if ($both !== null) {
                $pre[] = $entry;
            } else {
                $joint[] = $entry;
            }
        }

        foreach ($guideConstraints as $name => $constraint) {
            if (! isset($root[$name]) || isset($seen[$name]) || $root[$name] === $constraint) {
                continue;
            }
            $locked = $this->lockPackagesByName($lockJson)[$name] ?? [];
            $pre[] = $this->upgradeEntry(
                $name,
                $root[$name],
                isset($locked['version']) ? $this->normalizeVersion((string) $locked['version']) : null,
                $this->latestCompatibleVersion($packagist[$name] ?? [], $targetMajor, $currentMajor),
                $constraint,
            );
        }

        return [
            'blockers' => $blockers,
            'pre_upgrades' => $pre,
            'joint_upgrades' => $joint,
        ];
    }

    public function constraintAllowsMajor(string $constraint, int $major): bool
    {
        foreach ([$major.'.0.0', $major.'.1.0', $major.'.50.0'] as $probe) {
            if ($this->constraintMatchesVersion($constraint, $probe)) {
                return true;
            }
        }

        return false;
    }

    public function constraintMatchesVersion(string $constraint, string $version): bool
    {
        $constraint = trim($constraint);
        $version = $this->normalizeVersion($version);
        if ($constraint === '' || $constraint === '*' || str_starts_with($constraint, 'dev-')) {
            return true;
        }

        $ors = preg_split('/\s*\|\|?\s*/', $constraint) ?: [];
        foreach ($ors as $or) {
            $or = trim($or);
            if ($or === '') {
                continue;
            }
            $ands = preg_split('/\s*,\s*/', $or) ?: [];
            $ok = true;
            foreach ($ands as $and) {
                if (! $this->singleConstraintMatchesVersion(trim($and), $version)) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                return true;
            }
        }

        return false;
    }

    public function dependenciesAllowLaravelMajor(array $require, array $conflict, int $major): bool
    {
        $laravelConstraints = [];
        foreach ($require as $name => $constraint) {
            if ($name === 'laravel/framework' || str_starts_with((string) $name, 'illuminate/')) {
                $laravelConstraints[] = (string) $constraint;
            }
        }

        if ($laravelConstraints !== []) {
            foreach ($laravelConstraints as $constraint) {
                if (! $this->constraintAllowsMajor($constraint, $major)) {
                    return false;
                }
            }
        }

        foreach ($conflict as $name => $constraint) {
            if ($name !== 'laravel/framework' && ! str_starts_with((string) $name, 'illuminate/')) {
                continue;
            }
            if ($this->constraintBlocksEntireMajor((string) $constraint, $major)) {
                return false;
            }
        }

        return true;
    }

    public function releaseSupportsLaravelMajor(array $packageMeta, int $major): bool
    {
        $require = is_array($packageMeta['require'] ?? null) ? $this->stringMap($packageMeta['require']) : [];
        $conflict = is_array($packageMeta['conflict'] ?? null) ? $this->stringMap($packageMeta['conflict']) : [];

        return $this->dependenciesAllowLaravelMajor($require, $conflict, $major);
    }

    public function constraintForCompatibleVersion(string $version): string
    {
        $major = $this->majorOf($version);

        return $major !== null ? '^'.$major.'.0' : '^'.$this->normalizeVersion($version);
    }

    /**
     * @param  list<string>  $packages
     * @return array<string, list<array<string, mixed>>|null>
     */
    public function packagistStableVersionsForPackages(array $packages): array
    {
        $packages = array_values(array_unique(array_filter($packages)));
        if ($packages === []) {
            return [];
        }

        $result = [];
        foreach ($packages as $package) {
            try {
                $response = Http::timeout(20)
                    ->acceptJson()
                    ->get('https://repo.packagist.org/p2/'.$package.'.json');
            } catch (\Throwable) {
                $result[$package] = null;
                continue;
            }

            if (! $response->successful()) {
                $result[$package] = null;
                continue;
            }

            $items = $response->json('packages.'.$package);
            $result[$package] = is_array($items) ? $this->stableReleases($items) : [];
        }

        return $result;
    }

    /**
     * @param  list<array<string, mixed>>  $releases
     */
    public function latestCompatibleVersion(array $releases, int $targetMajor, ?int $alsoMajor = null): ?string
    {
        $best = null;
        foreach ($releases as $release) {
            if (! $this->releaseSupportsLaravelMajor($release, $targetMajor)) {
                continue;
            }
            if ($alsoMajor !== null && ! $this->releaseSupportsLaravelMajor($release, $alsoMajor)) {
                continue;
            }
            $version = $this->normalizeVersion((string) ($release['version'] ?? ''));
            if ($version === '' || preg_match('/^\d+\.\d+\.\d+$/', $version) !== 1) {
                continue;
            }
            if ($best === null || version_compare($version, $best, '>')) {
                $best = $version;
            }
        }

        return $best;
    }

    /**
     * @param  array<string, mixed>|null  $lockJson
     * @return array<string, array<string, mixed>>
     */
    public function lockPackagesByName(?string $lockJson = null): array
    {
        $lockJson ??= $this->readFile(base_path('composer.lock'));
        $data = json_decode($lockJson, true);
        if (! is_array($data)) {
            return [];
        }

        $byName = [];
        foreach (['packages', 'packages-dev'] as $section) {
            foreach ($data[$section] ?? [] as $package) {
                if (! is_array($package) || ! is_string($package['name'] ?? null)) {
                    continue;
                }
                $byName[$package['name']] = $package;
            }
        }

        return $byName;
    }

    private function shouldSkipRootPackage(string $name): bool
    {
        return $name === 'php'
            || $name === 'laravel/framework'
            || str_starts_with($name, 'ext-')
            || $name === 'composer-plugin-api';
    }

    /**
     * @param  list<mixed>  $items
     * @return list<array<string, mixed>>
     */
    private function stableReleases(array $items): array
    {
        $releases = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $version = $this->normalizeVersion((string) ($item['version'] ?? ''));
            if ($version !== '' && preg_match('/^\d+\.\d+\.\d+$/', $version) === 1) {
                $releases[] = $item;
            }
        }

        return $releases;
    }

    /**
     * @return array{package: string, installed: string|null, constraint: string, new_constraint: string|null, target_version: string|null}
     */
    private function upgradeEntry(
        string $package,
        string $constraint,
        ?string $installed,
        ?string $targetVersion,
        ?string $forcedConstraint = null,
    ): array {
        $newConstraint = $forcedConstraint;
        if ($newConstraint === null && $targetVersion !== null) {
            $candidate = $this->constraintForCompatibleVersion($targetVersion);
            $newConstraint = $this->constraintMatchesVersion($constraint, $targetVersion) ? null : $candidate;
        }

        return [
            'package' => $package,
            'installed' => $installed,
            'constraint' => $constraint,
            'new_constraint' => $newConstraint,
            'target_version' => $targetVersion,
        ];
    }

    private function constraintBlocksEntireMajor(string $constraint, int $major): bool
    {
        foreach ([$major.'.0.0', $major.'.1.0', $major.'.50.0'] as $probe) {
            if (! $this->constraintMatchesVersion($constraint, $probe)) {
                return false;
            }
        }

        return true;
    }

    private function singleConstraintMatchesVersion(string $part, string $version): bool
    {
        if ($part === '' || $part === '*') {
            return true;
        }

        if (preg_match('/^(>=|>|<=|<|!=)\s*v?(\d+(?:\.\d+){0,2})/', $part, $matches) === 1) {
            $bound = $this->padVersion($matches[2]);

            return version_compare($version, $bound, $matches[1]);
        }

        if (preg_match('/^\^v?(\d+(?:\.\d+){0,2})/', $part, $matches) === 1) {
            $min = $this->padVersion($matches[1]);
            $major = (int) explode('.', $min)[0];
            $max = ($major + 1).'.0.0';

            return version_compare($version, $min, '>=') && version_compare($version, $max, '<');
        }

        if (preg_match('/^~v?(\d+)\.(\d+)/', $part, $matches) === 1) {
            $min = $matches[1].'.'.$matches[2].'.0';
            $max = $matches[1].'.'.((int) $matches[2] + 1).'.0';

            return version_compare($version, $min, '>=') && version_compare($version, $max, '<');
        }

        if (preg_match('/^v?(\d+)\.(?:\*|x)/', $part, $matches) === 1) {
            return $this->majorOf($version) === (int) $matches[1];
        }

        if (preg_match('/^v?(\d+(?:\.\d+){0,2})$/', $part, $matches) === 1) {
            return version_compare($version, $this->padVersion($matches[1]), '==');
        }

        return true;
    }

    /**
     * @param  array<mixed>  $values
     * @return array<string, string>
     */
    private function stringMap(array $values): array
    {
        $map = [];
        foreach ($values as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $map[$key] = $value;
            }
        }

        return $map;
    }

    private function padVersion(string $version): string
    {
        $parts = explode('.', $this->normalizeVersion($version));

        return implode('.', [
            $parts[0] ?? '0',
            $parts[1] ?? '0',
            $parts[2] ?? '0',
        ]);
    }

    private function majorOf(string $version): ?int
    {
        $version = $this->normalizeVersion($version);
        if ($version === '' || preg_match('/^(\d+)\./', $version, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    private function normalizeVersion(string $version): string
    {
        $version = ltrim(trim($version), 'v');
        if (preg_match('/^(\d+\.\d+\.\d+)/', $version, $matches) === 1) {
            return $matches[1];
        }
        if (preg_match('/^(\d+\.\d+)/', $version, $matches) === 1) {
            return $matches[1].'.0';
        }
        if (preg_match('/^(\d+)$/', $version, $matches) === 1) {
            return $matches[1].'.0.0';
        }

        return $version;
    }

    private function readFile(string $path): string
    {
        return is_file($path) ? (string) file_get_contents($path) : '{}';
    }
}
