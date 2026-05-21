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

    /** @var array<string, string|null>|null */
    private ?array $mailOverlayCache = null;

    /** Keys that are stored in GeneralSetting (admin settings); EnvService::get() prefers DB over .env */
    private const GENERAL_SETTING_KEYS = [
        'MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION',
        'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME',
        'GOOGLE_SEO_PROPERTY_ID', 'GOOGLE_ANALYTICS_ID', 'GOOGLE_TAG_MANAGER_ID',
        'META_DESCRIPTION', 'META_KEYWORDS', 'GOOGLE_SITE_VERIFICATION',
        'GOOGLE_MAPS_API_KEY', 'GOOGLE_MAPS_MAP_ID', 'GOOGLE_MAPS_ZOOM',
        'GOOGLE_MAPS_CENTER_LAT', 'GOOGLE_MAPS_CENTER_LNG', 'GOOGLE_MAPS_TYPE',
        'WHATSAPP_API_TOKEN', 'WHATSAPP_PHONE_NUMBER_ID', 'WHATSAPP_BUSINESS_ACCOUNT_ID',
        'WHATSAPP_API_VERSION', 'WHATSAPP_WEBHOOK_VERIFY_TOKEN', 'WHATSAPP_DEFAULT_MESSAGE',
        'WHATSAPP_CLICK_TO_CHAT_ENABLED', 'WHATSAPP_CLICK_TO_CHAT_NUMBER',
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
    public function getMailOverlayValues(): array
    {
        if ($this->mailOverlayCache !== null) {
            return $this->mailOverlayCache;
        }

        $keys = [
            'MAIL_MAILER', 'MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_ENCRYPTION',
            'MAIL_FROM_ADDRESS', 'MAIL_FROM_NAME',
        ];

        $fromDb = GeneralSetting::getMany($keys);
        $fromEnv = $this->getAll();
        $merged = [];

        foreach ($keys as $key) {
            $value = $fromDb[$key] ?? null;
            if ($value === null || $value === '') {
                $value = $fromEnv[$key] ?? null;
            }
            $merged[$key] = $value !== null && $value !== '' ? (string) $value : null;
        }

        return $this->mailOverlayCache = $merged;
    }

    /**
     * Pas mailconfiguratie uit admin (#mail) toe op de runtime (SMTP-auth + From).
     */
    public function applyMailConfigToRuntime(): void
    {
        $mailer = $this->get('MAIL_MAILER', 'log');
        $encryption = $this->get('MAIL_ENCRYPTION', 'tls');
        $fromAddress = $this->get('MAIL_FROM_ADDRESS', config('mail.from.address', 'noreply@example.com'));
        $fromName = $this->get('MAIL_FROM_NAME', config('mail.from.name', config('app.name', 'NEXA')));

        Config::set('mail.default', $mailer);
        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', $fromName);

        if ($mailer === 'smtp') {
            Config::set('mail.mailers.smtp.host', $this->get('MAIL_HOST', ''));
            Config::set('mail.mailers.smtp.port', $this->get('MAIL_PORT', '587'));
            Config::set('mail.mailers.smtp.username', $this->get('MAIL_USERNAME', ''));
            Config::set('mail.mailers.smtp.password', $this->get('MAIL_PASSWORD', ''));
            Config::set('mail.mailers.smtp.encryption', $encryption === 'null' ? null : $encryption);
        }

        app()->forgetInstance('mail.manager');
    }

    /**
     * @return array{from_address: string, from_name: string, smtp_username: string}
     */
    public function resolveMailFromHeaders(): array
    {
        $configuredFrom = $this->get('MAIL_FROM_ADDRESS', config('mail.from.address', 'noreply@example.com'));
        $smtpUsername = trim((string) $this->get('MAIL_USERNAME', ''));

        // Envelope/From moet overeenkomen met SMTP-gebruiker als de server dat vereist
        $fromAddress = ($smtpUsername !== '' && $smtpUsername !== $configuredFrom)
            ? $smtpUsername
            : $configuredFrom;

        return [
            'from_address' => $fromAddress,
            'from_name' => $this->get('MAIL_FROM_NAME', config('mail.from.name', config('app.name', 'NEXA'))),
            'smtp_username' => $smtpUsername,
        ];
    }

    /**
     * Get a specific environment variable.
     * For keys in GENERAL_SETTING_KEYS, returns GeneralSetting value first (like Google Reviews), then .env.
     */
    public function get($key, $default = null)
    {
        if (in_array($key, self::GENERAL_SETTING_KEYS, true)) {
            $value = GeneralSetting::get($key, null);
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
     * Google Maps API key uit de root .env (projectroot).
     * De key staat in .env in de projectroot, niet in backend/.env.
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
     * Set environment variables
     */
    public function set(array $variables)
    {
        if (! File::exists($this->envPath)) {
            throw new \Exception('.env file not found');
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
