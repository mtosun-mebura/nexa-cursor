<?php

namespace App\Services;

class InformatieaanvraagEmailHtmlNormalizer
{
    public const FIELD_DIVIDER_COLOR = '#d1d5db';

    public function normalize(string $html): string
    {
        if ($html === '') {
            return $html;
        }

        $html = $this->ensureColorSchemeMeta($html);
        $html = $this->normalizeCardTable($html);
        $html = $this->wrapLooseInfoRequestFieldRowsInCard($html);
        $html = $this->tagUntaggedFieldTables($html);
        $html = $this->ensureInfoRequestFieldsTableLayout($html);
        $html = $this->normalizeFieldLabelWidths($html);
        $html = $this->normalizeFieldValueWidths($html);
        $html = $this->normalizeFieldLabelAlignment($html);
        $html = $this->normalizeFieldsTableSpacing($html);
        $html = $this->normalizeIntroToFieldsSpacing($html);
        $html = $this->removeFieldCellBorders($html);
        $html = $this->insertFieldDividerRows($html);
        $html = $this->normalizeFieldRowPadding($html);
        $html = $this->ensurePresentationCellAttributes($html);

        return $html;
    }

    public static function fieldDividerRowHtml(): string
    {
        $color = self::FIELD_DIVIDER_COLOR;

        return '<tr class="info-request-field-divider"><td colspan="2" height="1" bgcolor="'.$color.'" style="padding: 0; margin: 0; line-height: 1px; font-size: 1px; height: 1px; background-color: '.$color.'; border: none; mso-line-height-rule: exactly;">&#8203;</td></tr>';
    }

    protected function ensureColorSchemeMeta(string $html): string
    {
        if (stripos($html, 'color-scheme') !== false) {
            return $html;
        }

        if (preg_match('/<head(\b[^>]*)>/i', $html)) {
            return preg_replace(
                '/<head(\b[^>]*)>/i',
                '<head$1>'."\n".'    <meta name="color-scheme" content="light">'."\n".'    <meta name="supported-color-schemes" content="light">',
                $html,
                1
            ) ?? $html;
        }

        return $html;
    }

    protected function normalizeCardTable(string $html): string
    {
        $html = preg_replace_callback(
            '/<table(\s+role="presentation")(\s+style=")([^"]*width:\s*600px[^"]*)(")/i',
            static function (array $matches): string {
                if (stripos($matches[0], 'info-request-email-card') !== false) {
                    return $matches[0];
                }

                $style = preg_replace('/width:\s*600px/i', 'width: 100%; max-width: 600px', $matches[3]) ?? $matches[3];
                $style = preg_replace('/border-collapse\s*:\s*collapse/i', 'border-collapse: separate', $style) ?? $style;
                if (stripos($style, 'border-collapse') === false) {
                    $style .= '; border-collapse: separate';
                }
                if (stripos($style, 'border-spacing') === false) {
                    $style .= '; border-spacing: 0';
                }
                if (stripos($style, 'border-radius') === false) {
                    $style .= '; border-radius: 8px';
                }
                if (stripos($style, 'overflow') === false) {
                    $style .= '; overflow: hidden';
                }
                $style = preg_replace('/;\s*table-layout\s*:\s*fixed\s*/i', '; ', $style) ?? $style;

                return '<table'.$matches[1].' class="info-request-email-card" width="100%"'.$matches[2].$style.$matches[4];
            },
            $html,
            1
        ) ?? $html;

        return preg_replace(
            '/(<table[^>]*\binfo-request-email-card\b[^>]*>)\s*<colgroup>.*?<\/colgroup>/is',
            '$1',
            $html
        ) ?? $html;
    }

