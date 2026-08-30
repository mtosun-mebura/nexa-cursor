<?php

namespace App\Services;

use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;

class EnvService
{
    protected $envPath;

    /** @var array<string, string>|null */
    private ?array $envFileCache = null;

    /** @var array<int, array<string, string|null>> */
    private array $mailOverlayCacheByScope = [];

    /** Keys that are stored in GeneralSetting (admin settings); EnvService::get() prefers DB over .env */
    private const GENERAL_SETTING_KEYS = [
        'MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION',
        'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME',
        'GOOGLE_SEO_PROPERTY_ID', 'GOOGLE_ANALYTICS_ID', 'GOOGLE_TAG_MANAGER_ID',
        'META_DESCRIPTION', 'META_KEYWORDS', 'GOOGLE_SITE_VERIFICATION',
        'GOOGLE_SEARCH_CONSOLE_ENABLED', 'GOOGLE_SEARCH_CONSOLE_SITEMAP_PATH', 'GOOGLE_SEARCH_CONSOLE_AUTO_SITEMAP',
        'GOOGLE_MAPS_API_KEY', 'GOOGLE_MAPS_MAP_ID', 'GOOGLE_MAPS_ZOOM',
        'GOOGLE_MAPS_CENTER_LAT', 'GOOGLE_MAPS_CENTER_LNG', 'GOOGLE_MAPS_TYPE',
        'WHATSAPP_API_TOKEN', 'WHATSAPP_PHONE_NUMBER_ID', 'WHATSAPP_BUSINESS_ACCOUNT_ID',
        'WHATSAPP_API_VERSION', 'WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'WHATSAPP_DEFAULT_MESSAGE',
        'WHATSAPP_BOOKING_TEMPLATE', 'WHATSAPP_BOOKING_TEMPLATE_LANG',
        'WHATSAPP_BOOKING_CUSTOMER_TEMPLATE', 'WHATSAPP_BOOKING_CUSTOMER_TEMPLATE_LANG',
        'WHATSAPP_BOOKING_DETAIL_FIELDS',
        'WHATSAPP_RIDE_STATUS_TEMPLATE', 'WHATSAPP_RIDE_STATUS_TEMPLATE_LANG',
        'WHATSAPP_RIDE_STATUS_EVENTS',
        'WHATSAPP_PICKUP_PROPOSAL_TEMPLATE', 'WHATSAPP_PICKUP_PROPOSAL_TEMPLATE_LANG',
        'WHATSAPP_COMPANY_BOOKING_NOTIFY_ENABLED',
        'WHATSAPP_CLICK_TO_CHAT_ENABLED', 'WHATSAPP_CLICK_TO_CHAT_NUMBER',
        'WHATSAPP_COMPANY_BOOKING_NOTIFY_NUMBER',
        'WHATSAPP_WIDGET_ENABLED', 'WHATSAPP_WIDGET_PHONE', 'WHATSAPP_WIDGET_DEFAULT_MESSAGE',
    ];

    public function __construct()
    {
        // Get the .env file path - check if it's in root or backend directory
        $rootPath = base_path();
        $backendPath = base_path('backend');

        $rootEnv = $rootPath.'/.env';
        $backendEnv = $backendPath.'/.env';
        $fallback = base_path('.env');

        // File::exists is true for directories too; file() requires a regular file.
        if (File::exists($rootEnv) && is_file($rootEnv)) {
            $this->envPath = $rootEnv;
        } elseif (File::exists($backendEnv) && is_file($backendEnv)) {
            $this->envPath = $backendEnv;
        } elseif (File::exists($fallback) && is_file($fallback)) {
            $this->envPath = $fallback;
        } else {
            $this->envPath = $fallback;
        }
    }

