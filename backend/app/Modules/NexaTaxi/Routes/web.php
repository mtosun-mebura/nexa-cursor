<?php

use Illuminate\Support\Facades\Route;
use App\Modules\NexaTaxi\Controllers\Admin\VehicleController;
use App\Modules\NexaTaxi\Controllers\Admin\RideRequestController;
use App\Modules\NexaTaxi\Controllers\Admin\DispatchSettingsController;
use App\Modules\NexaTaxi\Controllers\Admin\TarievenController;
use App\Modules\NexaTaxi\Controllers\Admin\AiChatbotSettingsController;
use App\Modules\NexaTaxi\Controllers\Admin\KnowledgeDocumentController;
use App\Modules\NexaTaxi\Controllers\Admin\TransportCustomerController;
use App\Modules\NexaTaxi\Controllers\Admin\TransportPassengerController;
use App\Modules\NexaTaxi\Controllers\Admin\TransportGroupController;
use App\Modules\NexaTaxi\Controllers\Admin\TransportGroupRouteController;
use App\Modules\NexaTaxi\Controllers\Admin\TransportIndividualBookingController;
use App\Modules\NexaTaxi\Controllers\Admin\TransportContractInvoiceController;
use App\Modules\NexaTaxi\Controllers\Admin\TransportPlanningController;
use App\Modules\NexaTaxi\Controllers\Admin\TransportScheduleExceptionController;
use App\Modules\NexaTaxi\Models\Vehicle;
use App\Modules\NexaTaxi\Models\RideRequest;
use App\Modules\NexaTaxi\Models\KnowledgeDocument;
use App\Services\ModuleDatabaseService;

/*
|--------------------------------------------------------------------------
| Nexa Taxi Module – Admin routes
|--------------------------------------------------------------------------
| Voertuigen en ritten/aanvragen. Prefix: admin/taxi, name: admin.taxi.*
| Data komt uit de module-database nexa_taxi.
*/

$taxiConn = fn () => app(ModuleDatabaseService::class)->getModuleConnectionName('taxi');
Route::bind('vehicle', fn ($value) => Vehicle::on($taxiConn())->findOrFail($value));
Route::bind('ride_request', fn ($value) => RideRequest::on($taxiConn())->findOrFail($value));
Route::bind('knowledge_document', fn ($value) => KnowledgeDocument::on($taxiConn())->findOrFail($value));

Route::get('ai-chatbot/instellingen', [AiChatbotSettingsController::class, 'edit'])
    ->name('ai_chatbot.settings.edit');
Route::put('ai-chatbot/instellingen', [AiChatbotSettingsController::class, 'update'])
    ->name('ai_chatbot.settings.update');
Route::post('ai-chatbot/genereer-van-website', [KnowledgeDocumentController::class, 'generateFromWebsite'])
    ->name('knowledge_documents.generate_from_website');
Route::post('ai-chatbot/tekst-opmaken', [KnowledgeDocumentController::class, 'formatContent'])
    ->name('knowledge_documents.format_content');
Route::resource('ai-chatbot', KnowledgeDocumentController::class)
    ->parameters(['ai-chatbot' => 'knowledge_document'])
    ->names('knowledge_documents');

Route::get('dispatch-instellingen', [DispatchSettingsController::class, 'edit'])->name('dispatch_settings.edit');
Route::put('dispatch-instellingen', [DispatchSettingsController::class, 'update'])->name('dispatch_settings.update');
Route::get('dispatch-instellingen/klant-e-mail', [DispatchSettingsController::class, 'editCustomerAcceptEmail'])
    ->name('dispatch_settings.customer_accept_email.edit');
Route::put('dispatch-instellingen/klant-e-mail', [DispatchSettingsController::class, 'updateCustomerAcceptEmail'])
    ->name('dispatch_settings.customer_accept_email.update');
Route::get('tarieven', [TarievenController::class, 'edit'])->name('tarieven.edit');
Route::put('tarieven', [TarievenController::class, 'update'])->name('tarieven.update');
Route::post('ride_requests/{ride_request}/assign', [RideRequestController::class, 'assign'])->name('ride_requests.assign');
Route::post('ride_requests/{ride_request}/reoffer-dispatch', [RideRequestController::class, 'reofferDispatch'])
    ->name('ride_requests.reoffer_dispatch');
Route::get('ride_requests/{ride_request}/notificatielog', [RideRequestController::class, 'notificationLog'])
    ->name('ride_requests.notification_log');
Route::resource('ride_requests', RideRequestController::class);
Route::post('vehicles/upload-image', [VehicleController::class, 'uploadImage'])->name('vehicles.upload-image');
Route::resource('vehicles', VehicleController::class);