    protected function normalizeFieldLabelWidths(string $html): string
    {
        $html = preg_replace(
            '/(class="info-request-field-label"[^>]*style="[^"]*)width:\s*1%/i',
            '$1width: 175px',
            $html
        ) ?? $html;
        $html = preg_replace(
            '/(class="info-request-field-label"[^>]*style="[^"]*)width:\s*130px/i',
            '$1width: 175px',
            $html
        ) ?? $html;
        $html = preg_replace(
            '/(<table[^>]*\binfo-request-fields\b[^>]*>[\s\S]*?<col[^>]*style="[^"]*)width:\s*1%/i',
            '$1width: 175px',
            $html
        ) ?? $html;

        return preg_replace(
            '/(<table[^>]*\binfo-request-fields\b[^>]*>[\s\S]*?<col[^>]*style="[^"]*)width:\s*130px/i',
            '$1width: 175px',
            $html
        ) ?? $html;
    }

    protected function normalizeFieldValueWidths(string $html): string
    {
        $html = preg_replace(
            '/(<table[^>]*\binfo-request-fields\b[^>]*>\s*<colgroup>\s*<col[^>]*>\s*<col[^>]*style="[^"]*)width:\s*1%\s*;?/i',
            '$1',
            $html
        ) ?? $html;

        return preg_replace(
            '/(class="info-request-field-value(?:--multiline)?"[^>]*style="[^"]*)width:\s*1%\s*;?\s*/i',
            '$1',
            $html
        ) ?? $html;
    }

    protected function ensurePresentationCellAttributes(string $html): string
    {
        $cells = [
            'info-request-email-header' => '#2563eb',
            'info-request-email-body' => '#ffffff',
            'info-request-email-footer' => '#f9fafb',
        ];

        foreach ($cells as $className => $bgColor) {
            $html = preg_replace_callback(
                '/<td([^>]*\b'.preg_quote($className, '/').'\b[^>]*)>/i',
                static function (array $matches) use ($bgColor): string {
                    $attrs = $matches[1];
                    if (! preg_match('/\bwidth="/i', $attrs)) {
                        $attrs .= ' width="100%"';
                    }
                    if (! preg_match('/\bbgcolor="/i', $attrs)) {
                        $attrs .= ' bgcolor="'.$bgColor.'"';
                    }

                    return '<td'.$attrs.'>';
                },
                $html
            ) ?? $html;
        }

        $html = preg_replace_callback(
            '/<table([^>]*\binfo-request-email-card\b[^>]*)>/i',
            static function (array $matches): string {
                $attrs = $matches[1];
                if (! preg_match('/\bwidth="/i', $attrs)) {
                    $attrs .= ' width="100%"';
                }
                if (preg_match('/\sstyle="([^"]*)"/i', $attrs, $styleMatch)) {
                    $style = self::normalizeEmailCardStyle($styleMatch[1]);
                    $attrs = preg_replace('/\sstyle="[^"]*"/i', ' style="'.$style.'"', $attrs) ?? $attrs;
                } else {
                    $attrs .= ' style="'.self::normalizeEmailCardStyle('').'"';
                }

                return '<table'.$attrs.'>';
            },
            $html
        ) ?? $html;

        return preg_replace_callback(
            '/<table([^>]*\binfo-request-fields\b[^>]*)>/i',
            static function (array $matches): string {
                $attrs = $matches[1];
                if (! preg_match('/\bwidth="/i', $attrs)) {
                    $attrs .= ' width="100%"';
                }

                return '<table'.$attrs.'>';
            },
            $html
        ) ?? $html;
    }

