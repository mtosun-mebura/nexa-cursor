<?php

namespace App\Support;

use App\Models\GeneralSetting;

/**
 * Bekende SMTP-presets + door tenants opgeslagen custom providers.
 *
 * @phpstan-type Provider array{id: string, name: string, host: string, port: int, encryption: string}
 */
final class MailSmtpProviderCatalog
{
    public const CUSTOM_SETTING_KEY = 'MAIL_SMTP_CUSTOM_PROVIDERS';

    public const SELECTED_SETTING_KEY = 'MAIL_SMTP_PROVIDER';

    public const MANUAL_ID = '__manual__';

    /**
     * @return list<Provider>
     */
    public static function builtins(): array
    {
        return [
            ['id' => 'hostinger', 'name' => 'Hostinger', 'host' => 'smtp.hostinger.com', 'port' => 465, 'encryption' => 'ssl'],
            ['id' => 'one_com', 'name' => 'One.com', 'host' => 'send.one.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'gmail', 'name' => 'Gmail / Google Workspace', 'host' => 'smtp.gmail.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'microsoft365', 'name' => 'Microsoft 365 / Outlook', 'host' => 'smtp.office365.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'outlook_com', 'name' => 'Outlook.com / Hotmail', 'host' => 'smtp-mail.outlook.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'yahoo', 'name' => 'Yahoo Mail', 'host' => 'smtp.mail.yahoo.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'icloud', 'name' => 'iCloud Mail', 'host' => 'smtp.mail.me.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'aol', 'name' => 'AOL Mail', 'host' => 'smtp.aol.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'gmx', 'name' => 'GMX', 'host' => 'mail.gmx.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'zoho', 'name' => 'Zoho Mail', 'host' => 'smtp.zoho.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'zoho_eu', 'name' => 'Zoho Mail (EU)', 'host' => 'smtp.zoho.eu', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'fastmail', 'name' => 'Fastmail', 'host' => 'smtp.fastmail.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'proton_bridge', 'name' => 'Proton Mail Bridge', 'host' => '127.0.0.1', 'port' => 1025, 'encryption' => 'null'],
            ['id' => 'mailgun', 'name' => 'Mailgun', 'host' => 'smtp.mailgun.org', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'sendgrid', 'name' => 'SendGrid', 'host' => 'smtp.sendgrid.net', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'postmark', 'name' => 'Postmark', 'host' => 'smtp.postmarkapp.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'resend', 'name' => 'Resend', 'host' => 'smtp.resend.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'brevo', 'name' => 'Brevo (Sendinblue)', 'host' => 'smtp-relay.brevo.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'mailjet', 'name' => 'Mailjet', 'host' => 'in-v3.mailjet.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'sparkpost', 'name' => 'SparkPost', 'host' => 'smtp.sparkpostmail.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'mandrill', 'name' => 'Mandrill (Mailchimp)', 'host' => 'smtp.mandrillapp.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'smtp2go', 'name' => 'SMTP2GO', 'host' => 'mail.smtp2go.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'elastic_email', 'name' => 'Elastic Email', 'host' => 'smtp.elasticemail.com', 'port' => 2525, 'encryption' => 'tls'],
            ['id' => 'amazon_ses_eu_west_1', 'name' => 'Amazon SES (eu-west-1)', 'host' => 'email-smtp.eu-west-1.amazonaws.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'amazon_ses_eu_central_1', 'name' => 'Amazon SES (eu-central-1)', 'host' => 'email-smtp.eu-central-1.amazonaws.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'amazon_ses_us_east_1', 'name' => 'Amazon SES (us-east-1)', 'host' => 'email-smtp.us-east-1.amazonaws.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'transip', 'name' => 'TransIP', 'host' => 'smtp.transip.email', 'port' => 465, 'encryption' => 'ssl'],
            ['id' => 'antagonist', 'name' => 'Antagonist', 'host' => 'mail.antagonist.nl', 'port' => 465, 'encryption' => 'ssl'],
            ['id' => 'hostnet', 'name' => 'Hostnet', 'host' => 'smtp.hostnet.nl', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'vimexx', 'name' => 'Vimexx', 'host' => 'smtp.vimexx.nl', 'port' => 465, 'encryption' => 'ssl'],
            ['id' => 'yourhosting', 'name' => 'Yourhosting', 'host' => 'smtp.yourhosting.nl', 'port' => 465, 'encryption' => 'ssl'],
            ['id' => 'neostrada', 'name' => 'Neostrada', 'host' => 'smtp.neostrada.nl', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'combell', 'name' => 'Combell', 'host' => 'smtp.combell.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'ionos', 'name' => 'IONOS / 1&1', 'host' => 'smtp.ionos.nl', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'ionos_com', 'name' => 'IONOS (smtp.ionos.com)', 'host' => 'smtp.ionos.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'strato', 'name' => 'STRATO', 'host' => 'smtp.strato.com', 'port' => 465, 'encryption' => 'ssl'],
            ['id' => 'ovh', 'name' => 'OVH', 'host' => 'ssl0.ovh.net', 'port' => 465, 'encryption' => 'ssl'],
            ['id' => 'infomaniak', 'name' => 'Infomaniak', 'host' => 'mail.infomaniak.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'namecheap', 'name' => 'Namecheap Private Email', 'host' => 'mail.privateemail.com', 'port' => 465, 'encryption' => 'ssl'],
            ['id' => 'godaddy', 'name' => 'GoDaddy', 'host' => 'smtpout.secureserver.net', 'port' => 465, 'encryption' => 'ssl'],
            ['id' => 'dreamhost', 'name' => 'DreamHost', 'host' => 'smtp.dreamhost.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'siteground', 'name' => 'SiteGround', 'host' => 'mail.siteground.com', 'port' => 465, 'encryption' => 'ssl'],
            ['id' => 'bluehost', 'name' => 'Bluehost', 'host' => 'mail.bluehost.com', 'port' => 465, 'encryption' => 'ssl'],
            ['id' => 'scaleway', 'name' => 'Scaleway TEM', 'host' => 'smtp.tem.scaleway.com', 'port' => 587, 'encryption' => 'tls'],
            ['id' => 'mailtrap', 'name' => 'Mailtrap (sandbox)', 'host' => 'sandbox.smtp.mailtrap.io', 'port' => 2525, 'encryption' => 'tls'],
        ];
    }

    /**
     * @return list<Provider>
     */
    public static function allForCompany(?int $companyId): array
    {
        $byId = [];
        foreach (self::builtins() as $provider) {
            $byId[$provider['id']] = $provider;
        }
        foreach (self::customProviders(null) as $provider) {
            $byId[$provider['id']] = $provider;
        }
        if ($companyId !== null) {
            foreach (self::customProviders($companyId) as $provider) {
                $byId[$provider['id']] = $provider;
            }
        }

        $list = array_values($byId);
        usort($list, function (array $a, array $b): int {
            $priority = ['hostinger' => 0, 'one_com' => 1];
            $aRank = $priority[$a['id']] ?? 100;
            $bRank = $priority[$b['id']] ?? 100;
            if ($aRank !== $bRank) {
                return $aRank <=> $bRank;
            }

            return strcasecmp($a['name'], $b['name']);
        });

        return $list;
    }

    /**
     * @return list<Provider>
     */
    public static function customProviders(?int $companyId): array
    {
        $raw = GeneralSetting::get(self::CUSTOM_SETTING_KEY, '[]', $companyId);
        $decoded = json_decode((string) $raw, true);
        if (! is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $row) {
            $normalized = self::normalizeProvider($row);
            if ($normalized !== null) {
                $out[] = $normalized;
            }
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return Provider|null
     */
    public static function normalizeProvider(array $row): ?array
    {
        $host = strtolower(trim((string) ($row['host'] ?? '')));
        if ($host === '') {
            return null;
        }
        $port = (int) ($row['port'] ?? 587);
        if ($port < 1 || $port > 65535) {
            $port = 587;
        }
        $encryption = self::normalizeEncryption($row['encryption'] ?? 'tls');
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            $name = $host;
        }
        $id = trim((string) ($row['id'] ?? ''));
        if ($id === '' || $id === self::MANUAL_ID) {
            $id = self::customIdFor($host, $port, $encryption);
        }

        return [
            'id' => $id,
            'name' => $name,
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
        ];
    }

    public static function normalizeEncryption(mixed $encryption): string
    {
        $value = strtolower(trim((string) $encryption));
        if ($value === '' || $value === 'none' || $value === 'null') {
            return 'null';
        }
        if ($value === 'ssl' || $value === 'smtps') {
            return 'ssl';
        }

        return 'tls';
    }

    public static function customIdFor(string $host, int $port, string $encryption): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '_', strtolower(trim($host))) ?: 'smtp';
        $slug = trim($slug, '_');

        return 'custom_'.$slug.'_'.$port.'_'.self::normalizeEncryption($encryption);
    }

    /**
     * @param  list<Provider>  $providers
     * @return Provider|null
     */
    public static function findById(array $providers, ?string $id): ?array
    {
        $id = trim((string) $id);
        if ($id === '' || $id === self::MANUAL_ID) {
            return null;
        }
        foreach ($providers as $provider) {
            if ($provider['id'] === $id) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * @param  list<Provider>  $providers
     * @return Provider|null
     */
    public static function matchBySettings(array $providers, ?string $host, mixed $port, mixed $encryption): ?array
    {
        $host = strtolower(trim((string) $host));
        if ($host === '') {
            return null;
        }
        $port = (int) $port;
        $encryption = self::normalizeEncryption($encryption);
        foreach ($providers as $provider) {
            if (
                strtolower($provider['host']) === $host
                && (int) $provider['port'] === $port
                && self::normalizeEncryption($provider['encryption']) === $encryption
            ) {
                return $provider;
            }
        }

        return null;
    }

    /**
     * Sla een nieuwe SMTP-combinatie op als die nog niet in de lijst staat.
     *
     * @return Provider|null De (bestaande of nieuwe) provider
     */
    public static function rememberIfNew(?int $companyId, ?string $host, mixed $port, mixed $encryption, ?string $name = null): ?array
    {
        $host = strtolower(trim((string) $host));
        if ($host === '') {
            return null;
        }
        $port = (int) $port;
        if ($port < 1 || $port > 65535) {
            $port = 587;
        }
        $encryption = self::normalizeEncryption($encryption);
        $providers = self::allForCompany($companyId);
        $existing = self::matchBySettings($providers, $host, $port, $encryption);
        if ($existing !== null) {
            return $existing;
        }

        $provider = [
            'id' => self::customIdFor($host, $port, $encryption),
            'name' => trim((string) $name) !== '' ? trim((string) $name) : $host,
            'host' => $host,
            'port' => $port,
            'encryption' => $encryption,
        ];

        $customs = self::customProviders($companyId);
        $customs[] = $provider;
        GeneralSetting::set(self::CUSTOM_SETTING_KEY, json_encode(array_values($customs), JSON_UNESCAPED_UNICODE), $companyId);

        return $provider;
    }

    public static function resolveSelectedId(?int $companyId, ?string $storedId, ?string $host, mixed $port, mixed $encryption): string
    {
        $providers = self::allForCompany($companyId);
        $byId = self::findById($providers, $storedId);
        if ($byId !== null) {
            return $byId['id'];
        }
        $matched = self::matchBySettings($providers, $host, $port, $encryption);
        if ($matched !== null) {
            return $matched['id'];
        }

        return self::MANUAL_ID;
    }
}