// ---- Contractvervoer: contractklanten ----
Route::get('contractklanten', [TransportCustomerController::class, 'index'])->name('transport_customers.index');
Route::get('contractklanten/nieuw', [TransportCustomerController::class, 'create'])->name('transport_customers.create');
Route::post('contractklanten', [TransportCustomerController::class, 'store'])->name('transport_customers.store');
Route::get('contractklanten/{id}', [TransportCustomerController::class, 'show'])->name('transport_customers.show');
Route::get('contractklanten/{id}/bewerken', [TransportCustomerController::class, 'edit'])->name('transport_customers.edit');
Route::put('contractklanten/{id}', [TransportCustomerController::class, 'update'])->name('transport_customers.update');
Route::delete('contractklanten/{id}', [TransportCustomerController::class, 'destroy'])->name('transport_customers.destroy');

// Abonnementen (sub-resource van klant)
Route::get('contractklanten/{customerId}/abonnementen/nieuw', [TransportCustomerController::class, 'contractCreate'])
    ->name('transport_customers.contract_create');
Route::post('contractklanten/{customerId}/abonnementen', [TransportCustomerController::class, 'contractStore'])
    ->name('transport_customers.contract_store');
Route::get('contractklanten/{customerId}/abonnementen/{contractId}', [TransportCustomerController::class, 'contractShow'])
    ->name('transport_customers.contract_show');
Route::get('contractklanten/{customerId}/abonnementen/{contractId}/bewerken', [TransportCustomerController::class, 'contractEdit'])
    ->name('transport_customers.contract_edit');
Route::put('contractklanten/{customerId}/abonnementen/{contractId}', [TransportCustomerController::class, 'contractUpdate'])
    ->name('transport_customers.contract_update');
Route::post('contractklanten/{customerId}/abonnementen/{contractId}/mandaat', [TransportCustomerController::class, 'mandateSave'])
    ->name('transport_customers.mandate_save');

Route::post('contractklanten/{customerId}/abonnementen/{contractId}/facturen', [TransportContractInvoiceController::class, 'generate'])
    ->name('transport_contract_invoices.generate');
Route::post('contractklanten/{customerId}/abonnementen/{contractId}/facturen/{invoiceId}/verzenden', [TransportContractInvoiceController::class, 'send'])
    ->name('transport_contract_invoices.send');
Route::post('contractklanten/{customerId}/abonnementen/{contractId}/facturen/{invoiceId}/betaald', [TransportContractInvoiceController::class, 'markPaid'])
    ->name('transport_contract_invoices.mark_paid');
Route::delete('contractklanten/{customerId}/abonnementen/{contractId}/facturen/{invoiceId}', [TransportContractInvoiceController::class, 'destroy'])
    ->name('transport_contract_invoices.destroy');
Route::get('contractklanten/{customerId}/abonnementen/{contractId}/facturen/{invoiceId}/pdf', [TransportContractInvoiceController::class, 'downloadPdf'])
    ->name('transport_contract_invoices.pdf');
Route::get('contractklanten/{customerId}/abonnementen/{contractId}/facturen/export', [TransportContractInvoiceController::class, 'exportCsv'])
    ->name('transport_contract_invoices.export');

Route::get('contractvervoer/planning', [TransportPlanningController::class, 'index'])
    ->name('transport_planning.index');
Route::get('contractvervoer/uitzonderingen', [TransportScheduleExceptionController::class, 'index'])
    ->name('transport_schedule_exceptions.index');
Route::post('contractvervoer/uitzonderingen', [TransportScheduleExceptionController::class, 'store'])
    ->name('transport_schedule_exceptions.store');
Route::delete('contractvervoer/uitzonderingen/{id}', [TransportScheduleExceptionController::class, 'destroy'])
    ->name('transport_schedule_exceptions.destroy');

// Passagiers (sub-resource van abonnement)
Route::get('contractklanten/{customerId}/abonnementen/{contractId}/passagiers', [TransportPassengerController::class, 'index'])
    ->name('transport_passengers.index');
Route::get('contractklanten/{customerId}/abonnementen/{contractId}/passagiers/nieuw', [TransportPassengerController::class, 'create'])
    ->name('transport_passengers.create');
Route::post('contractklanten/{customerId}/abonnementen/{contractId}/passagiers', [TransportPassengerController::class, 'store'])
    ->name('transport_passengers.store');
Route::get('contractklanten/{customerId}/abonnementen/{contractId}/passagiers/{passengerId}/bewerken', [TransportPassengerController::class, 'edit'])
    ->name('transport_passengers.edit');
