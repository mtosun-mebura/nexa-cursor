<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\Candidate;
use App\Modules\Skillmatching\Models\Vacancy;
use Illuminate\Support\Facades\Mail;

class EmailTemplateService
{
    /**
     * Send rejection email to candidate
     */
    public function sendRejectionEmail(Candidate $candidate, Vacancy $vacancy, string $reason)
    {
        // Get the rejection email template
        $template = EmailTemplate::where('type', 'rejection')
            ->where('is_active', true)
            ->where(function($q) use ($vacancy) {
                $q->whereNull('company_id')
                  ->orWhere('company_id', $vacancy->company_id);
            })
            ->orderBy('company_id', 'desc') // Prefer company-specific template
            ->first();

        if (!$template) {
            throw new \Exception('Geen actieve afwijzings e-mail template gevonden.');
        }

        // Prepare variables
        $variables = [
            'CANDIDATE_NAME' => $candidate->first_name . ' ' . $candidate->last_name,
            'CANDIDATE_FIRST_NAME' => $candidate->first_name,
            'CANDIDATE_LAST_NAME' => $candidate->last_name,
            'CANDIDATE_EMAIL' => $candidate->email,
            'COMPANY_NAME' => $vacancy->company->name ?? 'Ons bedrijf',
            'VACANCY_TITLE' => $vacancy->title,
            'REJECTION_REASON' => $reason,
            'VACANCY_REFERENCE' => $vacancy->reference_number ?? '',
        ];

        // Parse template
        $subject = $this->parseTemplate($template->subject, $variables);
        $htmlContent = $this->parseTemplate($template->html_content, $variables);
        $textContent = $template->text_content ? $this->parseTemplate($template->text_content, $variables) : strip_tags($htmlContent);

        // Send email using send method with raw content
        Mail::send([], [], function ($message) use ($candidate, $subject, $htmlContent, $textContent) {
            $message->to($candidate->email, $candidate->first_name . ' ' . $candidate->last_name)
                    ->subject($subject);
            
            // Set HTML body
            if ($htmlContent) {
                $message->html($htmlContent);
            }
            
            // Add plain text alternative
            if ($textContent) {
                $message->text($textContent);
            }
        });
    }

    /**
     * Send interview update email to candidate
     */
    public function sendInterviewUpdateEmail(Candidate $candidate, Vacancy $vacancy, array $changes)
    {
        // Get the interview update email template
        $template = EmailTemplate::where('type', 'interview_update')
            ->where('is_active', true)
            ->where(function($q) use ($vacancy) {
                $q->whereNull('company_id')
                  ->orWhere('company_id', $vacancy->company_id);
            })
            ->orderBy('company_id', 'desc') // Prefer company-specific template
            ->first();

        // If no template exists, create a default message
        if (!$template) {
            $subject = 'Interview aangepast - ' . $vacancy->title;
            $htmlContent = '<p>Beste ' . htmlspecialchars($candidate->first_name) . ',</p>';
            $htmlContent .= '<p>Er zijn wijzigingen doorgevoerd in uw geplande interview voor de vacature "' . htmlspecialchars($vacancy->title) . '".</p>';
            $htmlContent .= '<p><strong>Wijzigingen:</strong></p><ul>';
            foreach ($changes as $change) {
                $htmlContent .= '<li>' . htmlspecialchars($change) . '</li>';
            }
            $htmlContent .= '</ul>';
            $htmlContent .= '<p>Met vriendelijke groet,<br>' . htmlspecialchars($vacancy->company->name ?? 'Ons bedrijf') . '</p>';
            $textContent = strip_tags($htmlContent);
        } else {
            // Prepare variables
            $variables = [
                'CANDIDATE_NAME' => $candidate->first_name . ' ' . $candidate->last_name,
                'CANDIDATE_FIRST_NAME' => $candidate->first_name,
                'CANDIDATE_LAST_NAME' => $candidate->last_name,
                'CANDIDATE_EMAIL' => $candidate->email,
                'COMPANY_NAME' => $vacancy->company->name ?? 'Ons bedrijf',
                'VACANCY_TITLE' => $vacancy->title,
                'CHANGES' => '<ul><li>' . implode('</li><li>', array_map('htmlspecialchars', $changes)) . '</li></ul>',
                'CHANGES_TEXT' => implode("\n", $changes),
                'VACANCY_REFERENCE' => $vacancy->reference_number ?? '',
            ];

            // Parse template
            $subject = $this->parseTemplate($template->subject, $variables);
            $htmlContent = $this->parseTemplate($template->html_content, $variables);
            $textContent = $template->text_content ? $this->parseTemplate($template->text_content, $variables) : strip_tags($htmlContent);
        }

        // Send email
        Mail::send([], [], function ($message) use ($candidate, $subject, $htmlContent, $textContent) {
            $message->to($candidate->email, $candidate->first_name . ' ' . $candidate->last_name)
                    ->subject($subject);
            
            if ($htmlContent) {
                $message->html($htmlContent);
            }
            
            if ($textContent) {
                $message->text($textContent);
            }
        });
    }

