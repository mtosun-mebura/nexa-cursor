<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PlatformBilling\TenantCustomerInvoicePaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TenantCustomerMollieWebhookController extends Controller
{
    public function __invoke(Request $request, TenantCustomerInvoicePaymentService $payments): Response
    {
        $paymentId = trim((string) ($request->input('id') ?? ''));
        if ($paymentId === '') {
            return response('', 400);
        }

        $payments->syncPaymentFromMollie($paymentId);

        return response('', 200);
    }
}
