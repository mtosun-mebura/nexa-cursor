<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\EnvService;
use App\Services\ProfanityFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;

class ContactController extends Controller
{
    protected $envService;
    protected $profanityFilter;

    public function __construct(EnvService $envService, ProfanityFilter $profanityFilter)
    {
        $this->envService = $envService;
        $this->profanityFilter = $profanityFilter;
    }

    /**
     * Display the contact form.
     */
    public function index()
    {
        return view('frontend.pages.contact');
    }

    /**
     * Handle the contact form submission.
     */
    public function submit(Request $request)
    {
        // Honeypot captcha check (geheime captcha)
        // Als dit veld is ingevuld, is het een bot
        if ($request->filled('website')) {
            // Bot detected, silently fail
            \Log::info('Contact form: Bot detected via honeypot');
            return redirect()->route('contact')->with('success', 'Bedankt voor uw bericht!');
        }

        // Time-based validation: form should not be submitted too quickly (less than 1 second)
        // This helps prevent bots that auto-submit forms immediately
        $submitTime = $request->input('_submit_time', 0);
        if ($submitTime > 0) {
            $timeDiff = time() - $submitTime;
            // Only block if submitted in less than 1 second (very fast = likely bot)
            if ($timeDiff < 1) {
                // Submitted too quickly, likely a bot
                \Log::warning('Contact form: Submitted too quickly', [
                    'time_diff' => $timeDiff,
                    'ip' => $request->ip(),
                ]);
                return redirect()->route('contact')->with('error', 'Het formulier is te snel verzonden. Wacht even en probeer het opnieuw.');
            }
        }

        // Validate the form met uitgebreide validatie
        $validator = Validator::make($request->all(), [
            'first_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[\p{L}\s\-\'\.]+$/u', // Alleen letters, spaties, streepjes, apostrofs en punten
            ],
            'last_name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[\p{L}\s\-\'\.]+$/u', // Alleen letters, spaties, streepjes, apostrofs en punten
            ],
            'email' => [
                'required',
                'email:rfc,dns,strict', // Strikte email validatie met DNS check
                'max:255',
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:/^[0-9]{10}$/', // Exact 10 cijfers
            ],
            'message' => [
                'required',
                'string',
                'min:10', // Minimaal 10 karakters
                'max:1000',
            ],
        ], [
            'first_name.required' => 'Voornaam is verplicht.',
            'first_name.min' => 'Voornaam moet minimaal 2 karakters lang zijn.',
            'first_name.max' => 'Voornaam mag maximaal 255 karakters bevatten.',
            'first_name.regex' => 'Voornaam mag alleen letters, spaties, streepjes, apostrofs en punten bevatten.',
            'last_name.required' => 'Achternaam is verplicht.',
            'last_name.min' => 'Achternaam moet minimaal 2 karakters lang zijn.',
            'last_name.max' => 'Achternaam mag maximaal 255 karakters bevatten.',
            'last_name.regex' => 'Achternaam mag alleen letters, spaties, streepjes, apostrofs en punten bevatten.',
            'email.required' => 'E-mailadres is verplicht.',
            'email.email' => 'E-mailadres moet een geldig en volledig e-mailadres zijn.',
            'phone.regex' => 'Telefoonnummer moet exact 10 cijfers bevatten (bijvoorbeeld: 0612345678).',
            'message.required' => 'Omschrijving is verplicht.',
            'message.min' => 'Omschrijving moet minimaal 10 karakters bevatten.',
            'message.max' => 'Omschrijving mag maximaal 1000 karakters bevatten.',
        ]);

        // Extra beveiligingschecks na validatie
        if ($validator->passes()) {
            $first_name = $request->input('first_name');
            $last_name = $request->input('last_name');
            $email = $request->input('email');
            $phone = $request->input('phone');
            $message = $request->input('message');
            
            // Check op SQL injection patterns (slimmere detectie)
            // Let op: Laravel gebruikt al prepared statements, maar extra checks helpen
            $sqlPatterns = [
                // SQL commando's met quotes en semicolons (gevaarlijke combinaties)
                '/(\bUNION\b.*\bSELECT\b.*[\'";])/i',
                '/(\bSELECT\b.*\bFROM\b.*[\'";])/i',
                '/(\bINSERT\b.*\bINTO\b.*[\'";])/i',
                '/(\bUPDATE\b.*\bSET\b.*[\'";])/i',
                '/(\bDELETE\b.*\bFROM\b.*[\'";])/i',
                '/(\bDROP\b.*\bTABLE\b.*[\'";])/i',
                // SQL commentaar syntax in combinatie met commando's
                '/(--|\/\*|\*\/).*(\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b)/i',
                // SQL injection via OR/AND true patterns
                '/(\bOR\b|\bAND\b).*(\'?\s*1\s*=\s*1\'?|\'?\s*\'1\'=\'1\'?)/i',
                // Gevaarlijke combinaties van quotes en SQL keywords
                '/[\'";].*(\bUNION\b|\bSELECT\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b|\bDROP\b)/i',
            ];
            
            $allInput = $first_name . ' ' . $last_name . ' ' . $email . ' ' . ($phone ?? '') . ' ' . $message;
            
            foreach ($sqlPatterns as $pattern) {
                if (preg_match($pattern, $allInput)) {
                    \Log::warning('Contact form: SQL injection attempt detected', [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                        'pattern' => $pattern,
                    ]);
                    return redirect()->route('contact')
                        ->with('error', 'Ongeldige invoer gedetecteerd. Probeer het opnieuw.')
                        ->withInput();
                }
            }
            
            // Check op XSS patterns
            $xssPatterns = [
                '/(<script|<\/script>)/i',
                '/(javascript:)/i',
                '/(onerror|onload|onclick)=/i',
                '/(<iframe|<\/iframe>)/i',
                '/(<object|<\/object>)/i',
                '/(<embed|<\/embed>)/i',
            ];
            
            foreach ($xssPatterns as $pattern) {
                if (preg_match($pattern, $allInput)) {
                    \Log::warning('Contact form: XSS attempt detected', [
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);
                    return redirect()->route('contact')
                        ->with('error', 'Ongeldige invoer gedetecteerd. Probeer het opnieuw.')
                        ->withInput();
                }
            }
            
            // Check op profanity in alle tekstvelden
            if ($this->profanityFilter->containsProfanity($first_name) ||
                $this->profanityFilter->containsProfanity($last_name) ||
                $this->profanityFilter->containsProfanity($message)) {
                \Log::warning('Contact form: Profanity detected', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                return redirect()->route('contact')
                    ->with('error', 'Uw bericht bevat ongepaste taal. Pas uw bericht aan en probeer het opnieuw.')
                    ->withInput();
            }
        }

        if ($validator->fails()) {
            return redirect()->route('contact')
                ->withErrors($validator)
                ->withInput();
        }

        // Sanitize alle input data (veilig maken voor gebruik)
        // Laravel's Eloquent gebruikt prepared statements, maar extra sanitization is een goede beveiligingslaag
        $data = [
            'first_name' => trim($request->input('first_name')),
            'last_name' => trim($request->input('last_name')),
            'email' => trim($request->input('email')),
            'phone' => $request->input('phone') ? preg_replace('/[^0-9]/', '', trim($request->input('phone'))) : null,
            'message' => trim($request->input('message')),
        ];

        try {
            // Laad mail instellingen uit backend instellingen
            $this->applyMailSettings();
            
            \Log::info('Contact form: Attempting to send email', ['to' => 'support@mebura.nl', 'data' => array_keys($data)]);
            
            // Prepare email data - veilig voor gebruik in email template
            // Note: We use 'user_message' instead of 'message' to avoid conflict with Laravel's $message variable
            // Data is al gesanitized, maar we gebruiken htmlspecialchars opnieuw voor extra veiligheid
            $emailData = [
                'first_name' => htmlspecialchars($data['first_name'], ENT_QUOTES, 'UTF-8'),
                'last_name' => htmlspecialchars($data['last_name'], ENT_QUOTES, 'UTF-8'),
                'email' => filter_var($data['email'], FILTER_SANITIZE_EMAIL),
                'phone' => $data['phone'] ? htmlspecialchars($data['phone'], ENT_QUOTES, 'UTF-8') : '',
                'user_message' => nl2br(htmlspecialchars($data['message'], ENT_QUOTES, 'UTF-8')), // Voor email template met line breaks
            ];
            
            // Haal from adres en naam uit backend instellingen
            // FROM adres wordt alleen gebruikt voor email headers, niet voor SMTP authenticatie
            $fromAddress = $this->envService->get('MAIL_FROM_ADDRESS', config('mail.from.address', 'noreply@nexa-skillmatching.nl'));
            $fromName = $this->envService->get('MAIL_FROM_NAME', config('mail.from.name', 'NEXA Skillmatching'));
            
            // Haal SMTP username op voor envelope sender
            // De envelope sender (SMTP MAIL FROM commando) moet overeenkomen met de SMTP authenticatie gebruiker
            // om te voorkomen dat de mailserver de verzending weigert
            $smtpUsername = $this->envService->get('MAIL_USERNAME', '');
            
            // Send email
            // SMTP authenticatie gebruikt automatisch MAIL_USERNAME en MAIL_PASSWORD
            // FROM adres wordt alleen in de email headers gezet
            // Envelope sender wordt ingesteld op SMTP username voor de SMTP MAIL FROM commando
            Mail::send('emails.contact', $emailData, function ($mailMessage) use ($emailData, $fromAddress, $fromName, $smtpUsername) {
                $subject = 'Nieuw contactformulier bericht van ' . $emailData['first_name'] . ' ' . $emailData['last_name'];
                $mailMessage->to('support@mebura.nl', 'NEXA Support')
                    ->subject($subject)
                    ->replyTo($emailData['email'], $emailData['first_name'] . ' ' . $emailData['last_name'])
                    ->from($fromAddress, $fromName);
                
                // Voeg een Sender header toe als SMTP username beschikbaar is
                // De Sender header geeft aan welk adres daadwerkelijk de email verzendt
                // Dit kan helpen bij mailservers die autorisatie controleren
                if (!empty($smtpUsername)) {
                    try {
                        $symfonyMessage = $mailMessage->getSymfonyMessage();
                        $symfonyMessage->getHeaders()->remove('Sender');
                        $symfonyMessage->getHeaders()->addMailboxHeader('Sender', $smtpUsername);
                    } catch (\Exception $e) {
                        // Als het toevoegen van de Sender header faalt, log het maar ga door
                        \Log::warning('Could not set Sender header', [
                            'error' => $e->getMessage(),
                            'smtp_username' => $smtpUsername
                        ]);
                    }
                }
            });
            
            \Log::info('Contact form: Email sent successfully');

            return redirect()->route('contact')->with('success', 'Bedankt voor uw bericht! We nemen zo spoedig mogelijk contact met u op.');
        } catch (\Illuminate\Session\TokenMismatchException $e) {
            \Log::error('Contact form: CSRF token mismatch', [
                'error' => $e->getMessage(),
                'session_token' => session()->token(),
                'request_token' => $request->input('_token'),
            ]);
            
            return redirect()->route('contact')
                ->with('error', 'Uw sessie is verlopen. Ververs de pagina en probeer het opnieuw.')
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Contact form error: ' . $e->getMessage());
            \Log::error('Contact form error trace: ' . $e->getTraceAsString());
            \Log::error('Contact form error file: ' . $e->getFile() . ':' . $e->getLine());
            \Log::error('Contact form error details', [
                'mailer' => $this->envService->get('MAIL_MAILER', 'unknown'),
                'from_address' => $this->envService->get('MAIL_FROM_ADDRESS', 'unknown'),
                'smtp_host' => $this->envService->get('MAIL_HOST', 'unknown'),
                'smtp_username' => $this->envService->get('MAIL_USERNAME', 'unknown'),
            ]);
            
            // If mail is configured to log, still show success to user
            $mailer = $this->envService->get('MAIL_MAILER', config('mail.default', 'log'));
            if ($mailer === 'log') {
                \Log::info('Contact form: Mail configured to log, showing success message');
                return redirect()->route('contact')->with('success', 'Bedankt voor uw bericht! We nemen zo spoedig mogelijk contact met u op.');
            }
            
            // Check if it's an SMTP authorization error
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'not authorized to send') !== false || 
                strpos($errorMessage, '550') !== false ||
                strpos($errorMessage, 'not authorized') !== false) {
                
                // Haal de huidige instellingen op voor betere foutmelding
                $smtpUsername = $this->envService->get('MAIL_USERNAME', 'niet ingesteld');
                $fromAddress = $this->envService->get('MAIL_FROM_ADDRESS', 'niet ingesteld');
                
                \Log::error('Contact form: SMTP authorization error', [
                    'smtp_username' => $smtpUsername,
                    'from_address' => $fromAddress,
                    'error' => $errorMessage,
                ]);
                
                return redirect()->route('contact')
                    ->with('error', 'Mail server weigert verzending: De SMTP gebruiker (' . $smtpUsername . ') is niet geautoriseerd om namens het FROM adres (' . $fromAddress . ') te verzenden. Pas de mail instellingen aan in de admin interface of vraag de beheerder om de SMTP gebruiker te autoriseren voor het FROM adres.')
                    ->withInput();
            }
            
            return redirect()->route('contact')
                ->with('error', 'Er is een fout opgetreden bij het versturen van uw bericht: ' . $e->getMessage() . '. Probeer het later opnieuw of neem contact op met de beheerder.')
                ->withInput();
        }
    }

    /**
     * Apply mail settings from backend configuration
     * SMTP authenticatie gebruikt alleen username en password
     * FROM adres is alleen voor email headers, niet voor authenticatie
     */
    protected function applyMailSettings()
    {
        // Haal mail instellingen op uit .env via EnvService
        $mailer = $this->envService->get('MAIL_MAILER', 'log');
        $host = $this->envService->get('MAIL_HOST', '');
        $port = $this->envService->get('MAIL_PORT', '587');
        $username = $this->envService->get('MAIL_USERNAME', '');
        $password = $this->envService->get('MAIL_PASSWORD', '');
        $encryption = $this->envService->get('MAIL_ENCRYPTION', 'tls');
        $fromAddress = $this->envService->get('MAIL_FROM_ADDRESS', config('mail.from.address', 'noreply@nexa-skillmatching.nl'));
        $fromName = $this->envService->get('MAIL_FROM_NAME', config('mail.from.name', 'NEXA Skillmatching'));

        // Pas mail configuratie dynamisch aan
        Config::set('mail.default', $mailer);
        
        // FROM adres is alleen voor email headers, niet voor SMTP authenticatie
        Config::set('mail.from.address', $fromAddress);
        Config::set('mail.from.name', $fromName);

        // Als SMTP wordt gebruikt, pas SMTP authenticatie instellingen aan
        // Alleen username en password worden gebruikt voor authenticatie
        if ($mailer === 'smtp') {
            Config::set('mail.mailers.smtp.host', $host);
            Config::set('mail.mailers.smtp.port', $port);
            // SMTP authenticatie: alleen username en password
            Config::set('mail.mailers.smtp.username', $username);
            Config::set('mail.mailers.smtp.password', $password);
            Config::set('mail.mailers.smtp.encryption', $encryption === 'null' ? null : $encryption);
            
            // Zorg ervoor dat auth wordt gebruikt (als username en password zijn ingesteld)
            if (!empty($username) && !empty($password)) {
                Config::set('mail.mailers.smtp.auth_mode', null); // Laat Laravel automatisch auth mode bepalen
            }
        }
        
        // Clear mail manager cache zodat nieuwe configuratie wordt gebruikt
        app()->forgetInstance('mail.manager');
        
        // Registreer een custom envelope sender callback die de SMTP username gebruikt
        // Dit zorgt ervoor dat de envelope sender (SMTP MAIL FROM) gelijk is aan de SMTP username
        if ($mailer === 'smtp' && !empty($username)) {
            // We kunnen dit niet direct via config doen, maar moeten het per email instellen
            // Dit wordt gedaan in de Mail::send callback
        }
    }
}

