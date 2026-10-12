<?php

namespace App\Services\TaxiPartnerFinder;

use App\Models\NewsletterProspect;
use App\Models\TaxiProspect;
use App\Models\TaxiProspectOptOut;

/**
 * Brug naar de bestaande nieuwsbrief-opt-inlijst.
 * Nooit automatisch een actieve NexaTaxi-partner aanmaken.
 */
class TaxiProspectNewsletterBridge
{
    public function pushIfEligible(TaxiProspect $prospect): ?NewsletterProspect
    {
        $email = strtolower(trim((string) $prospect->email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        if (TaxiProspectOptOut::query()->where('email', $email)->exists()) {
            return null;
        }
        if ($prospect->lead_status === TaxiProspect::LEAD_OPTED_OUT) {
            return null;
        }

        $existing = NewsletterProspect::query()->where('email', $email)->first();
        if ($existing) {
            if ($existing->status === NewsletterProspect::STATUS_UNSUBSCRIBED) {
                return null;
            }
            $fill = [];
            foreach (['company_name', 'phone', 'website', 'city', 'province'] as $field) {
                if (blank($existing->{$field}) && filled($prospect->{$field})) {
                    $fill[$field] = $prospect->{$field};
                }
            }
            if ($fill !== []) {
                $existing->fill($fill)->save();
            }

            return $existing;
        }

        return NewsletterProspect::query()->create([
            'company_name' => $prospect->company_name,
            'email' => $email,
            'phone' => $prospect->phone,
            'website' => $prospect->website,
            'address' => trim(implode(' ', array_filter([
                $prospect->street,
                $prospect->house_number,
                $prospect->postal_code,
            ]))) ?: null,
            'city' => $prospect->city,
            'province' => $prospect->province,
            'branch' => 'taxi',
            'source' => $prospect->knv_member_status === TaxiProspect::KNV_MEMBER ? 'knv' : 'partner_finder',
            'source_url' => $prospect->sources()->latest('id')->value('source_url'),
            'status' => NewsletterProspect::STATUS_SUBSCRIBED,
            'notes' => 'Via Taxi Partner Finder #'.$prospect->id,
        ]);
    }
}
