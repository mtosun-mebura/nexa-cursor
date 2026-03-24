<?php

/**
 * Bouwt app/Database/Pre2026Baseline.php uit database/migrations_archive/pre-2026-baseline/.
 * Vereist dat die map bestaat (bijv. uit git-tag of backup). Eenmalig / bij bewuste baseline-wijziging:
 *   php scripts/build-pre2026-baseline.php
 */
declare(strict_types=1);

$backendRoot = dirname(__DIR__);
$archiveRoot = $backendRoot.'/database/migrations_archive/pre-2026-baseline';

$directories = [
    'core' => $archiveRoot.'/core',
    'shared' => $archiveRoot.'/shared',
    'taxiroyaal' => $archiveRoot.'/modules/taxiroyaal',
    'skillmatching' => $archiveRoot.'/modules/skillmatching',
];

$files = [];
foreach ($directories as $set => $dir) {
    if (! is_dir($dir)) {
        fwrite(STDERR, "Ontbrekende map: {$dir}\n");
        exit(1);
    }
    foreach (glob($dir.'/*.php') ?: [] as $file) {
        $files[] = ['set' => $set, 'path' => $file, 'basename' => basename($file)];
    }
}

usort($files, fn (array $a, array $b): int => strcmp($a['basename'], $b['basename']));

$allUses = [];
$steps = [];

foreach ($files as $entry) {
    $path = $entry['path'];
    $parsed = parseMigrationFile($path);
    foreach ($parsed['uses'] as $u) {
        $allUses[$u] = true;
    }
    $steps[] = [
        'set' => $entry['set'],
        'basename' => $entry['basename'],
        'expr' => $parsed['expr'],
    ];
}

$useLines = array_keys($allUses);
sort($useLines);

$out = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Database;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Config;

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

PHP;

foreach ($steps as $step) {
    $set = addslashes($step['set']);
    $base = addslashes($step['basename']);
    $expr = $step['expr'];
    $out .= "            [\n";
    $out .= "                'set' => '{$set}',\n";
    $out .= "                'basename' => '{$base}',\n";
    $out .= "                'run' => static function (): void {\n";
    $out .= '                    (';
    $out .= trim($expr);
    $out .= ")->up();\n";
    $out .= "                },\n";
    $out .= "            ],\n";
}

// Anonymous classes erven namespace App\Database — kwalificeer globale built-ins (bv. Exception).
$out = preg_replace('/\bnew Exception\s*\(/', 'new \\Exception(', $out);

$out .= <<<'PHP'
        ];
    }

    public static function runFull(): void
    {
        $skipModules = self::shouldSkipModuleMigrations();
        foreach (self::steps() as $step) {
            if ($skipModules && in_array($step['set'], ['taxiroyaal', 'skillmatching'], true)) {
                continue;
            }
            ($step['run'])();
        }
    }

    /**
     * @param  list<string>  $allowedSets  bv. ['core','shared','taxiroyaal']
     */
    public static function runForSetsOnConnection(array $allowedSets, string $connectionName): void
    {
        $allowedSets = array_values(array_unique(array_map('strtolower', $allowedSets)));
        $previous = Config::get('database.default');
        Config::set('database.default', $connectionName);
        try {
            foreach (self::steps() as $step) {
                if (! in_array($step['set'], $allowedSets, true)) {
                    continue;
                }
                ($step['run'])();
            }
        } finally {
            Config::set('database.default', $previous);
        }
    }

    protected static function shouldSkipModuleMigrations(): bool
    {
        return config('database.default') === 'sqlite';
    }
}

PHP;

// Prepend merged use statements (after namespace, before final class uses)
$useBlock = '';
foreach ($useLines as $line) {
    $useBlock .= $line."\n";
}

$out = preg_replace(
    '/namespace App\\\\Database;\n\nuse Illuminate\\\\Database\\\\Migrations\\\\Migration;/',
    "namespace App\\Database;\n\n".$useBlock,
    $out,
    1
);

$target = $backendRoot.'/app/Database/Pre2026Baseline.php';
if (! is_dir(dirname($target))) {
    mkdir(dirname($target), 0755, true);
}
file_put_contents($target, $out);
echo "Geschreven: {$target} (".count($steps)." stappen)\n";

/**
 * @return array{uses: list<string>, expr: string}
 */
function parseMigrationFile(string $path): array
{
    $code = file_get_contents($path);
    if ($code === false) {
        throw new RuntimeException("Kan niet lezen: {$path}");
    }

    $uses = [];
    if (preg_match_all('/^use\s+[^;]+;\s*$/m', $code, $m)) {
        foreach ($m[0] as $line) {
            $uses[] = trim($line);
        }
    }

    $tokens = token_get_all($code);
    $start = null;
    $n = count($tokens);
    for ($i = 0; $i < $n; $i++) {
        if (! is_array($tokens[$i])) {
            continue;
        }
        if ($tokens[$i][0] !== T_RETURN) {
            continue;
        }
        $j = $i + 1;
        while ($j < $n && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }
        if ($j >= $n || ! is_array($tokens[$j]) || $tokens[$j][0] !== T_NEW) {
            continue;
        }
        $start = $j;
        break;
    }
    if ($start === null) {
        throw new RuntimeException("Geen 'return new class' in {$path}");
    }

    $expr = extractExpressionFromNewClass($tokens, $start, $n);

    return ['uses' => $uses, 'expr' => $expr];
}

/**
 * Vanaf T_NEW tot en met het sluitende `}` van de anonymous class (expressie = `new class ...`).
 */
function extractExpressionFromNewClass(array $tokens, int $start, int $n): string
{
    $depth = 0;
    $started = false;
    $buf = '';
    for ($k = $start; $k < $n; $k++) {
        $t = $tokens[$k];
        if (is_array($t)) {
            $type = $t[0];
            $text = $t[1];
            if (! shouldCountBracesInToken($type)) {
                $buf .= $text;

                continue;
            }
            $buf .= $text;
            $open = substr_count($text, '{');
            $close = substr_count($text, '}');
            if (! $started && $open > 0) {
                $started = true;
            }
            $depth += $open - $close;
            if ($started && $depth === 0) {
                return trim($buf);
            }
        } else {
            $buf .= $t;
            if ($t === '{') {
                $depth++;
                $started = true;
            } elseif ($t === '}') {
                $depth--;
                if ($started && $depth === 0) {
                    return trim($buf);
                }
            }
        }
    }

    throw new RuntimeException('Kon anonymous class-expressie niet parsen');
}

/**
 * T_DOUBLE_QUOTE en delen van encapsed strings bevatten soms `{` (bijv. regex) — niet meetellen.
 */
function shouldCountBracesInToken(int $type): bool
{
    $n = token_name($type);
    if ($n === null) {
        return true;
    }
    foreach (['STRING', 'HEREDOC', 'NOWDOC', 'COMMENT', 'WHITESPACE', 'INLINE_HTML', 'ENCAPSED'] as $p) {
        if (str_contains($n, $p)) {
            return false;
        }
    }

    return true;
}
