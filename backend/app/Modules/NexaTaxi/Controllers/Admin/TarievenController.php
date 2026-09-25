<?php

namespace App\Modules\NexaTaxi\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Modules\NexaTaxi\Models\DefaultRate;
use App\Modules\NexaTaxi\Support\DefaultRateSchema;
use App\Modules\NexaTaxi\Traits\UsesModuleDatabase;
use App\Support\Admin\AdminTenantScope;
use Illuminate\Http\Request;

class TarievenController extends Controller
{
    use UsesModuleDatabase;

    public function edit()
    {
        $this->authorizeOrPermissionAny(['rates.view', 'vehicles.view']);

        $conn = $this->moduleConnection();
        $scopeCompanyId = app(AdminTenantScope::class)->selectedTenantId();
        $rates = DefaultRate::getRatesForEdit($conn, $scopeCompanyId);
        $eveningNight = DefaultRate::eveningNightSettings($rates->first());

        return view('taxi::admin.tarieven.edit', [
            'rates' => $rates,
            'eveningNight' => $eveningNight,
            'scopeCompanyId' => $scopeCompanyId,
            'scopeCompanyName' => $this->scopeCompanyName($scopeCompanyId),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorizeOrPermissionAny(['rates.update', 'vehicles.update']);

        $conn = $this->moduleConnection();
        DefaultRateSchema::ensureColumns($conn);
        $scopeCompanyId = app(AdminTenantScope::class)->selectedTenantId();
        $normalize = function (array $arr) {
            $optional = ['base_fare', 'cleaning_costs', 'person_range'];
            foreach ($arr as $k => $v) {
                if ($v === '') {
                    $arr[$k] = in_array($k, $optional, true) ? null : 0;
                }
            }

            return $arr;
        };
        $rates = array_map($normalize, (array) $request->input('rates', []));
        $request->merge(['rates' => $rates]);

        $request->validate([
            'rates' => 'required|array|min:1',
            'rates.*.person_range' => ['required', 'regex:/^\d+\s*-\s*\d+$/'],
            'rates.*.base_fare' => 'nullable|numeric|min:0',
            'rates.*.min_fare' => 'nullable|numeric|min:0',
            'rates.*.price_per_km' => 'nullable|numeric|min:0',
            'rates.*.price_per_min' => 'nullable|numeric|min:0',
            'rates.*.cleaning_costs' => 'nullable|numeric|min:0',
            'evening_night_multiplier' => 'required|numeric|min:1|max:5',
            'evening_night_from_hour' => 'required|integer|min:0|max:23',
            'evening_night_until_hour' => 'required|integer|min:0|max:23',
        ]);

        $eveningNight = [
            'evening_night_multiplier' => round((float) $request->input('evening_night_multiplier'), 2),
            'evening_night_from_hour' => DefaultRate::normalizeHour($request->input('evening_night_from_hour')),
            'evening_night_until_hour' => DefaultRate::normalizeHour($request->input('evening_night_until_hour')),
        ];

        $normalized = [];
        foreach ($rates as $row) {
            $range = preg_replace('/\s+/', '', (string) ($row['person_range'] ?? ''));
            if (! preg_match('/^\d+-\d+$/', $range)) {
                continue;
            }
            [$start, $end] = DefaultRate::parseRangeBounds($range);
            $normalizedRange = $start . '-' . $end;
            $payload = [
                'person_range' => $normalizedRange,
                'base_fare' => ($row['base_fare'] ?? null) === '' ? null : ($row['base_fare'] ?? null),
                'min_fare' => ($row['min_fare'] ?? 0) === '' ? 0 : ($row['min_fare'] ?? 0),
                'price_per_km' => ($row['price_per_km'] ?? 0) === '' ? 0 : ($row['price_per_km'] ?? 0),
                'price_per_min' => ($row['price_per_min'] ?? 0) === '' ? 0 : ($row['price_per_min'] ?? 0),
                'cleaning_costs' => ($row['cleaning_costs'] ?? null) === '' ? null : ($row['cleaning_costs'] ?? null),
                ...$eveningNight,
            ];
            if (DefaultRate::hasCompanyIdColumn($conn)) {
                $payload['company_id'] = $scopeCompanyId;
            }
            $normalized[$normalizedRange] = $payload;
        }
        if (empty($normalized)) {
            return back()->withErrors(['rates' => 'Voeg minimaal 1 geldig personenbereik toe.'])->withInput();
        }

        $existing = DefaultRate::queryForCompany($conn, $scopeCompanyId)->get()->keyBy('person_range');
        foreach ($normalized as $range => $payload) {
            $rate = $existing->get($range);
            if ($rate) {
                $rate->update($payload);
            } else {
                DefaultRate::on($conn)->create($payload);
            }
        }
        $toDelete = $existing->keys()->diff(array_keys($normalized))->all();
        if (! empty($toDelete)) {
            DefaultRate::queryForCompany($conn, $scopeCompanyId)->whereIn('person_range', $toDelete)->delete();
        }

        if ($scopeCompanyId) {
            \App\Support\TenantPublicCache::bump($scopeCompanyId);
        }

        return redirect()->route('admin.taxi.tarieven.edit')->with('success', 'Tarieven zijn bijgewerkt.');
    }

    private function scopeCompanyName(?int $scopeCompanyId): ?string
    {
        if ($scopeCompanyId === null) {
            return null;
        }

        return Company::query()->whereKey($scopeCompanyId)->value('name');
    }

    private function authorizeOrPermissionAny(array $abilities): void
    {
        if (auth()->user()->hasRole('super-admin')) {
            return;
        }
        foreach ($abilities as $ability) {
            if (auth()->user()->can($ability)) {
                return;
            }
        }
        abort(403, 'Geen rechten voor deze actie.');
    }
}
