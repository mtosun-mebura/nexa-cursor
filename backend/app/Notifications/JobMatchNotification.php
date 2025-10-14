<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Vacancy;

class JobMatchNotification extends Notification
{
    use Queueable;

    protected $vacancy;

    /**
     * Create a new notification instance.
     */
    public function __construct(Vacancy $vacancy)
    {
        $this->vacancy = $vacancy;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];
        
        // Add SMS if user has phone and SMS notifications enabled
        if ($notifiable->phone && $notifiable->sms_notifications) {
            $channels[] = 'sms';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Nieuwe job match gevonden!')
                    ->greeting('Hallo ' . $notifiable->first_name . '!')
                    ->line('We hebben een nieuwe vacature gevonden die perfect bij jouw profiel past.')
                    ->line('**Vacature:** ' . $this->vacancy->title)
                    ->line('**Bedrijf:** ' . $this->vacancy->company->name)
                    ->line('**Locatie:** ' . $this->vacancy->location)
                    ->line('**Salaris:** €' . number_format($this->vacancy->salary_min) . ' - €' . number_format($this->vacancy->salary_max))
                    ->action('Bekijk vacature', route('frontend.vacancy-details', $this->vacancy->id))
                    ->line('Bedankt voor het gebruik van NEXA Skillmatching!');
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): string
    {
        return "Nieuwe job match! {$this->vacancy->title} bij {$this->vacancy->company->name} in {$this->vacancy->location}. Bekijk: " . route('frontend.vacancy-details', $this->vacancy->id);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'vacancy_id' => $this->vacancy->id,
            'vacancy_title' => $this->vacancy->title,
            'company_name' => $this->vacancy->company->name,
        ];
    }
}
