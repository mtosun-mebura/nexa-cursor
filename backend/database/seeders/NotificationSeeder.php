<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Haal alle gebruikers op behalve super admin
        $users = User::whereDoesntHave('roles', function($q) {
            $q->where('name', 'super-admin');
        })->get();

        if ($users->isEmpty()) {
            $this->command->error('Geen gebruikers gevonden (behalve super admin)!');
            return;
        }

        $this->command->info('Aanmaken van notificaties voor ' . $users->count() . ' gebruikers...');

        $notificationTemplates = [
            [
                'title' => 'Welkom bij het platform!',
                'message' => 'Welkom bij ons skillmatching platform. We zijn blij dat je je hebt aangemeld. Bekijk de beschikbare vacatures en begin met het matchen van je vaardigheden.',
                'type' => 'welcome',
                'priority' => 'medium'
            ],
            [
                'title' => 'Nieuwe vacature beschikbaar',
                'message' => 'Er is een nieuwe vacature beschikbaar die mogelijk interessant voor je is. Bekijk de details en solliciteer als je geïnteresseerd bent.',
                'type' => 'vacancy',
                'priority' => 'high'
            ],
            [
                'title' => 'Interview gepland',
                'message' => 'Je hebt een interview gepland voor de functie waar je op hebt gesolliciteerd. Controleer de details en bereid je voor op het gesprek.',
                'type' => 'interview',
                'priority' => 'urgent'
            ],
            [
                'title' => 'Profiel update aanbevolen',
                'message' => 'Je profiel kan worden verbeterd met meer details over je ervaring en vaardigheden. Dit helpt bij het vinden van betere matches.',
                'type' => 'profile',
                'priority' => 'low'
            ],
            [
                'title' => 'Systeem onderhoud',
                'message' => 'Er wordt gepland onderhoud uitgevoerd op het platform. Tijdens deze periode kan de service tijdelijk niet beschikbaar zijn.',
                'type' => 'system',
                'priority' => 'medium'
            ],
            [
                'title' => 'Nieuwe functies beschikbaar',
                'message' => 'We hebben nieuwe functies toegevoegd aan het platform. Bekijk de updates en ontdek wat er nieuw is.',
                'type' => 'feature',
                'priority' => 'low'
            ]
        ];

        $createdCount = 0;

        foreach ($users as $user) {
            // Maak 3 notificaties per gebruiker
            $selectedNotifications = collect($notificationTemplates)->random(3);
            
            foreach ($selectedNotifications as $template) {
                // Bepaal of de notificatie gelezen is (70% kans dat het gelezen is)
                $isRead = rand(1, 10) <= 7;
                
                $notification = Notification::create([
                    'title' => $template['title'],
                    'message' => $template['message'],
                    'type' => $template['type'],
                    'priority' => $template['priority'],
                    'user_id' => $user->id,
                    'company_id' => $user->company_id,
                    'read_at' => $isRead ? Carbon::now()->subDays(rand(1, 7)) : null,
                    'created_at' => Carbon::now()->subDays(rand(1, 14))
                ]);

                $createdCount++;
            }
        }

        $this->command->info("✓ {$createdCount} notificaties succesvol aangemaakt voor " . $users->count() . " gebruikers!");
        
        // Toon statistieken
        $totalNotifications = Notification::count();
        $readNotifications = Notification::whereNotNull('read_at')->count();
        $unreadNotifications = $totalNotifications - $readNotifications;
        
        $this->command->info("📊 Statistieken:");
        $this->command->info("   - Totaal notificaties: {$totalNotifications}");
        $this->command->info("   - Gelezen: {$readNotifications}");
        $this->command->info("   - Ongelezen: {$unreadNotifications}");
    }
}