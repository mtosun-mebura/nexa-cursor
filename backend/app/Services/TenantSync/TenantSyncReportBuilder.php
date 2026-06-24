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

    /** @var null|callable(array<string, mixed>): void */
    private $onProgress = null;

    /**
     * @param  callable(array<string, mixed>): void|null  $callback
     */
    public function onProgress(?callable $callback): void
    {
        $this->onProgress = $callback;
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

    public function setSummary(int $remoteCompanyId, int $inserted, int $updated, int $skipped): void
    {
        $this->remoteCompanyId = $remoteCompanyId;
        $this->totalInserted = $inserted;
        $this->totalUpdated = $updated;
        $this->totalSkipped = $skipped;

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
    }

    public function summaryLine(): string
    {
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