    /**
     * Send a test email using an email template with custom variables.
     *
     * @param  string|null  $fromEmail  Optioneel From-adres (bijv. ingelogde gebruiker); voorkomt SMTP 550 "not authorized to send on behalf of"
     * @param  string|null  $fromName   Optioneel From-naam
     */
    public function sendTestEmail(EmailTemplate $template, string $toEmail, string $toName, array $variables = [], ?string $fromEmail = null, ?string $fromName = null): void
    {
        $logoCompanyId = $template->company_id
            ?? (function_exists('auth') && auth()->check() ? auth()->user()->company_id : null);
        $logoCompanyId = $logoCompanyId ? (int) $logoCompanyId : null;
        $defaultCompanyName = $template->company?->name ?? 'Ons bedrijf';

        $defaults = [
            'USER_NAME' => $toName ?: $toEmail,
            'USER_EMAIL' => $toEmail,
            'USER_FIRST_NAME' => $variables['VOORNAAM'] ?? '',
            'USER_LAST_NAME' => $variables['ACHTERNAAM'] ?? '',
            'COMPANY_NAME' => $defaultCompanyName,
            'NOTIFICATION_TITLE' => $template->subject,
            'NOTIFICATION_MESSAGE' => $variables['OMSCHRIJVING'] ?? '',
            'ACTION_URL' => '',
            'EMAIL_AANVRAAG' => $variables['EMAIL_AANVRAAG'] ?? $toEmail,
            'DATUM_AANVRAAG' => $variables['DATUM_AANVRAAG'] ?? now()->format('d-m-Y H:i'),
        ];
        if (! array_key_exists('COMPANY_LOGO', $variables)) {
            $defaults = array_merge(
                $defaults,
                app(CompanyEmailLogoService::class)->templateVariable($logoCompanyId, $defaultCompanyName)
            );
        }
        $merged = array_merge($defaults, $variables);

        $subject = $this->parseTemplate($template->subject ?? '', $merged);
        $htmlContent = $template->html_content ?? '';
        if ($template->type === 'informatieaanvraag') {
            $htmlContent = str_replace('{{ DYNAMIC_FORM_FIELDS }}', $template->renderDynamicFormFieldsHtml(), $htmlContent);
        }
        $htmlContent = $this->parseTemplate($htmlContent, $merged);
        if ($template->type === 'informatieaanvraag' && $htmlContent !== '') {
            $htmlContent = '<style>.info-request-email-body, .info-request-email-body table, .info-request-email-body td, .info-request-email-body th, .info-request-email-body p, .info-request-email-body div { text-align: left !important; }</style>'
                . '<div class="info-request-email-body" style="text-align: left !important; background-color: #f3f4f6 !important; padding: 1rem;">' . $htmlContent . '</div>';
        }
        $textContent = $template->text_content
            ? $this->parseTemplate($template->text_content, $merged)
            : strip_tags($htmlContent);

        $logoService = app(CompanyEmailLogoService::class);

        Mail::send([], [], function ($message) use (
            $toEmail,
            $toName,
            $subject,
            $htmlContent,
            $textContent,
            $fromEmail,
            $fromName,
            $logoCompanyId,
            $defaultCompanyName,
            $logoService
        ) {
            if ($fromEmail) {
                $message->from($fromEmail, $fromName ?: $fromEmail);
            }
            $message->to($toEmail, $toName)->subject($subject);
            if ($htmlContent) {
                $htmlBody = $logoService->embedInHtml(
                    $htmlContent,
                    $message,
                    $logoCompanyId,
                    $defaultCompanyName
                );
                $message->html($htmlBody);
            }
            if ($textContent) {
                $message->text($textContent);
            }
        });
    }

