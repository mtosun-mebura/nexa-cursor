<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GeneralSetting;
use App\Modules\NexaTaxi\Services\TaxiPickupProposalService;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Meta WhatsApp Cloud API webhook.
 *
 * Callback URL: https://<platform-host>/api/whatsapp/webhook
 * Verify token: zelfde waarde als WHATSAPP_WEBHOOK_VERIFY_TOKEN (Algemene configuraties).
 */
class WhatsAppWebhookController extends Controller
{
    /**
     * Meta subscription verification (GET).
     */
    public function verify(Request $request): Response
    {
        $mode = (string) $request->query('hub_mode', $request->query('hub.mode', ''));
        $token = (string) $request->query('hub_verify_token', $request->query('hub.verify_token', ''));
        $challenge = (string) $request->query('hub_challenge', $request->query('hub.challenge', ''));

        $expected = trim((string) GeneralSetting::get('WHATSAPP_WEBHOOK_VERIFY_TOKEN', ''));

        if ($mode === 'subscribe' && $expected !== '' && hash_equals($expected, $token)) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('WhatsApp webhook verificatie geweigerd.', [
            'mode' => $mode,
            'token_present' => $token !== '',
            'expected_configured' => $expected !== '',
        ]);

        return response('Forbidden', 403)->header('Content-Type', 'text/plain');
    }

    /**
     * Incoming message / status events (POST). Acknowledge quickly; process pickup proposal replies.
     */
    public function handle(
        Request $request,
        ModuleDatabaseService $moduleDb,
        TaxiPickupProposalService $pickupProposals
    ): Response {
        try {
            $entries = $request->input('entry');
            if (is_array($entries)) {
                $conn = $moduleDb->getModuleConnectionName('taxi');
                foreach ($entries as $entry) {
                    $changes = $entry['changes'] ?? [];
                    if (! is_array($changes)) {
                        continue;
                    }
                    foreach ($changes as $change) {
                        $value = $change['value'] ?? [];
                        $messages = $value['messages'] ?? [];
                        if (! is_array($messages)) {
                            continue;
                        }
                        foreach ($messages as $message) {
                            if (! is_array($message)) {
                                continue;
                            }
                            $pickupProposals->handleInboundCustomerMessage($conn, $message);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('WhatsApp webhook verwerking mislukt.', [
                'error' => $e->getMessage(),
            ]);
        }

        if (config('app.debug')) {
            Log::debug('WhatsApp webhook ontvangen.', [
                'object' => $request->input('object'),
                'entry_count' => is_array($request->input('entry')) ? count($request->input('entry')) : 0,
            ]);
        }

        return response('EVENT_RECEIVED', 200)->header('Content-Type', 'text/plain');
    }
}
