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

    /** Platform setting: e-mailadres voor nieuwe incidenten van klanten. */
    public const SETTING_NOTIFICATION_EMAIL = 'incident_notification_email';

    public const DEFAULT_NOTIFICATION_EMAIL = 'support@nexasuite.nl';

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

    /**
     * Nederlandse UI-teksten van resources/js/incidents/IncidentApp.vue (alle t('…')-literals).
     * Wordt als vertaalmap meegegeven aan de Vue-app; een test bewaakt dat lijst en bestand gelijk blijven.
     *
     * @return list<string>
     */
    public static function uiStrings(): array
    {
        return [
            'Kon incidenten niet laden.',
            'Er ging iets mis.',
            'Toon :from tot :to van :total incidenten',
            'Archiveren is niet gelukt.',
            ':count incident naar archief.',
            ':count incidenten naar archief.',
            ':count incident teruggezet.',
            ':count incidenten teruggezet.',
            'Soort',
            'Uitleg',
            'Versturen',
            'Een afbeelding is groter dan 5MB. Kies een kleinere screenshot.',
            'plak-:time.png',
            'Schermafbeelding maken wordt niet ondersteund in deze browser. Plak of sleep een screenshot.',
            'Kon geen schermafbeelding maken.',
            'schermafbeelding-:time.png',
            'Schermafbeelding is geannuleerd of mislukt. Je kunt ook plakken of slepen.',
            'Beschrijf kort wat er aan de hand is (minimaal 4 tekens).',
            'Vertel iets meer, zodat we je snel kunnen helpen (minimaal 10 tekens).',
            'Versturen is niet gelukt.',
            'Incident niet gevonden.',
            'Kon incident niet openen.',
            'Opgeslagen',
            'Toelichting opslaan is niet gelukt.',
            'Toelichting verwijderen is niet gelukt.',
            'Commentaar opslaan is niet gelukt.',
            'Commentaar verwijderen is niet gelukt.',
            'Opslaan is niet gelukt.',
            'Status opslaan is niet gelukt.',
            'Commentaar verwijderen?',
            'Toelichting verwijderen?',
            'Dit commentaar wordt definitief verwijderd.',
            'De toelichting voor de klant wordt definitief verwijderd.',
            'Incidenten',
            'Hulp nodig?',
            'Bekijk meldingen, dien zelf een ticket in en werk de status bij.',
            'Meld een storing, vraag of wens. Voeg een screenshot toe, dan kunnen we je sneller helpen.',
            'Nieuw incident',
            'Nieuw',
            'In behandeling',
            'Afgehandeld',
            'Totaal',
            'Archief',
            'Alle meldingen',
            'Jouw meldingen',
            'Lijstweergave',
            'Meldingen',
            'Zoek op titel of nummer',
            'Zoekveld wissen',
            'Alle statussen',
            'Alle prioriteiten',
            'Alles',
            'Archiveren…',
            'Naar archief',
            'Terugzetten…',
            'Terugzetten',
            ':count geselecteerd',
            'Incidenten laden…',
            'Het archief is leeg.',
            'Nog geen incidenten. Klik op Nieuw incident om iets te melden.',
            'Er zijn nog geen incidenten.',
            'Selecteer :reference',
            ':count screenshot',
            ':count screenshots',
            'Toon',
            'Toon aantal per pagina',
            'per pagina',
            'Paginering',
            'Vorige',
            'Volgende',
            'Sluiten',
            'Incident melden',
            'In een paar stappen klaar. Screenshot plakken mag gewoon met Ctrl+V.',
            'Stappen',
            'Ga naar :step',
            ':step (voltooid)',
            'Verstuurd',
            ':reference staat bij NEXA Support. Je krijgt een melding zodra het is afgehandeld.',
            'Bekijk melding',
            'Wat is er aan de hand?',
            'Korte titel',
            'Bijv. Planning opent niet op mobiel',
            '(optioneel)',
            'Omschrijving',
            'Wat gebeurde er, wat verwachtte je, en waar zag je het?',
            'Screenshots',
            'Sleep, plak (Ctrl+V / Cmd+V) of maak een schermafbeelding. Max. 5, JPG/PNG/WebP tot 5MB.',
            'Klik of sleep afbeelding',
            'JPG, PNG, WebP (max. 5MB)',
            'Bezig…',
            'Schermafbeelding maken',
            'Vergroot :name',
            'Verwijderen',
            'Terug',
            'Hoe dringend is het?',
            'Geen titel',
            ':count screenshot bijgevoegd',
            ':count screenshots bijgevoegd',
            'Bedrijf (optioneel)',
            'Intern / geen specifiek bedrijf',
            'Versturen…',
            'Incident versturen',
            'Incident',
            'Status',
            'Laden…',
            'Bedrijf',
            'Gemeld door',
            'Gemeld op',
            'Vergroot screenshot :number van :total',
            'Geen toelichting.',
            'We zijn ermee bezig. Je krijgt een melding zodra dit incident is afgehandeld.',
            'Afhandeling',
            'Toelichting voor de klant',
            'Wat is er gedaan of wat moet de klant weten?',
            'Toelichting bewerken',
            'Toelichting verwijderen',
            'Annuleren',
            'Opslaan…',
            'Opslaan',
            'Commentaar',
            'Commentaar verwijderen',
            'Interne notitie voor jezelf of andere ontwikkelaars',
            'Vorige afbeelding',
            'Volgende afbeelding',
        ];
    }

    public static function isHandled(string $status): bool
    {
        return in_array($status, [self::STATUS_RESOLVED, self::STATUS_CLOSED], true);
    }
}
