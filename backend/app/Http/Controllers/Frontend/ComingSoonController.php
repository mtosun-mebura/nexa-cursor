<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use Illuminate\Support\Facades\Storage;

/**
 * Toont de "Coming soon" landing page wanneer er geen actieve module is.
 * Instellingen zijn configureerbaar in admin (Configuraties).
 */
class ComingSoonController extends Controller
{
    /**
     * Default waarden voor coming soon (als nog niet ingesteld).
     */
    protected static function defaults(): array
    {
        return [
            'coming_soon_title' => 'We zijn bijna live',
            'coming_soon_text' => 'Onze website wordt op dit moment voor u klaargemaakt. Binnenkort vindt u hier alle informatie en mogelijkheden.',
            'coming_soon_secondary_text' => 'Heeft u vragen? Neem gerust contact met ons op.',
            'coming_soon_show_email' => '1',
            'coming_soon_contact_email' => '',
            'coming_soon_contact_label' => 'E-mail',
            'coming_soon_footer_text' => '© {year} {site}. Binnenkort beschikbaar.',
        ];
    }

    /**
     * Haal alle coming-soon instellingen op (met defaults).
     */
    public static function getSettings(): array
    {
        $defaults = static::defaults();
        $settings = [];
        foreach (array_keys($defaults) as $key) {
            $settings[$key] = GeneralSetting::get($key, $defaults[$key]);
        }

        $logoPath = GeneralSetting::get('logo');
        $settings['logo_url'] = null;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            // Relatief pad zodat het logo met hetzelfde protocol/host als de pagina laadt (voorkomt problemen bij http vs https op bv. localhost)
            $settings['logo_url'] = '/storage/' . ltrim($logoPath, '/');
        }

        $faviconPath = GeneralSetting::get('favicon');
        $settings['favicon_url'] = null;
        if ($faviconPath && Storage::disk('public')->exists($faviconPath)) {
            $settings['favicon_url'] = '/storage/' . ltrim($faviconPath, '/');
        }

        $settings['site_name'] = GeneralSetting::get('site_name', config('app.name', 'Nexa'));

        return $settings;
    }

    /**
     * Toon de coming soon pagina.
     */
    public function index()
    {
        $settings = static::getSettings();

        return view('frontend.coming-soon', [
            'settings' => $settings,
            'showEmail' => !empty($settings['coming_soon_show_email']) && $settings['coming_soon_show_email'] !== '0',
            'contactEmail' => $settings['coming_soon_contact_email'] ?? '',
        ]);
    }
}
