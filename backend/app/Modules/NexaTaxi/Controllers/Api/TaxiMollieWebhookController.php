<?php

namespace App\Modules\NexaTaxi\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaxiMollieWebhookController extends Controller
{
    public function __invoke(Request $request, TaxiRidePaymentService $payments): Response
    {
        $paymentId = $request->input('id');
        if (! is_string($paymentId) || $paymentId === '') {
            return response('', 200);
        }

        // 503: Mollie probeert opnieuw. De rit-betaling staat soms nog in de
        // transactie die de checkout aanmaakt, en een 200 zou die webhook weggooien.
        if (! $payments->handleWebhookPaymentId($paymentId)) {
            return response('', 503);
        }

        return response('', 200);
    }
}
