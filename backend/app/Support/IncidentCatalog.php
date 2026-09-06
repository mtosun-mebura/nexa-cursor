<?php

namespace App\Support;

final class IncidentCatalog
{
    public const KIND_STORING = 'storing';
    public const KIND_VRAAG = 'vraag';
    public const KIND_WENS = 'wens';
    public const KIND_OVERIG = 'overig';

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const NOTIFICATION_TYPE = 'incident';
    public const NOTIFICATION_CATEGORY = 'incident';

    /**
     * @return list<string>
     */
    public static function kinds(): array
    {
        return [self::KIND_STORING, self::KIND_VRAAG, self::KIND_WENS, self::KIND_OVERIG];
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_RESOLVED, self::STATUS_CLOSED];
    }

    /**
     * @return list<string>
     */
    public static function priorities(): array
    {
        return [self::PRIORITY_LOW, self::PRIORITY_NORMAL, self::PRIORITY_HIGH, self::PRIORITY_URGENT];
    }

    /**
     * @return list<array{value: string, label: string, hint: string, icon: string}>
     */
    public static function kindOptions(): array
    {
        return [
            [
                'value' => self::KIND_STORING,
                'label' => 'Storing',
                'hint' => 'Iets werkt niet of geeft een fout',
                'icon' => 'ki-information-4',
            ],
            [
                'value' => self::KIND_VRAAG,
                'label' => 'Vraag',
                'hint' => 'Ik snap iets niet of heb hulp nodig',
                'icon' => 'ki-message-question',
            ],
            [
                'value' => self::KIND_WENS,
                'label' => 'Wens',
                'hint' => 'Ik wil iets aanpassen of toevoegen',
                'icon' => 'ki-heart',
            ],
            [
                'value' => self::KIND_OVERIG,
                'label' => 'Overig',
                'hint' => 'Iets anders dat je wilt doorgeven',
                'icon' => 'ki-notepad',
            ],
        ];
    }

    /**
     * @return list<array{value: string, label: string, tone: string}>
     */
    public static function statusOptions(): array
    {
        return [
            ['value' => self::STATUS_OPEN, 'label' => 'Nieuw', 'tone' => 'info'],
            ['value' => self::STATUS_IN_PROGRESS, 'label' => 'In behandeling', 'tone' => 'warning'],
            ['value' => self::STATUS_RESOLVED, 'label' => 'Afgehandeld', 'tone' => 'success'],
            ['value' => self::STATUS_CLOSED, 'label' => 'Gesloten', 'tone' => 'secondary'],
        ];
    }

    /**
     * @return list<array{value: string, label: string, hint: string, tone: string}>
     */
    public static function priorityOptions(): array
    {
        return [
            ['value' => self::PRIORITY_LOW, 'label' => 'Laag', 'hint' => 'Kan later', 'tone' => 'secondary'],
            ['value' => self::PRIORITY_NORMAL, 'label' => 'Normaal', 'hint' => 'Gewoon oppakken', 'tone' => 'info'],
            ['value' => self::PRIORITY_HIGH, 'label' => 'Hoog', 'hint' => 'Houdt ons werk tegen', 'tone' => 'warning'],
            ['value' => self::PRIORITY_URGENT, 'label' => 'Urgent', 'hint' => 'We kunnen nu niet verder', 'tone' => 'danger'],
        ];
    }

    public static function kindLabel(string $kind): string
    {
        foreach (self::kindOptions() as $option) {
            if ($option['value'] === $kind) {
                return $option['label'];
            }
        }

        return $kind;
    }

    public static function statusLabel(string $status): string
    {
        foreach (self::statusOptions() as $option) {
            if ($option['value'] === $status) {
                return $option['label'];
            }
        }

        return $status;
    }

    public static function priorityLabel(string $priority): string
    {
        foreach (self::priorityOptions() as $option) {
            if ($option['value'] === $priority) {
                return $option['label'];
            }
        }

        return $priority;
    }

    public static function isHandled(string $status): bool
    {
        return in_array($status, [self::STATUS_RESOLVED, self::STATUS_CLOSED], true);
    }
}
