<?php

namespace App\Modules\NexaTaxi\Services;

use App\Models\Invoice;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\RideStop;
use App\Modules\NexaTaxi\Models\TransportAnnouncement;
use App\Modules\NexaTaxi\Models\TransportAssignment;
use App\Modules\NexaTaxi\Models\TransportContract;
use App\Modules\NexaTaxi\Models\TransportCustomer;
use App\Modules\NexaTaxi\Models\TransportCustomerPortalUser;
use App\Modules\NexaTaxi\Models\TransportGroup;
use App\Modules\NexaTaxi\Models\TransportGroupMember;
use App\Modules\NexaTaxi\Models\TransportIndividualBooking;
use App\Modules\NexaTaxi\Models\TransportOccurrence;
use App\Modules\NexaTaxi\Models\TransportPassenger;
use App\Modules\NexaTaxi\Models\TransportPassengerAbsence;
use App\Modules\NexaTaxi\Models\TransportPassengerGuardian;
use App\Modules\NexaTaxi\Models\TransportPaymentMandate;
use App\Modules\NexaTaxi\Models\TransportRouteStop;
use App\Modules\NexaTaxi\Models\TransportRouteTemplate;
use App\Modules\NexaTaxi\Models\TransportScheduleException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Hard-delete a transport customer and all nested contract data
 * (abonnementen, passagiers, groepen, routes, portal, etc.).
 */
final class TransportCustomerCascadeDeleteService
{
    public function delete(string $conn, TransportCustomer $customer): void
    {
        DB::connection($conn)->transaction(function () use ($conn, $customer) {
            $contractIds = TransportContract::on($conn)
                ->where('transport_customer_id', $customer->id)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($contractIds !== []) {
                $this->deleteContractsCascade($conn, $contractIds);
            }

            if ($this->hasTable($conn, 'transport_announcements')) {
                TransportAnnouncement::on($conn)
                    ->where('transport_customer_id', $customer->id)
                    ->delete();
            }

            if ($this->hasTable($conn, 'transport_customer_portal_users')) {
                TransportCustomerPortalUser::on($conn)
                    ->where('transport_customer_id', $customer->id)
                    ->delete();
            }

            $customer->delete();
        });
    }

    /**
     * @param  list<int>  $contractIds
     */
    public function deleteContractsCascade(string $conn, array $contractIds): void
    {
        $contractIds = array_values(array_unique(array_filter(array_map('intval', $contractIds))));
        if ($contractIds === []) {
            return;
        }

        $passengerIds = TransportPassenger::on($conn)
            ->whereIn('transport_contract_id', $contractIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $groupIds = TransportGroup::on($conn)
            ->whereIn('transport_contract_id', $contractIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $routeTemplateIds = $groupIds === []
            ? []
            : TransportRouteTemplate::on($conn)
                ->whereIn('transport_group_id', $groupIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

        $bookingIds = TransportIndividualBooking::on($conn)
            ->whereIn('transport_contract_id', $contractIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($routeTemplateIds !== []) {
            TransportRouteStop::on($conn)
                ->whereIn('transport_route_template_id', $routeTemplateIds)
                ->delete();

            TransportAssignment::on($conn)
                ->where('assignable_type', TransportRouteTemplate::ASSIGNABLE_TYPE)
                ->whereIn('assignable_id', $routeTemplateIds)
                ->delete();

            TransportRouteTemplate::on($conn)
                ->whereIn('id', $routeTemplateIds)
                ->delete();
        }

        if ($bookingIds !== []) {
            TransportAssignment::on($conn)
                ->where('assignable_type', TransportAssignment::ASSIGNABLE_INDIVIDUAL_BOOKING)
                ->whereIn('assignable_id', $bookingIds)
                ->delete();
        }

        if ($groupIds !== []) {
            TransportGroupMember::on($conn)
                ->whereIn('transport_group_id', $groupIds)
                ->delete();

            TransportGroup::on($conn)
                ->whereIn('id', $groupIds)
                ->delete();
        }

        if ($passengerIds !== []) {
            if ($this->hasTable($conn, 'transport_passenger_absences')) {
                TransportPassengerAbsence::on($conn)
                    ->whereIn('transport_passenger_id', $passengerIds)
                    ->delete();
            }

            if ($this->hasTable($conn, 'transport_passenger_guardians')) {
                TransportPassengerGuardian::on($conn)
                    ->whereIn('transport_passenger_id', $passengerIds)
                    ->delete();
            }

            TransportGroupMember::on($conn)
                ->whereIn('transport_passenger_id', $passengerIds)
                ->delete();

            if ($this->hasTable($conn, 'ride_stops')) {
                RideStop::on($conn)
                    ->whereIn('transport_passenger_id', $passengerIds)
                    ->update(['transport_passenger_id' => null]);
            }
        }

        TransportOccurrence::on($conn)
            ->whereIn('transport_contract_id', $contractIds)
            ->delete();

        TransportIndividualBooking::on($conn)
            ->whereIn('transport_contract_id', $contractIds)
            ->delete();

        TransportPassenger::on($conn)
            ->whereIn('transport_contract_id', $contractIds)
            ->delete();

        TransportPaymentMandate::on($conn)
            ->whereIn('transport_contract_id', $contractIds)
            ->delete();

        if ($this->hasTable($conn, 'transport_schedule_exceptions')) {
            TransportScheduleException::on($conn)
                ->whereIn('transport_contract_id', $contractIds)
                ->delete();
        }

        if ($this->hasTable($conn, 'ride_requests')) {
            RideRequest::on($conn)
                ->whereIn('transport_contract_id', $contractIds)
                ->update([
                    'transport_contract_id' => null,
                    'transport_occurrence_id' => null,
                    'transport_passenger_id' => null,
                ]);
        }

        $this->deleteDraftInvoicesForContracts($contractIds);

        TransportContract::on($conn)
            ->whereIn('id', $contractIds)
            ->delete();
    }

    /**
     * @param  list<int>  $contractIds
     */
    private function deleteDraftInvoicesForContracts(array $contractIds): void
    {
        if ($contractIds === []) {
            return;
        }

        if (! Schema::hasTable((new Invoice)->getTable())) {
            return;
        }

        $invoices = Invoice::query()
            ->where('module', Invoice::MODULE_TAXI_CONTRACT)
            ->whereIn('module_reference_id', $contractIds)
            ->where('status', 'draft')
            ->get();

        foreach ($invoices as $invoice) {
            if ($invoice->pdf_path) {
                Storage::disk('local')->delete($invoice->pdf_path);
            }
            $invoice->delete();
        }
    }

    private function hasTable(string $conn, string $table): bool
    {
        return Schema::connection($conn)->hasTable($table);
    }
}