    /**
     * Parse template with variables
     * Supports {VAR}, {{VAR}}, { VAR } and {{ VAR }} (with spaces) for email client compatibility.
     * Removes one level of surrounding single curly braces so output is not wrapped in { value }.
     */
    public function parseTemplateVariables(string $template, array $variables): string
    {
        return $this->parseTemplate($template, $variables);
    }

    private function parseTemplate(string $template, array $variables): string
    {
        $result = $template;

        foreach ($variables as $key => $value) {
            $search = [
                '{' . $key . '}',
                '{{' . $key . '}}',
                '{ ' . $key . ' }',
                '{{ ' . $key . ' }}',
            ];
            $result = str_replace($search, $value, $result);
        }

        // Verwijder overgebleven { } om waarden (bv. door letterlijke braces in template)
        $result = preg_replace('#\{\s*([^{}]*)\s*\}#u', '$1', $result);

        return $result;
    }

    /**
     * Send reactivation email to candidate and interviewer
     */
    public function sendInterviewReactivationEmail(Candidate $candidate, Vacancy $vacancy, $interview)
    {
        // Get the interview reactivation email template (or use update template as fallback)
        $template = EmailTemplate::where('type', 'interview_reactivation')
            ->where('is_active', true)
            ->where(function($q) use ($vacancy) {
                $q->whereNull('company_id')
                  ->orWhere('company_id', $vacancy->company_id);
            })
            ->orderBy('company_id', 'desc')
            ->first();

        // Fallback to interview update template if reactivation template doesn't exist
        if (!$template) {
            $template = EmailTemplate::where('type', 'interview_update')
                ->where('is_active', true)
                ->where(function($q) use ($vacancy) {
                    $q->whereNull('company_id')
                      ->orWhere('company_id', $vacancy->company_id);
                })
                ->orderBy('company_id', 'desc')
                ->first();
        }

        if (!$template) {
            throw new \Exception('Geen actieve interview e-mail template gevonden.');
        }

        // Prepare interview details
        $interviewDate = $interview->scheduled_at ? $interview->scheduled_at->format('d-m-Y') : 'Niet opgegeven';
        $interviewTime = $interview->scheduled_at ? $interview->scheduled_at->format('H:i') : 'Niet opgegeven';
        
        $typeMap = [
            'phone' => 'Telefoon',
            'video' => 'Video',
            'onsite' => 'Op locatie',
            'assessment' => 'Assessment',
            'final' => 'Eindgesprek',
        ];
        $interviewType = $typeMap[$interview->type] ?? ucfirst($interview->type);

        // Prepare variables
        $variables = [
            'CANDIDATE_NAME' => $candidate->first_name . ' ' . $candidate->last_name,
            'CANDIDATE_FIRST_NAME' => $candidate->first_name,
            'CANDIDATE_LAST_NAME' => $candidate->last_name,
            'CANDIDATE_EMAIL' => $candidate->email,
            'COMPANY_NAME' => $vacancy->company->name ?? 'Ons bedrijf',
            'VACANCY_TITLE' => $vacancy->title,
            'INTERVIEW_DATE' => $interviewDate,
            'INTERVIEW_TIME' => $interviewTime,
            'INTERVIEW_TYPE' => $interviewType,
            'INTERVIEW_LOCATION' => $interview->location ?? 'Niet opgegeven',
            'INTERVIEW_DURATION' => $interview->duration ?? 60,
            'INTERVIEWER_NAME' => $interview->interviewer_name ?? 'Niet opgegeven',
            'INTERVIEWER_EMAIL' => $interview->interviewer_email ?? 'Niet opgegeven',
        ];

        // Parse template
        $subject = $this->parseTemplate($template->subject, $variables);
        $htmlContent = $this->parseTemplate($template->html_content, $variables);
        $textContent = $template->text_content ? $this->parseTemplate($template->text_content, $variables) : strip_tags($htmlContent);

        // Send email to candidate
        Mail::send([], [], function ($message) use ($candidate, $subject, $htmlContent, $textContent) {
            $message->to($candidate->email, $candidate->first_name . ' ' . $candidate->last_name)
                    ->subject($subject)
                    ->html($htmlContent)
                    ->text($textContent);
        });

        // Send email to interviewer if email is provided
        if ($interview->interviewer_email && $interview->interviewer_email !== $candidate->email) {
            Mail::send([], [], function ($message) use ($interview, $subject, $htmlContent, $textContent) {
                $message->to($interview->interviewer_email, $interview->interviewer_name ?? 'Interviewer')
                        ->subject($subject)
                        ->html($htmlContent)
                        ->text($textContent);
            });
        }

        return true;
    }

