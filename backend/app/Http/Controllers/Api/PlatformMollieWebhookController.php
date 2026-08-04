<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PlatformBilling\PlatformBillingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlatformMollieWebhookController extends Controller
{
    public function __invoke(Request $request, PlatformBillingService $billing): Response
    {
        $paymentId = (string) ($request->input('id') ?? '');
        if ($paymentId === '') {
            return response('', 400);
        }

        $billing->syncPaymentFromMollie($paymentId);

        return response('', 200);
    }
}