Route::put('contractklanten/{customerId}/abonnementen/{contractId}/passagiers/{passengerId}', [TransportPassengerController::class, 'update'])
    ->name('transport_passengers.update');
Route::delete('contractklanten/{customerId}/abonnementen/{contractId}/passagiers/{passengerId}', [TransportPassengerController::class, 'destroy'])
    ->name('transport_passengers.destroy');

// Groepen (sub-resource van abonnement)
Route::get('contractklanten/{customerId}/abonnementen/{contractId}/groepen', [TransportGroupController::class, 'index'])
    ->name('transport_groups.index');
Route::get('contractklanten/{customerId}/abonnementen/{contractId}/groepen/nieuw', [TransportGroupController::class, 'create'])
    ->name('transport_groups.create');
Route::post('contractklanten/{customerId}/abonnementen/{contractId}/groepen', [TransportGroupController::class, 'store'])
    ->name('transport_groups.store');
Route::get('contractklanten/{customerId}/abonnementen/{contractId}/groepen/{groupId}', [TransportGroupController::class, 'show'])
    ->name('transport_groups.show');
Route::get('contractklanten/{customerId}/abonnementen/{contractId}/groepen/{groupId}/bewerken', [TransportGroupController::class, 'edit'])
    ->name('transport_groups.edit');
Route::put('contractklanten/{customerId}/abonnementen/{contractId}/groepen/{groupId}', [TransportGroupController::class, 'update'])
    ->name('transport_groups.update');
Route::delete('contractklanten/{customerId}/abonnementen/{contractId}/groepen/{groupId}', [TransportGroupController::class, 'destroy'])
    ->name('transport_groups.destroy');
Route::post('contractklanten/{customerId}/abonnementen/{contractId}/groepen/{groupId}/leden', [TransportGroupController::class, 'memberStore'])
    ->name('transport_groups.member_store');
Route::delete('contractklanten/{customerId}/abonnementen/{contractId}/groepen/{groupId}/leden/{memberId}', [TransportGroupController::class, 'memberRemove'])
    ->name('transport_groups.member_remove');

// Routeplanner (sub-resource van groep)
Route::get('contractklanten/{customerId}/abonnementen/{contractId}/groepen/{groupId}/route', [TransportGroupRouteController::class, 'edit'])
    ->name('transport_groups.route.edit');
Route::put('contractklanten/{customerId}/abonnementen/{contractId}/groepen/{groupId}/route/instellingen', [TransportGroupRouteController::class, 'updateSettings'])
    ->name('transport_groups.route.settings');
Route::post('contractklanten/{customerId}/abonnementen/{contractId}/groepen/{groupId}/route/berekenen', [TransportGroupRouteController::class, 'calculate'])
    ->name('transport_groups.route.calculate');
Route::put('contractklanten/{customerId}/abonnementen/{contractId}/groepen/{groupId}/route/stops', [TransportGroupRouteController::class, 'updateStops'])
    ->name('transport_groups.route.stops');
Route::post('contractklanten/{customerId}/abonnementen/{contractId}/groepen/{groupId}/route/vastzetten', [TransportGroupRouteController::class, 'toggleLock'])
    ->name('transport_groups.route.lock');
Route::put('contractklanten/{customerId}/abonnementen/{contractId}/groepen/{groupId}/route/toewijzing', [TransportGroupRouteController::class, 'updateAssignment'])
    ->name('transport_groups.route.assignment');

// Individuele contractritten (sub-resource van abonnement)
Route::get('contractklanten/{customerId}/abonnementen/{contractId}/individuele-ritten', [TransportIndividualBookingController::class, 'index'])
    ->name('transport_individual_bookings.index');
Route::get('contractklanten/{customerId}/abonnementen/{contractId}/individuele-ritten/nieuw', [TransportIndividualBookingController::class, 'create'])
    ->name('transport_individual_bookings.create');
Route::post('contractklanten/{customerId}/abonnementen/{contractId}/individuele-ritten', [TransportIndividualBookingController::class, 'store'])
    ->name('transport_individual_bookings.store');
Route::get('contractklanten/{customerId}/abonnementen/{contractId}/individuele-ritten/{bookingId}/bewerken', [TransportIndividualBookingController::class, 'edit'])
    ->name('transport_individual_bookings.edit');
Route::put('contractklanten/{customerId}/abonnementen/{contractId}/individuele-ritten/{bookingId}', [TransportIndividualBookingController::class, 'update'])
    ->name('transport_individual_bookings.update');
Route::delete('contractklanten/{customerId}/abonnementen/{contractId}/individuele-ritten/{bookingId}', [TransportIndividualBookingController::class, 'destroy'])
    ->name('transport_individual_bookings.destroy');