    /**
     * Get all environment variables
     */
    public function getAll()
    {
        if ($this->envFileCache !== null) {
            return $this->envFileCache;
        }

        $env = [];
        if (! is_string($this->envPath) || $this->envPath === '' || ! is_file($this->envPath) || ! is_readable($this->envPath)) {
            return $this->envFileCache = $env;
        }

        $lines = @file($this->envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! is_array($lines)) {
            return $env;
        }

        foreach ($lines as $line) {
            // Skip comments
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parse KEY=VALUE
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }

                $env[$key] = $value;
            }
        }

        return $this->envFileCache = $env;
    }

    /**
     * Mail-instellingen voor config-overlay (één batch-query i.p.v. per key).
     *
     * @return array<string, string|null>
     */
    /**
     * @param  int|null  $forCompanyId  Expliciete tenant; null = huidige scope (host/admin)
     * @return array<string, string|null>
     */
    public function getMailOverlayValues(?int $forCompanyId = null, bool $platformOnly = false): array
    {
        $cacheKey = $platformOnly ? -1 : ($forCompanyId !== null && $forCompanyId > 0 ? $forCompanyId : 0);
        if (isset($this->mailOverlayCacheByScope[$cacheKey])) {
            return $this->mailOverlayCacheByScope[$cacheKey];
        }

        $keys = [
            'MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION',
            'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME',
        ];

        $cid = null;
        if (! $platformOnly) {
            $cid = $forCompanyId !== null && $forCompanyId > 0 ? $forCompanyId : null;
            if ($cid === null) {
                $cid = GeneralSetting::resolveScopeCompanyId();
            }
        }

        $fromTenant = [];
        $fromPlatform = [];
        try {
            if ($cid !== null) {
                $fromTenant = GeneralSetting::query()
                    ->whereIn('key', $keys)
                    ->where('company_id', $cid)
                    ->pluck('value', 'key')
                    ->all();
            }
            $fromPlatform = GeneralSetting::query()
                ->whereIn('key', $keys)
                ->whereNull('company_id')
                ->pluck('value', 'key')
                ->all();
        } catch (\Throwable) {
            $fromTenant = [];
            $fromPlatform = [];
        }
        $fromEnv = $this->getAll();
        $merged = [];

        foreach ($keys as $key) {
            $merged[$key] = $this->firstNonEmptyMailValue(
                $fromTenant[$key] ?? null,
                $fromPlatform[$key] ?? null,
                $fromEnv[$key] ?? null
            );
        }

        return $this->mailOverlayCacheByScope[$cacheKey] = $merged;
    }

    private function firstNonEmptyMailValue(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * Alleen de Nexa SaaS-mailserver (geen tenant-SMTP, geen geselecteerde tenant).
     */
    public function applyPlatformMailConfigToRuntime(): void
    {
        $this->applyMailConfigToRuntime(null, true);
    }

    /**
     * Pas mailconfiguratie uit admin (#mail) toe op de runtime (SMTP-auth + From).
     */
    public function applyMailConfigToRuntime(?int $forCompanyId = null, bool $platformOnly = false): void
    {
        $mail = $this->getMailOverlayValues($forCompanyId, $platformOnly);
        $mailer = $mail['MAIL_MAILER'] ?? 'log';
        $encryption = $mail['MAIL_ENCRYPTION'] ?? 'tls';
        $fromAddress = $mail['MAIL_FROM_ADDRESS'] ?? config('mail.from.address', 'noreply@example.com');
        $fromName = $mail['MAIL_FROM_NAME'] ?? config('mail.from.name', config('app.name', 'NEXA'));

        Config::set('mail.default', $mailer);
        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', $fromName);

        if ($mailer === 'smtp') {
            $port = $mail['MAIL_PORT'] ?? '587';
            Config::set('mail.mailers.smtp.host', $mail['MAIL_HOST'] ?? '');
            Config::set('mail.mailers.smtp.port', $port);
            Config::set('mail.mailers.smtp.username', $mail['MAIL_USERNAME'] ?? '');
            Config::set('mail.mailers.smtp.password', $mail['MAIL_PASSWORD'] ?? '');
            Config::set('mail.mailers.smtp.scheme', $this->smtpSchemeForEncryption($encryption, $port));
            Config::set('mail.mailers.smtp.encryption', $encryption === 'null' || $encryption === '' ? null : $encryption);
        }

        app()->forgetInstance('mail.manager');
    }

    /**
     * Laravel 12 SMTP-DSN accepteert alleen smtp/smtps, niet het oude MAIL_ENCRYPTION-waarde tls.
     */
    public function smtpSchemeForEncryption(?string $encryption, int|string|null $port = null): string
    {
        $enc = strtolower(trim((string) $encryption));
        $port = (int) $port;
        if ($enc === 'ssl' || $enc === 'smtps' || $port === 465) {
            return 'smtps';
        }

        return 'smtp';
    }

    /**
     * Uitleg voor de beheerder waarom uitgaande mail (nog) niet aankomt.
     */
    public function mailDeliveryHint(?int $forCompanyId = null): ?string
    {
        $mail = $this->getMailOverlayValues($forCompanyId);
        $mailer = strtolower(trim((string) ($mail['MAIL_MAILER'] ?? config('mail.default', 'log'))));

        if ($mailer === '' || $mailer === 'log' || $mailer === 'array') {
            return 'Mailer staat op „Log (alleen loggen)”. E-mails worden nergens naar een inbox verstuurd. Kies SMTP, vul host/poort/gebruikersnaam/wachtwoord in en sla op.';
        }

        if ($mailer === 'smtp') {
            if (trim((string) ($mail['MAIL_HOST'] ?? '')) === '') {
                return 'SMTP-host ontbreekt. Vul de SMTP-server in (bijvoorbeeld send.one.com) en sla op.';
            }
            if (trim((string) ($mail['MAIL_USERNAME'] ?? '')) === '' || trim((string) ($mail['MAIL_PASSWORD'] ?? '')) === '') {
                return 'SMTP-gebruikersnaam of -wachtwoord ontbreekt. Zonder inloggen weigert de mailserver verzending.';
            }
        }

        $scheme = strtolower(trim((string) config('mail.mailers.smtp.scheme', '')));
        if ($mailer === 'smtp' && in_array($scheme, ['tls', 'ssl'], true)) {
            return 'SMTP-scheme „'.$scheme.'” is ongeldig in Laravel 12. Gebruik STARTTLS op poort 587 (scheme smtp) of SSL op poort 465 (scheme smtps). Dit wordt bij verzenden automatisch gecorrigeerd.';
        }

        return null;
    }

    public function explainMailSendException(\Throwable $e): string
    {
        $raw = $e->getMessage();

        if (str_contains($raw, 'scheme is not supported')) {
            return 'SMTP-encryptie is verkeerd ingesteld voor Laravel 12: scheme moet smtp (poort 587, STARTTLS) of smtps (poort 465, SSL) zijn, niet tls. Pas Encryptie/poort aan onder Mail Server Instellingen en probeer opnieuw.';
        }
        if (str_contains($raw, 'not authorized to send on behalf of') || str_contains($raw, '550 5.7.1')) {
            return 'De mailserver weigert verzending: het From-adres mag niet namens deze SMTP-gebruiker. Zet From-adres gelijk aan de SMTP-gebruikersnaam, of laat de server namens dat adres verzenden.';
        }
        if (str_contains($raw, 'Connection could not be established') || str_contains($raw, 'Connection timed out')) {
            return 'Geen verbinding met de SMTP-server. Controleer host, poort, firewall en of TLS/SSL bij de poort past (587 = TLS, 465 = SSL).';
        }
        if (str_contains($raw, 'Failed to authenticate') || str_contains($raw, '535')) {
            return 'SMTP-inloggen mislukt. Controleer gebruikersnaam en wachtwoord.';
        }

        return 'Verzenden mislukt: '.$raw;
    }

    /**
     * Of uitgaande mail daadwerkelijk naar een inbox kan (niet alleen log/array).
     */
    public function isMailDeliverableToInbox(?int $forCompanyId = null): bool
    {
        $mail = $this->getMailOverlayValues($forCompanyId);
        $this->applyMailConfigToRuntime($forCompanyId);
        $mailer = (string) ($mail['MAIL_MAILER'] ?? config('mail.default', 'log'));

        if (in_array($mailer, ['log', 'array'], true)) {
            return false;
        }

        if ($mailer === 'smtp') {
            return trim((string) ($mail['MAIL_HOST'] ?? '')) !== '';
        }

        return true;
    }

    /**
     * @return array{from_address: string, from_name: string, smtp_username: string}
     */
    public function resolveMailFromHeaders(?int $forCompanyId = null, bool $platformOnly = false): array
    {
        $mail = $this->getMailOverlayValues($forCompanyId, $platformOnly);
        $configuredFrom = $mail['MAIL_FROM_ADDRESS'] ?? config('mail.from.address', 'noreply@example.com');
        $smtpUsername = trim((string) ($mail['MAIL_USERNAME'] ?? ''));

        // Envelope/From moet overeenkomen met SMTP-gebruiker als de server dat vereist
        $fromAddress = ($smtpUsername !== '' && $smtpUsername !== $configuredFrom)
            ? $smtpUsername
            : $configuredFrom;

        return [
            'from_address' => $fromAddress,
            'from_name' => $mail['MAIL_FROM_NAME'] ?? config('mail.from.name', config('app.name', 'NEXA')),
            'smtp_username' => $smtpUsername,
        ];
    }

    /**
     * Get a specific environment variable.
     * For keys in GENERAL_SETTING_KEYS, returns GeneralSetting value first (like Google Reviews), then .env.
     * Optional $companyId scopes tenant settings. Platform WhatsApp Business keys
     * (WHATSAPP_API_TOKEN, WHATSAPP_PHONE_NUMBER_ID, …) are always global.
     */
    public function get($key, $default = null, ?int $companyId = null)
    {
        if (in_array($key, self::GENERAL_SETTING_KEYS, true)) {
            if (GeneralSetting::isMailSettingKey($key)) {
                $mail = $this->getMailOverlayValues($companyId);
                $value = $mail[$key] ?? null;

                return $value !== null ? $value : $default;
            }
            $value = GeneralSetting::get($key, null, $companyId);
            if ($value !== null) {
                return $value;
            }
        }
        $all = $this->getAll();

        return $all[$key] ?? $default;
    }

    /**
     * Pad naar de root .env (projectroot, één niveau boven Laravel base_path).
     * GOOGLE_MAPS_API_KEY staat hier; backend/.env wordt niet gebruikt voor deze key.
     */
    public static function getRootEnvPath(): string
    {
        return dirname(base_path()).'/.env';
    }

    /**
     * Google Maps API key: eerst platform-configuratie (Algemene configuraties), daarna .env-fallback.
     */
    public function getGoogleMapsApiKey(): string
    {
        $key = trim((string) $this->get('GOOGLE_MAPS_API_KEY', ''));
        if ($key !== '') {
            return $key;
        }

        $rootEnv = self::getRootEnvPath();
        if (File::exists($rootEnv) && is_readable($rootEnv)) {
            $key = trim((string) $this->getFromFile($rootEnv, 'GOOGLE_MAPS_API_KEY', ''));
            if ($key !== '') {
                return $key;
            }
        }

        return trim((string) (config('maps.api_key') ?? env('GOOGLE_MAPS_API_KEY', '')));
    }

    /**
     * Lees één key uit een .env-bestand (zonder wijziging van $this->envPath).
     * Key-match is case-sensitive; waarde mag tussen aanhalingstekens staan.
     */
    private function getFromFile(string $filePath, string $key, $default = null)
    {
        if (! File::exists($filePath) || ! is_readable($filePath)) {
            return $default;
        }
        $lines = @file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! is_array($lines)) {
            return $default;
        }
        $key = trim($key);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            if (strpos($line, '=') !== false) {
                [$k, $value] = explode('=', $line, 2);
                if (trim($k) === $key) {
                    $value = trim($value);
                    if (strlen($value) >= 2 && ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') || (substr($value, 0, 1) === "'" && substr($value, -1) === "'"))) {
                        $value = substr($value, 1, -1);
                    }

                    return $value;
                }
            }
        }

        return $default;
    }

    /**
     * Google Maps Map ID (optioneel). Bij invullen: kaarten gebruiken AdvancedMarkerElement (geen deprecation).
     */
    public function getGoogleMapsMapId(): string
    {
        return trim((string) $this->get('GOOGLE_MAPS_MAP_ID', ''));
    }

    /**
     * @return array<string, string>
     */
    public function mapsFormSettings(): array
    {
        return [
            'GOOGLE_MAPS_API_KEY' => $this->getGoogleMapsApiKey(),
            'GOOGLE_MAPS_MAP_ID' => $this->getGoogleMapsMapId(),
            'GOOGLE_MAPS_ZOOM' => (string) $this->get('GOOGLE_MAPS_ZOOM', '12'),
            'GOOGLE_MAPS_CENTER_LAT' => (string) $this->get('GOOGLE_MAPS_CENTER_LAT', '52.3676'),
            'GOOGLE_MAPS_CENTER_LNG' => (string) $this->get('GOOGLE_MAPS_CENTER_LNG', '4.9041'),
            'GOOGLE_MAPS_TYPE' => (string) $this->get('GOOGLE_MAPS_TYPE', 'roadmap'),
        ];
    }

    /**
     * Synchroniseer platform Maps-instellingen naar config('maps.*') voor legacy config()-gebruik.
     */
    public function syncMapsConfig(): void
    {
        $settings = $this->mapsFormSettings();
        config([
            'maps.api_key' => $settings['GOOGLE_MAPS_API_KEY'],
            'maps.map_id' => $settings['GOOGLE_MAPS_MAP_ID'],
            'maps.zoom' => (int) $settings['GOOGLE_MAPS_ZOOM'],
            'maps.center_lat' => $settings['GOOGLE_MAPS_CENTER_LAT'],
            'maps.center_lng' => $settings['GOOGLE_MAPS_CENTER_LNG'],
            'maps.type' => $settings['GOOGLE_MAPS_TYPE'],
        ]);
    }

    /**
     * Set environment variables
     */
    public function set(array $variables)
    {
        if (! File::exists($this->envPath)) {
            throw new \Exception('.env file not found');
        }

        if (is_file($this->envPath)) {
            $backupPath = $this->envPath.'.backup.'.date('Y-m-d_His');
            File::copy($this->envPath, $backupPath);
        }

        $env = $this->getAll();

        // Update values
        foreach ($variables as $key => $value) {
            $env[$key] = $value;
        }

        // Write back to file
        $content = '';
        $lines = file($this->envPath, FILE_IGNORE_NEW_LINES);
        $keysWritten = [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            // Keep comments and empty lines as is
            if (strpos($trimmedLine, '#') === 0 || empty($trimmedLine)) {
                $content .= $line."\n";

                continue;
            }

            // Parse and update existing keys
            if (strpos($line, '=') !== false) {
                [$key] = explode('=', $line, 2);
                $key = trim($key);

                if (isset($env[$key])) {
                    $value = $env[$key];
                    // Add quotes if value contains spaces or special characters
                    if (preg_match('/[\s=#]/', $value) || empty($value)) {
                        $value = '"'.addslashes($value).'"';
                    }
                    $content .= $key.'='.$value."\n";
                    $keysWritten[] = $key;
                    unset($env[$key]);
                } else {
                    $content .= $line."\n";
                }
            } else {
                $content .= $line."\n";
            }
        }

        // Add new keys that weren't in the file
        foreach ($env as $key => $value) {
            if (! in_array($key, $keysWritten)) {
                // Add quotes if value contains spaces or special characters
                if (preg_match('/[\s=#]/', $value) || empty($value)) {
                    $value = '"'.addslashes($value).'"';
                }
                $content .= $key.'='.$value."\n";
            }
        }

        File::put($this->envPath, $content);
    }
}