    protected function wrapLooseInfoRequestFieldRowsInCard(string $html): string
    {
        if (! str_contains($html, 'info-request-field-row')
            && ! str_contains($html, 'info-request-field-divider')
            && ! str_contains($html, 'info-request-email-card')
            && stripos($html, 'box-shadow') === false) {
            return $html;
        }

        $previous = libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML(
            '<?xml encoding="utf-8" ?>'.$html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($doc);
        $cards = $xpath->query("//table[contains(concat(' ', normalize-space(@class), ' '), ' info-request-email-card ')]");
        if ($cards->length === 0) {
            $cards = $xpath->query("//table[contains(@style, 'box-shadow')]");
        }

        foreach ($cards as $card) {
            if ($card instanceof \DOMElement) {
                $this->relocateLooseInfoRequestFieldRows($doc, $card);
            }
        }

        $result = $doc->saveHTML();

        return preg_replace('/^<\?xml encoding="utf-8" \?>/', '', $result ?? $html) ?? $html;
    }

    protected function tagUntaggedFieldTables(string $html): string
    {
        if (! str_contains($html, 'info-request-email-card') && stripos($html, 'box-shadow') === false) {
            return $html;
        }

        $previous = libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML(
            '<?xml encoding="utf-8" ?>'.$html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($doc);
        $cards = $xpath->query("//table[contains(concat(' ', normalize-space(@class), ' '), ' info-request-email-card ') or contains(@style, 'box-shadow')]");
        foreach ($cards as $card) {
            if (! $card instanceof \DOMElement) {
                continue;
            }
            $tables = $card->getElementsByTagName('table');
            foreach ($tables as $table) {
                if (! $table instanceof \DOMElement || $table === $card) {
                    continue;
                }
                if (str_contains($table->getAttribute('class'), 'info-request-fields')) {
                    continue;
                }
                if ($this->tableContainsFieldRows($table)) {
                    $class = trim($table->getAttribute('class').' info-request-fields');
                    $table->setAttribute('class', $class);
                    if (! $table->hasAttribute('width')) {
                        $table->setAttribute('width', '100%');
                    }
                }
            }
        }

        $result = $doc->saveHTML();

        return preg_replace('/^<\?xml encoding="utf-8" \?>/', '', $result ?? $html) ?? $html;
    }

    protected function tableContainsFieldRows(\DOMElement $table): bool
    {
        foreach ($table->getElementsByTagName('tr') as $row) {
            if (! $row instanceof \DOMElement) {
                continue;
            }
            $class = $row->getAttribute('class');
            if (str_contains($class, 'info-request-field-row') || str_contains($class, 'info-request-field-divider')) {
                return true;
            }
            if ($this->isLikelyFieldDataRow($row)) {
                return true;
            }
        }

        return false;
    }

    protected function relocateLooseInfoRequestFieldRows(\DOMDocument $doc, \DOMElement $card): void
    {
        $container = $this->tableRowContainer($card);

        $looseRows = $this->collectLooseFieldRelatedRows($container);
        if ($looseRows === []) {
            return;
        }

        $fieldsTable = $this->findExistingFieldsTable($card);
        if ($fieldsTable === null) {
            $fieldsTable = $this->createInfoRequestFieldsTable($doc);
            $bodyCell = $this->findEmailBodyCell($card);
            if ($bodyCell !== null) {
                $bodyCell->appendChild($fieldsTable);
            } else {
                $wrapperTr = $doc->createElement('tr');
                $wrapperTd = $doc->createElement('td');
                $wrapperTd->setAttribute('class', 'info-request-email-body');
                $wrapperTd->setAttribute('width', '100%');
                $wrapperTd->setAttribute('bgcolor', '#ffffff');
                $wrapperTd->setAttribute('style', 'padding: 30px; background-color: #ffffff; width: 100%;');
                $wrapperTd->appendChild($fieldsTable);
                $wrapperTr->appendChild($wrapperTd);
                $container->insertBefore($wrapperTr, $looseRows[0]);
            }
        }

        $fieldsContainer = $this->tableRowContainer($fieldsTable);
        foreach ($looseRows as $row) {
            if ($row->parentNode !== null) {
                $row->parentNode->removeChild($row);
            }
            $fieldsContainer->appendChild($row);
        }
    }

    protected function tableRowContainer(\DOMElement $table): \DOMElement
    {
        foreach ($table->childNodes as $child) {
            if ($child instanceof \DOMElement && strtolower($child->nodeName) === 'tbody') {
                return $child;
            }
        }

        return $table;
    }

    /**
     * @return list<\DOMElement>
     */
    protected function collectLooseFieldRelatedRows(\DOMElement $container): array
    {
        $rows = [];
        foreach (iterator_to_array($container->childNodes) as $node) {
            if (! $node instanceof \DOMElement || strtolower($node->nodeName) !== 'tr') {
                continue;
            }
            $class = $node->getAttribute('class');
            if (str_contains($class, 'info-request-field-row')
                || str_contains($class, 'info-request-field-divider')
                || $this->isLikelyFieldDataRow($node)) {
                $rows[] = $node;
            }
        }

        return $rows;
    }

    protected function findExistingFieldsTable(\DOMElement $card): ?\DOMElement
    {
        foreach ($card->getElementsByTagName('table') as $table) {
            if (! $table instanceof \DOMElement) {
                continue;
            }
            if (str_contains($table->getAttribute('class'), 'info-request-fields')) {
                return $table;
            }
        }

        return null;
    }

    protected function findEmailBodyCell(\DOMElement $card): ?\DOMElement
    {
        $xpath = new \DOMXPath($card->ownerDocument ?? new \DOMDocument());
        $nodes = $xpath->query(".//td[contains(concat(' ', normalize-space(@class), ' '), ' info-request-email-body ')]", $card);
        if ($nodes !== false && $nodes->length > 0 && $nodes->item(0) instanceof \DOMElement) {
            return $nodes->item(0);
        }

        return null;
    }

    protected function createInfoRequestFieldsTable(\DOMDocument $doc): \DOMElement
    {
        $table = $doc->createElement('table');
        $table->setAttribute('role', 'presentation');
        $table->setAttribute('class', 'info-request-fields');
        $table->setAttribute('width', '100%');
        $table->setAttribute('style', 'width: 100%; border-collapse: collapse; margin: 0; font-size: 15px; color: #333333; background-color: #ffffff; text-align: left; table-layout: fixed;');

        $colgroup = $doc->createElement('colgroup');
        $labelCol = $doc->createElement('col');
        $labelCol->setAttribute('style', 'width: 175px;');
        $colgroup->appendChild($labelCol);
        $colgroup->appendChild($doc->createElement('col'));
        $table->appendChild($colgroup);

        return $table;
    }

    protected function ensureInfoRequestFieldsTableLayout(string $html): string
    {
        $html = preg_replace_callback(
            '/<table([^>]*\binfo-request-fields\b[^>]*)>/i',
            static function (array $matches): string {
                $attrs = $matches[1];
                if (stripos($attrs, 'table-layout') === false) {
                    if (preg_match('/\sstyle="/i', $attrs)) {
                        $attrs = preg_replace('/\sstyle="/i', ' style="table-layout: fixed; ', $attrs, 1) ?? $attrs;
                    } else {
                        $attrs .= ' style="table-layout: fixed;"';
                    }
                }

                return '<table'.$attrs.'>';
            },
            $html
        ) ?? $html;

        return preg_replace(
            '/(<table[^>]*\binfo-request-fields\b[^>]*>)(\s*(?!<colgroup))/i',
            '$1<colgroup><col style="width: 175px;"><col></colgroup>',
            $html
        ) ?? $html;
    }

    protected function normalizeFieldLabelAlignment(string $html): string
    {
        $html = preg_replace(
            '/(class="info-request-field-label"[^>]*style="[^"]*)text-align:\s*left/i',
            '$1text-align: right',
            $html
        ) ?? $html;

        return preg_replace_callback(
            '/(<td[^>]*class="info-request-field-label"[^>]*style=")([^"]*)(")/i',
            static function (array $matches): string {
                if (stripos($matches[2], 'text-align') !== false) {
                    return $matches[0];
                }

                return $matches[1].rtrim($matches[2], '; ').'; text-align: right'.$matches[3];
            },
            $html
        ) ?? $html;
    }

    protected function normalizeFieldsTableSpacing(string $html): string
    {
        return preg_replace_callback(
            '/(<table[^>]*\binfo-request-fields\b[^>]*style=")([^"]*)(")/i',
            static function (array $matches): string {
                $style = preg_replace('/margin:\s*20px\s+0/i', 'margin: 0', $matches[2]) ?? $matches[2];
                $style = preg_replace('/margin:\s*8px\s+0\s+0/i', 'margin: 0', $style) ?? $style;
                $style = preg_replace('/margin-top:\s*20px/i', 'margin-top: 0', $style) ?? $style;
                $style = preg_replace('/margin-top:\s*8px/i', 'margin-top: 0', $style) ?? $style;
                if (! preg_match('/margin(?:-top)?:/i', $style)) {
                    $style .= '; margin: 0';
                }

                return $matches[1].$style.$matches[3];
            },
            $html
        ) ?? $html;
    }

    protected function normalizeIntroToFieldsSpacing(string $html): string
    {
        $html = preg_replace(
            '/(<td[^>]*class="info-request-email-body"[^>]*>[\s\S]*?<p[^>]*style="[^"]*)margin:\s*0\s+0\s+(?:15|6)px\s+0/i',
            '$1margin: 0',
            $html,
            1
        ) ?? $html;

        return preg_replace(
            '/(<td[^>]*class="info-request-email-body"[^>]*>[\s\S]*?<p[^>]*style="[^"]*)margin:\s*0\s+0\s+20px\s+0/i',
            '$1margin: 0',
            $html,
            1
        ) ?? $html;
    }

    protected function removeFieldCellBorders(string $html): string
    {
        return preg_replace_callback(
            '/(<td[^>]*class="info-request-field-(?:label|value)[^"]*"[^>]*style=")([^"]*)(")/i',
            static function (array $matches): string {
                $style = preg_replace('/\s*border-bottom:\s*1px\s+solid\s+#[0-9a-f]{3,6}\s*;?/i', '', $matches[2]) ?? $matches[2];
                $style = preg_replace('/;\s*;/', ';', $style) ?? $style;

                return $matches[1].trim($style, '; ').$matches[3];
            },
            $html
        ) ?? $html;
    }

    protected function insertFieldDividerRows(string $html): string
    {
        if (! str_contains($html, 'info-request-field-row') && ! str_contains($html, 'info-request-fields')) {
            return $html;
        }

        $previous = libxml_use_internal_errors(true);
        $doc = new \DOMDocument();
        $doc->loadHTML(
            '<?xml encoding="utf-8" ?>'.$html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new \DOMXPath($doc);
        $fieldTables = $xpath->query("//table[contains(concat(' ', normalize-space(@class), ' '), ' info-request-fields ')]");

        foreach ($fieldTables as $table) {
            if (! $table instanceof \DOMElement) {
                continue;
            }
            $this->insertDividersInFieldsTable($doc, $table);
        }

        $result = $doc->saveHTML();

        return preg_replace('/^<\?xml encoding="utf-8" \?>/', '', $result ?? $html) ?? $html;
    }

    protected function insertDividersInFieldsTable(\DOMDocument $doc, \DOMElement $table): void
    {
        $container = $table;
        foreach ($table->childNodes as $child) {
            if ($child instanceof \DOMElement && strtolower($child->nodeName) === 'tbody') {
                $container = $child;
                break;
            }
        }

        $fieldRows = [];
        foreach (iterator_to_array($container->childNodes) as $node) {
            if (! $node instanceof \DOMElement || strtolower($node->nodeName) !== 'tr') {
                continue;
            }
            $class = $node->getAttribute('class');
            if (str_contains($class, 'info-request-field-divider')) {
                continue;
            }
            if (str_contains($class, 'info-request-field-row') || $this->isLikelyFieldDataRow($node)) {
                $fieldRows[] = $node;
            }
        }

        if (count($fieldRows) < 2) {
            return;
        }

        for ($i = count($fieldRows) - 2; $i >= 0; $i--) {
            $row = $fieldRows[$i];
            $next = $fieldRows[$i + 1] ?? null;
            if ($next === null) {
                continue;
            }
            $between = $row->nextSibling;
            if ($between instanceof \DOMElement
                && strtolower($between->nodeName) === 'tr'
                && str_contains($between->getAttribute('class'), 'info-request-field-divider')) {
                continue;
            }

            $divider = $this->createFieldDividerRowElement($doc);
            if ($divider !== null) {
                $container->insertBefore($divider, $next);
            }
        }
    }

    protected function createFieldDividerRowElement(\DOMDocument $doc): ?\DOMElement
    {
        $color = self::FIELD_DIVIDER_COLOR;
        $tr = $doc->createElement('tr');
        $tr->setAttribute('class', 'info-request-field-divider');
        $td = $doc->createElement('td');
        $td->setAttribute('colspan', '2');
        $td->setAttribute('height', '1');
        $td->setAttribute('bgcolor', $color);
        $td->setAttribute('style', 'padding: 0; margin: 0; line-height: 1px; font-size: 1px; height: 1px; background-color: '.$color.'; border: none; mso-line-height-rule: exactly;');
        $td->appendChild($doc->createTextNode("\xE2\x80\x8B"));
        $tr->appendChild($td);

        return $tr;
    }

    protected function isLikelyFieldDataRow(\DOMElement $row): bool
    {
        $cells = [];
        foreach ($row->childNodes as $child) {
            if ($child instanceof \DOMElement && strtolower($child->nodeName) === 'td') {
                $cells[] = $child;
            }
        }

        return count($cells) === 2;
    }

    protected function normalizeFieldRowPadding(string $html): string
    {
        return preg_replace_callback(
            '/(<td[^>]*class="info-request-field-(?:label|value)[^"]*"[^>]*style=")([^"]*)(")/i',
            static function (array $matches): string {
                $style = preg_replace('/padding:\s*8px\s+10px\s+8px\s+14px/i', 'padding: 6px 10px 6px 14px', $matches[2]) ?? $matches[2];
                $style = preg_replace('/padding:\s*8px\s+10px\s+8px\s+10px/i', 'padding: 6px 10px 6px 10px', $style) ?? $style;

                return $matches[1].$style.$matches[3];
            },
            $html
        ) ?? $html;
    }

    protected static function normalizeEmailCardStyle(string $style): string
    {
        $style = preg_replace('/;\s*table-layout\s*:\s*fixed\s*/i', '; ', $style) ?? $style;
        $style = preg_replace('/^\s*table-layout\s*:\s*fixed\s*;?\s*/i', '', $style) ?? $style;
        $style = preg_replace('/border-collapse\s*:\s*collapse/i', 'border-collapse: separate', $style) ?? $style;
        if (stripos($style, 'border-collapse') === false) {
            $style .= ($style !== '' ? '; ' : '').'border-collapse: separate';
        }
        if (stripos($style, 'border-spacing') === false) {
            $style .= '; border-spacing: 0';
        }
        if (stripos($style, 'border-radius') === false) {
            $style .= '; border-radius: 8px';
        }
        if (stripos($style, 'overflow') === false) {
            $style .= '; overflow: hidden';
        }
        if (stripos($style, 'background-color') === false) {
            $style .= '; background-color: #ffffff';
        }
        $style = preg_replace('/border:\s*1px\s+solid\s+#e5e7eb/i', 'border: 1px solid '.self::FIELD_DIVIDER_COLOR, $style) ?? $style;
        if (! preg_match('/\bborder:\s*1px\s+solid/i', $style)) {
            $style .= '; border: 1px solid '.self::FIELD_DIVIDER_COLOR;
        }

        return ltrim($style, '; ');
    }
}
