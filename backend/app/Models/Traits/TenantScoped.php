<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait TenantScoped
{
    protected static function bootTenantScoped(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = app()->bound('tenant_id') ? app('tenant_id') : null;
            if ($tenantId && in_array('company_id', (new static)->getFillable(), true)) {
                $builder->where($builder->getModel()->getTable() . '.company_id', $tenantId);
            }
        });
    }
}


