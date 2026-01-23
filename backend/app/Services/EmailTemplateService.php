<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Models\Candidate;
use App\Models\Vacancy;
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
     * Parse template with variables
     */
    private function parseTemplate(string $template, array $variables): string
    {
        $result = $template;
        
        foreach ($variables as $key => $value) {
            // Support both {VAR} and {{VAR}} syntax
            $result = str_replace(['{' . $key . '}', '{{' . $key . '}}'], $value, $result);
        }
        
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
}

