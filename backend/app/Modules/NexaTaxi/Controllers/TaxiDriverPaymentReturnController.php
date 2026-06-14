<?php

namespace App\Modules\NexaTaxi\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\NexaTaxi\Models\RidePayment;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Services\TaxiRidePaymentService;
use App\Services\ModuleDatabaseService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaxiDriverPaymentReturnController extends Controller
{
    public function __invoke(
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
            $openPayment = RidePayment::on($conn)
                ->where('ride_request_id', $ride->id)
                ->where('channel', RidePayment::CHANNEL_DRIVER)
                ->orderByDesc('id')
                ->first();

            if ($openPayment) {
                $payments->syncRidePaymentFromMollie($conn, $openPayment);
                $ride = $ride->fresh();
            }
        }

        $paid = $ride && $ride->payment_status === RideRequest::PAYMENT_STATUS_PAID;

        return view('taxi::driver-app.payment-return', [
            'paid' => $paid,
        ]);
    }
}
