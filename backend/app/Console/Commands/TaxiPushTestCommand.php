<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Modules\NexaTaxi\Services\DriverExpoPushService;
use Illuminate\Console\Command;

class TaxiPushTestCommand extends Command
{
    protected $signature = 'taxi:push-test {email : E-mail van de chauffeur}';

    protected $description = 'Stuurt een testpush naar de geregistreerde apparaten van een chauffeur.';

    public function handle(DriverExpoPushService $push): int
    {
        $email = trim((string) $this->argument('email'));
        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $this->error('Geen gebruiker met dat e-mailadres.');

            return self::FAILURE;
        }

        $result = $push->sendTest((int) $user->id);
        $this->line('Apparaten: '.$result['devices']);
        if ($result['error']) {
            $this->warn('Versturen mislukt ('.$result['error'].').');
        }
        foreach ($result['tickets'] as $ticket) {
            $line = 'Ticket: '.$ticket['status'];
            if ($ticket['error']) {
                $line .= ' ('.$ticket['error'].')';
            }
            $this->line($line);
        }

        return $result['error'] ? self::FAILURE : self::SUCCESS;
    }
}