    /**
     * Send interview confirmation email to candidate when interview is scheduled
     */
    public function sendInterviewScheduledEmail(Candidate $candidate, Vacancy $vacancy, $interview)
    {
        // Try to find template specific to interview type (e.g., interview_phone, interview_video)
        $interviewTypeTemplate = 'interview_' . $interview->type;
        $template = EmailTemplate::where('type', $interviewTypeTemplate)
            ->where('is_active', true)
            ->where(function($q) use ($vacancy) {
                $q->whereNull('company_id')
                  ->orWhere('company_id', $vacancy->company_id);
            })
            ->orderBy('company_id', 'desc') // Prefer company-specific template
            ->first();

        // Fallback to general interview template if type-specific template doesn't exist
        if (!$template) {
            $template = EmailTemplate::where('type', 'interview')
                ->where('is_active', true)
                ->where(function($q) use ($vacancy) {
                    $q->whereNull('company_id')
                      ->orWhere('company_id', $vacancy->company_id);
                })
                ->orderBy('company_id', 'desc') // Prefer company-specific template
                ->first();
        }

        // If still no template, use interview_confirmed as fallback
        if (!$template) {
            $template = EmailTemplate::where('type', 'interview_confirmed')
                ->where('is_active', true)
                ->where(function($q) use ($vacancy) {
                    $q->whereNull('company_id')
                      ->orWhere('company_id', $vacancy->company_id);
                })
                ->orderBy('company_id', 'desc')
                ->first();
        }

        // If no template exists at all, create a default message
        if (!$template) {
            \Log::warning('No active interview email template found', [
                'interview_type' => $interview->type,
                'company_id' => $vacancy->company_id,
            ]);
            return false;
        }

        // Prepare interview details
        $interviewDate = $interview->scheduled_at ? $interview->scheduled_at->format('d-m-Y') : 'Niet opgegeven';
        $interviewTime = $interview->scheduled_at ? $interview->scheduled_at->format('H:i') : 'Niet opgegeven';
        
        $typeMap = [
            'phone' => 'Telefoon',
            'video' => 'Video',
            'onsite' => 'Op locatie',
            'assessment' => 'Assessment',
            'final' => 'Eindgesprek',
        ];
        $interviewType = $typeMap[$interview->type] ?? ucfirst($interview->type);

        // Determine location display
        $locationDisplay = $interview->location ?? 'Niet opgegeven';
        if (empty($interview->location) && $interview->type === 'phone') {
            $locationDisplay = 'Op afstand';
        }

        // Prepare variables
        $variables = [
            'CANDIDATE_NAME' => $candidate->first_name . ' ' . $candidate->last_name,
            'CANDIDATE_FIRST_NAME' => $candidate->first_name,
            'CANDIDATE_LAST_NAME' => $candidate->last_name,
            'CANDIDATE_EMAIL' => $candidate->email,
            'COMPANY_NAME' => $vacancy->company->name ?? 'Ons bedrijf',
            'VACANCY_TITLE' => $vacancy->title,
            'INTERVIEW_DATE' => $interviewDate,
            'INTERVIEW_TIME' => $interviewTime,
            'INTERVIEW_TYPE' => $interviewType,
            'INTERVIEW_LOCATION' => $locationDisplay,
            'INTERVIEW_DURATION' => $interview->duration ?? 60,
            'INTERVIEWER_NAME' => $interview->interviewer_name ?? 'Niet opgegeven',
            'INTERVIEWER_EMAIL' => $interview->interviewer_email ?? 'Niet opgegeven',
            'VACANCY_REFERENCE' => $vacancy->reference_number ?? '',
        ];

        // Parse template
        $subject = $this->parseTemplate($template->subject, $variables);
        $htmlContent = $this->parseTemplate($template->html_content, $variables);
        $textContent = $template->text_content ? $this->parseTemplate($template->text_content, $variables) : strip_tags($htmlContent);

        // Send email to candidate
        try {
            Mail::send([], [], function ($message) use ($candidate, $subject, $htmlContent, $textContent) {
                $message->to($candidate->email, $candidate->first_name . ' ' . $candidate->last_name)
                        ->subject($subject);
                
                if ($htmlContent) {
                    $message->html($htmlContent);
                }
                
                if ($textContent) {
                    $message->text($textContent);
                }
            });

            \Log::info('Interview scheduled email sent to candidate', [
                'candidate_email' => $candidate->email,
                'interview_id' => $interview->id,
                'template_type' => $template->type,
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send interview scheduled email', [
                'candidate_email' => $candidate->email,
                'interview_id' => $interview->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Send email based on notification with email template
     */
    public function sendNotificationEmail(\App\Models\Notification $notification)
    {
        if (!$notification->email_template_id) {
            return false; // No email template selected
        }

        $template = EmailTemplate::find($notification->email_template_id);
        if (!$template || !$template->is_active) {
            \Log::warning('Email template not found or inactive', [
                'notification_id' => $notification->id,
                'email_template_id' => $notification->email_template_id,
            ]);
            return false;
        }

        $user = $notification->user;
        if (!$user || !$user->email) {
            \Log::warning('User not found or no email address', [
                'notification_id' => $notification->id,
                'user_id' => $notification->user_id,
            ]);
            return false;
        }

        // Prepare variables from notification and user data
        $variables = [
            'USER_NAME' => $user->first_name . ' ' . $user->last_name,
            'USER_FIRST_NAME' => $user->first_name,
            'USER_LAST_NAME' => $user->last_name,
            'USER_EMAIL' => $user->email,
            'NOTIFICATION_TITLE' => $notification->title,
            'NOTIFICATION_MESSAGE' => $notification->message,
            'NOTIFICATION_TYPE' => $notification->type,
            'NOTIFICATION_CATEGORY' => $notification->category,
            'ACTION_URL' => $notification->action_url ?? '',
            'COMPANY_NAME' => $notification->company->name ?? 'Ons bedrijf',
        ];

        // Add company name if available
        if ($notification->company) {
            $variables['COMPANY_NAME'] = $notification->company->name;
        }

        // Try to get additional context from notification data
        if ($notification->data) {
            $data = json_decode($notification->data, true);
            if (is_array($data)) {
                // Add match_id related info if available
                if (isset($data['match_id']) && $data['match_id']) {
                    $match = \App\Models\JobMatch::with(['vacancy', 'candidate'])->find($data['match_id']);
                    if ($match) {
                        if ($match->vacancy) {
                            $variables['VACANCY_TITLE'] = $match->vacancy->title;
                            $variables['VACANCY_REFERENCE'] = $match->vacancy->reference_number ?? '';
                            if ($match->vacancy->company) {
                                $variables['COMPANY_NAME'] = $match->vacancy->company->name;
                            }
                        }
                        if ($match->candidate) {
                            $variables['CANDIDATE_NAME'] = $match->candidate->first_name . ' ' . $match->candidate->last_name;
                            $variables['CANDIDATE_FIRST_NAME'] = $match->candidate->first_name;
                            $variables['CANDIDATE_LAST_NAME'] = $match->candidate->last_name;
                            $variables['CANDIDATE_EMAIL'] = $match->candidate->email;
                        }
                    }
                }
            }
        }

        // Parse template
        $subject = $this->parseTemplate($template->subject, $variables);
        $htmlContent = $this->parseTemplate($template->html_content, $variables);
        $textContent = $template->text_content ? $this->parseTemplate($template->text_content, $variables) : strip_tags($htmlContent);

        // Send email
        try {
            Mail::send([], [], function ($message) use ($user, $subject, $htmlContent, $textContent) {
                $message->to($user->email, $user->first_name . ' ' . $user->last_name)
                        ->subject($subject);
                
                if ($htmlContent) {
                    $message->html($htmlContent);
                }
                
                if ($textContent) {
                    $message->text($textContent);
                }
            });

            \Log::info('Notification email sent', [
                'notification_id' => $notification->id,
                'user_email' => $user->email,
                'template_type' => $template->type,
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to send notification email', [
                'notification_id' => $notification->id,
                'user_email' => $user->email,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

