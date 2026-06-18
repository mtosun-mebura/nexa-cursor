<?php

namespace App\Modules\NexaTaxi\Controllers\Admin\Concerns;

trait AuthorizesTaxiPermissions
{
    private function authorizeOrPermission(string $ability): void
    {
        if (auth()->user()->hasRole('super-admin')) {
            return;
        }
        if (! auth()->user()->can($ability)) {
            abort(403, 'Geen rechten voor deze actie.');
        }
    }
}
