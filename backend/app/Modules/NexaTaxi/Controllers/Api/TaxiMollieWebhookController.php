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
        if (is_string($paymentId) && $paymentId !== '') {
            $payments->handleWebhookPaymentId($paymentId);
        }

        return response('', 200);
    }
}
