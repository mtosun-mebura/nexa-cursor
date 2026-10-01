<?php

/**
 * Minimal PDF writer (Helvetica, uncompressed) — reliable in Preview/Safari.
 */
final class SimplePdf
{
    /** @var list<string> */
    private array $pages = [];

    public function addPage(string $contentStream): void
    {
        $this->pages[] = $contentStream;
    }

    public function output(): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        $pageObjectNumbers = [];

        // Reserve object numbers: 1=catalog, 2=pages, 3=font, then pairs of page+content
        $fontObj = 3;
        $next = 4;
        foreach ($this->pages as $i => $stream) {
            $pageObj = $next++;
            $contentObj = $next++;
            $pageObjectNumbers[] = [$pageObj, $contentObj, $stream];
            $kids[] = $pageObj.' 0 R';
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $kids).'] /Count '.count($kids).' >>';
        $objects[$fontObj] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';

        foreach ($pageObjectNumbers as [$pageObj, $contentObj, $stream]) {
            $len = strlen($stream);
            $objects[$pageObj] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] '
                .'/Resources << /Font << /F1 '.$fontObj.' 0 R >> >> '
                .'/Contents '.$contentObj.' 0 R >>';
            $objects[$contentObj] = "<< /Length {$len} >>\nstream\n{$stream}\nendstream";
        }

        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $n => $body) {
            $offsets[$n] = strlen($pdf);
            $pdf .= $n." 0 obj\n".$body."\nendobj\n";
        }

        $xrefPos = strlen($pdf);
        $size = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 {$size}\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i < $size; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }
        $pdf .= "trailer\n<< /Size {$size} /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefPos}\n%%EOF\n";

        return $pdf;
    }

    public static function escape(string $text): string
    {
        // WinAnsi-ish: strip unsupported chars
        $text = str_replace(['—', '–', '→', '≤', '≥'], ['-', '-', '->', '<=', '>='], $text);
        $text = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text) ?: $text;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    /**
     * @param  list<string>  $lines
     */
    public static function pageFromLines(array $lines, float $startY = 800, float $fontSize = 11, float $leading = 16): string
    {
        $y = $startY;
        $out = "BT\n/F1 {$fontSize} Tf\n14 TL\n50 {$y} Td\n";
        $first = true;
        foreach ($lines as $line) {
            $esc = self::escape($line);
            if ($first) {
                $out .= "({$esc}) Tj\n";
                $first = false;
            } else {
                $out .= "0 -{$leading} Td\n({$esc}) Tj\n";
            }
        }
        $out .= "ET\n";

        return $out;
    }
}

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$generatedAt = now()->timezone('Europe/Amsterdam')->format('d-m-Y H:i');

$page1 = SimplePdf::pageFromLines([
    'NEXA Network - testdemo',
    '',
    'Gegenereerd: '.$generatedAt,
    'Opnieuw seeden: php artisan taxi:seed-network-demo',
    '',
    'WACHTWOORD (alle accounts)',
    'NetworkTest2026!',
    '',
    'LOGINS',
    'Owner-chauffeur (hand-over):  network.owner.driver@nexa.test',
    'Partner dichtbij (max 10 km): network.partner.near@nexa.test',
    'Partner verweg (> 10 km):     network.partner.far@nexa.test',
    'Admin Owner:                  network.owner.admin@nexa.test',
    'Admin Partner:                network.partner.admin@nexa.test',
    '',
    'URLS',
    'Chauffeur-app:  http://localhost:8085/taxi/chauffeur',
    'Admin:          http://localhost:8085/admin',
    'Dispatch/Network: http://localhost:8085/admin/taxi/dispatch-instellingen',
    '',
    'NETWORK-INSTELLINGEN',
    'Owner <-> Partner gekoppeld (accepted)',
    'Modus: auto | Max. radius: 10 km | Fallback: 60 seconden',
    'GPS van chauffeurs staat t.o.v. Amsterdam Centraal',
], 800, 11, 15);

$page2 = SimplePdf::pageFromLines([
    'TESTRITTEN (owner)',
    '1. Hand-over naar network - accepted - gebruik Naar network',
    '2. Auto-fallback - offered - na fallback + expire naar partners',
    '3. Extra inbox-rit - offered - extra aanbod owner-chauffeur',
    'Rit-IDs kunnen wijzigen na opnieuw seeden.',
    '',
    'TESTPLAN',
    '1. Login als network.owner.driver@nexa.test in de chauffeur-app,',
    '   zet online.',
    '2. Open de geaccepteerde rit, kies Naar network.',
    '   Partner dichtbij moet een offer krijgen.',
    '3. Login als network.partner.near@nexa.test en claim de rit.',
    '4. network.partner.far@nexa.test blijft buiten 10 km: geen offer.',
    '5. Admin owner: Chauffeur dispatch -> NEXA Network',
    '   (radius/fallback controleren).',
    '6. Auto-fallback: laat open offer verlopen (>60s);',
    '   escalate biedt partners binnen radius aan.',
], 800, 11, 16);

$pdf = new SimplePdf;
$pdf->addPage($page1);
$pdf->addPage($page2);
$bytes = $pdf->output();

$dir = public_path('tmp');
if (! is_dir($dir)) {
    mkdir($dir, 0755, true);
}
$path = $dir.'/nexa-network-testdemo.pdf';
file_put_contents($path, $bytes);

echo "path={$path}\n";
echo 'bytes='.strlen($bytes)."\n";
echo 'has_password='.(str_contains($bytes, 'NetworkTest2026!') ? 'yes' : 'no')."\n";
echo 'url='.rtrim((string) config('app.url'), '/').'/tmp/nexa-network-testdemo.pdf'."\n";
