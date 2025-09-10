<?php

namespace App\Http\Controllers\Admin\Traits;

trait TenantFilter
{
    protected function applyTenantFilter($query)
    {
        $user = auth()->user();
        
        // Get the table name from the query
        $tableName = $query->getModel()->getTable();
        
        // Categories are visible to all companies (no filtering)
        if ($tableName === 'categories') {
            return $query;
        }
        
        // Super admin kan alle data zien, tenzij een specifieke tenant is geselecteerd
        if ($user->hasRole('super-admin')) {
            if (session('selected_tenant')) {
                if ($tableName === 'companies') {
                    $query->where('id', session('selected_tenant'));
                } else {
                    $query->where('company_id', session('selected_tenant'));
                }
            } else {
                // Als geen tenant geselecteerd, toon alle data (geen filtering)
                return $query;
            }
        } else {
            // Company admin en staff kunnen alleen hun eigen bedrijfsdata zien
            if ($tableName === 'companies') {
                $query->where('id', $user->company_id);
            } else {
                $query->where('company_id', $user->company_id);
            }
        }
        
        return $query;
    }
    
    protected function getTenantId()
    {
        $user = auth()->user();
        
        if ($user->hasRole('super-admin') && session('selected_tenant')) {
            return session('selected_tenant');
        }
        
        return $user->company_id;
    }
    
    protected function canAccessResource($resource)
    {
        $user = auth()->user();
        
        // Super admin kan alles benaderen
        if ($user->hasRole('super-admin')) {
            return true;
        }
        
        // Categories are accessible to all users
        $tableName = $resource->getTable();
        if ($tableName === 'categories') {
            return true;
        }
        
        // Andere gebruikers kunnen alleen hun eigen bedrijfsresources benaderen
        if ($tableName === 'companies') {
            return $resource->id === $user->company_id;
        } else {
            return $resource->company_id === $user->company_id;
        }
    }
}
