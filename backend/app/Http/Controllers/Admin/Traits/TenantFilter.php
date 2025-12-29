<?php

namespace App\Http\Controllers\Admin\Traits;

trait TenantFilter
{
    protected function applyTenantFilter($query)
    {
        $user = auth()->user();
        
        // Get the table name from the query
        $tableName = $query->getModel()->getTable();
        
        // Branches are visible to all companies (no filtering)
        if ($tableName === 'branches') {
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
        
        // Branches are accessible to all users
        $tableName = $resource->getTable();
        if ($tableName === 'branches') {
            return true;
        }
        
        // Voor gebruikers: controleer of de resource een super-admin is
        // Alleen super-admins kunnen andere super-admins zien
        if ($tableName === 'users') {
            // Als de resource een super-admin is, kan alleen een super-admin deze benaderen
            if ($resource->hasRole('super-admin')) {
                return false; // Niet-super-admins kunnen geen super-admins benaderen
            }
            // Voor niet-super-admin gebruikers: controleer bedrijfsfilter
            return $resource->company_id === $user->company_id;
        }
        
        // Andere gebruikers kunnen alleen hun eigen bedrijfsresources benaderen
        if ($tableName === 'companies') {
            return $resource->id === $user->company_id;
        } else {
            return $resource->company_id === $user->company_id;
        }
    }
}
