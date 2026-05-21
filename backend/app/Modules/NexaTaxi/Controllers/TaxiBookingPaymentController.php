<?php

namespace App\Modules\NexaTaxi\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxiBookingPaymentController extends Controller
{
    public function returnPage(
        Request $request,
        ModuleDatabaseService $moduleDb,
        TaxiRidePaymentService $payments
    ): View {
        $conn = $moduleDb->getModuleConnectionName('taxi');
        $rideId = (int) $request->query('ride', 0);

        $ride = $rideId > 0
            ? RideRequest::on($conn)->find($rideId)
            : null;

        if ($ride) {
            $latestPayment = $ride->payments()->orderByDesc('id')->first();
            if ($latestPayment && $latestPayment->status === 'open') {
                $payments->syncRidePaymentFromMollie($conn, $latestPayment);
                $ride = $ride->fresh();
            }
        }

        $paid = $ride && $ride->payment_status === RideRequest::PAYMENT_STATUS_PAID;

        return view('taxi::booking.payment-return', [
            'paid' => $paid,
            'rideId' => $rideId,
        ]);
    }
}
