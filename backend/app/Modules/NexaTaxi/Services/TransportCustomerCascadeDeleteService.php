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
use App\Modules\NexaTaxi\Support\ContractTransportTimezone;
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
            $this->purgePlanningAndAgendaRides($conn, $customer, keepPastRides: false);

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
     * Verwijder planning-/agenda-ritten van dit contract.
     * Met $keepPastRides blijven ritten vóór vandaag staan; toekomst verdwijnt altijd.
     *
     * @return array{occurrences: int, rides: int}
     */
    public function purgePlanningAndAgendaRides(
        string $conn,
        TransportCustomer $customer,
        bool $keepPastRides = false,
    ): array {
        $contractIds = TransportContract::on($conn)
            ->where('transport_customer_id', $customer->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($contractIds === []) {
            return ['occurrences' => 0, 'rides' => 0];
        }

        $today = now(ContractTransportTimezone::TIMEZONE)->toDateString();

        $occurrenceQuery = TransportOccurrence::on($conn)
            ->whereIn('transport_contract_id', $contractIds);
        if ($keepPastRides) {
            $occurrenceQuery->whereDate('scheduled_date', '>=', $today);
        }
        $occurrenceIds = $occurrenceQuery->pluck('id')->map(fn ($id) => (int) $id)->all();

        $rideIdsFromOccurrences = $occurrenceIds === []
            ? []
            : TransportOccurrence::on($conn)
                ->whereIn('id', $occurrenceIds)
                ->whereNotNull('ride_request_id')
                ->pluck('ride_request_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

        $rideIdsDirect = [];
        if ($this->hasTable($conn, 'ride_requests')) {
            $rideQuery = RideRequest::on($conn)
                ->whereIn('transport_contract_id', $contractIds);
            if ($keepPastRides) {
                $rideQuery->where(function ($q) use ($today) {
                    $q->whereNull('pickup_at')
                        ->orWhereDate('pickup_at', '>=', $today);
                });
            }
            $rideIdsDirect = $rideQuery->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        $rideIds = array_values(array_unique(array_merge($rideIdsFromOccurrences, $rideIdsDirect)));

        if ($occurrenceIds !== []) {
            TransportOccurrence::on($conn)->whereIn('id', $occurrenceIds)->delete();
        }

        if ($rideIds !== [] && $this->hasTable($conn, 'ride_requests')) {
            $this->deleteRideRequests($conn, $rideIds);
        }

        return [
            'occurrences' => count($occurrenceIds),
            'rides' => count($rideIds),
        ];
    }

    /**
     * @param  list<int>  $rideIds
     */
    private function deleteRideRequests(string $conn, array $rideIds): void
    {
        $rideIds = array_values(array_unique(array_filter(array_map('intval', $rideIds))));
        if ($rideIds === []) {
            return;
        }

        if ($this->hasTable($conn, 'ride_dispatch_offers')) {
            DB::connection($conn)
                ->table('ride_dispatch_offers')
                ->whereIn('ride_request_id', $rideIds)
                ->delete();
        }

        if ($this->hasTable($conn, 'ride_stops')) {
            RideStop::on($conn)->whereIn('ride_request_id', $rideIds)->delete();
        }

        RideRequest::on($conn)->whereIn('id', $rideIds)->delete();
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
            $remainingRideIds = RideRequest::on($conn)
                ->whereIn('transport_contract_id', $contractIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $this->deleteRideRequests($conn, $remainingRideIds);
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
