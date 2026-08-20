<?php

namespace App\Services\TenantSync;

/**
 * Gestructureerd overzicht van een tenant-sync-run (tabellen + aantallen + fouten).
 */
final class TenantSyncReportBuilder
{
    private int $remoteCompanyId = 0;

    private int $totalInserted = 0;

    private int $totalUpdated = 0;

    private int $totalSkipped = 0;

    /** @var array<string, array{title: string, rows: list<array<string, mixed>>}> */
    private array $sections = [];

    /** @var list<array{section: string, message: string}> */
    private array $errors = [];

    /** @var list<string> */
    private array $notes = [];

    private ?string $customSummary = null;

    /** @var null|callable(array<string, mixed>): void */
    private $onProgress = null;

    private int $progressDone = 0;

    private int $progressTotal = 0;

    /**
     * @param  callable(array<string, mixed>): void|null  $callback
     */
    public function onProgress(?callable $callback): void
    {
        $this->onProgress = $callback;
    }

    /**
     * Verwacht aantal voortgangseenheden (stappen + tabelrijen) voor de procentbalk.
     */
    public function setProgressTotal(int $total): void
    {
        $this->progressTotal = max(0, $total);
        $this->emitProgress(false);
    }

    public function addStep(string $label, string $status = 'done'): void
    {
        $label = trim($label);
        if ($label === '') {
            return;
        }

        $this->emit('step', [
            'label' => $label,
            'status' => $status,
        ]);
        $this->bumpProgress();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function emit(string $type, array $payload = []): void
    {
        if ($this->onProgress === null) {
            return;
        }

        ($this->onProgress)(array_merge(['type' => $type], $payload));
    }

    private function bumpProgress(int $by = 1): void
    {
        $this->progressDone += max(0, $by);
        $this->emitProgress(false);
    }

    private function emitProgress(bool $complete): void
    {
        if ($complete) {
            $percent = 100;
            $done = max($this->progressDone, $this->progressTotal);
            $total = max($this->progressTotal, $done);
        } elseif ($this->progressTotal > 0) {
            $done = $this->progressDone;
            $total = $this->progressTotal;
            // Houd 100% voor de complete-event; tijdens de run max 99%.
            $percent = min(99, (int) floor(($done / $total) * 100));
        } else {
            $done = $this->progressDone;
            $total = 0;
            $percent = min(90, 5 + ($done * 3));
        }

        $this->emit('progress', [
            'done' => $done,
            'total' => $total,
            'percent' => $percent,
        ]);
    }

    public function setSummary(int $remoteCompanyId, int $inserted, int $updated, int $skipped, ?string $summaryOverride = null): void
    {
        $this->remoteCompanyId = $remoteCompanyId;
        $this->totalInserted = $inserted;
        $this->totalUpdated = $updated;
        $this->totalSkipped = $skipped;
        $this->customSummary = $summaryOverride;

        $this->emitProgress(true);
        $this->emit('summary', [
            'remote_company_id' => $remoteCompanyId,
            'totals' => [
                'inserted' => $inserted,
                'updated' => $updated,
                'skipped' => $skipped,
            ],
            'summary' => $this->summaryLine(),
        ]);
    }

    public function addNote(string $note): void
    {
        $note = trim($note);
        if ($note !== '') {
            $this->notes[] = $note;
            $this->emit('note', ['note' => $note]);
        }
    }

    public function addError(string $section, string $message): void
    {
        $message = trim($message);
        if ($message === '') {
            return;
        }

        $this->errors[] = [
            'section' => $section,
            'message' => $message,
        ];

        $this->addRow($section, '— fout —', 0, 0, 0, 'error', $message);
    }

    /**
     * @param  'ok'|'error'|'skipped'|'warning'  $status
     */
    public function addRow(
        string $section,
        string $label,
        int $inserted = 0,
        int $updated = 0,
        int $skipped = 0,
        string $status = 'ok',
        ?string $error = null
    ): void {
        $sectionKey = $this->normalizeSectionKey($section);
        if (! isset($this->sections[$sectionKey])) {
            $this->sections[$sectionKey] = [
                'title' => $section,
                'rows' => [],
            ];
        }

        $row = [
            'label' => $label,
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'status' => $status,
        ];
        if ($error !== null && trim($error) !== '') {
            $row['error'] = trim($error);
        }

        $this->sections[$sectionKey]['rows'][] = $row;

        $this->emit('row', [
            'section' => $section,
            'row' => $row,
        ]);
        $this->bumpProgress();
    }

    public function summaryLine(): string
    {
        if ($this->customSummary !== null && $this->customSummary !== '') {
            return $this->customSummary;
        }

        return sprintf(
            'Tenant-sync voltooid. Doel company_id: %d. Toegevoegd: %d, bijgewerkt: %d, overgeslagen: %d.',
            $this->remoteCompanyId,
            $this->totalInserted,
            $this->totalUpdated,
            $this->totalSkipped
        );
    }

    /**
     * @return array{
     *     remote_company_id: int,
     *     totals: array{inserted: int, updated: int, skipped: int},
     *     sections: list<array{title: string, rows: list<array<string, mixed>>}>,
     *     errors: list<array{section: string, message: string}>,
     *     notes: list<string>,
     *     summary: string
     * }
     */
    public function toArray(): array
    {
        return [
            'remote_company_id' => $this->remoteCompanyId,
            'totals' => [
                'inserted' => $this->totalInserted,
                'updated' => $this->totalUpdated,
                'skipped' => $this->totalSkipped,
            ],
            'sections' => array_values($this->sections),
            'errors' => $this->errors,
            'notes' => $this->notes,
            'summary' => $this->summaryLine(),
        ];
    }

    private function normalizeSectionKey(string $section): string
    {
        return strtolower(trim($section));
    }
}
