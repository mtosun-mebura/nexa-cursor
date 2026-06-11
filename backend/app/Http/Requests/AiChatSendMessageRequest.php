<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiChatSendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'message' => 'required|string|max:4000',
            'history' => 'nullable|array|max:20',
            'history.*.role' => 'required_with:history|in:user,assistant,ai',
            'history.*.text' => 'required_with:history|string|max:4000',
            'module' => 'nullable|string|max:50',
            'sessionId' => 'nullable|string|max:120',
            'quoteAddress' => 'nullable|array',
            'quoteAddress.label' => 'nullable|string|max:500',
            'quoteAddress.place_id' => 'nullable|string|max:255',
            'quoteAddress.lat' => 'nullable|numeric|between:-90,90',
            'quoteAddress.lng' => 'nullable|numeric|between:-180,180',
            'quoteBaggage' => 'nullable|array',
            'quoteBaggage.baggage' => 'nullable|array',
            'quoteBaggage.baggage.*' => 'integer|min:0|max:20',
            'quoteBaggage.special_baggage' => 'nullable|array',
            'quoteBaggage.special_baggage.*' => 'integer|min:0|max:20',
        ];
    }

    /**
     * @return array{label: string, place_id?: string, lat?: float, lng?: float}|null
     */
    public function quoteAddress(): ?array
    {
        $raw = $this->validated('quoteAddress');
        if (! is_array($raw)) {
            return null;
        }

        $label = trim((string) ($raw['label'] ?? ''));
        $placeId = trim((string) ($raw['place_id'] ?? ''));
        $lat = $raw['lat'] ?? null;
        $lng = $raw['lng'] ?? null;

        if ($label === '' && $placeId === '' && ! is_numeric($lat) && ! is_numeric($lng)) {
            return null;
        }

        $payload = ['label' => $label !== '' ? $label : trim((string) $this->validated('message'))];

        if ($placeId !== '') {
            $payload['place_id'] = $placeId;
        }

        if (is_numeric($lat) && is_numeric($lng)) {
            $payload['lat'] = (float) $lat;
            $payload['lng'] = (float) $lng;
        }

        return $payload;
    }

    /**
     * @return array{baggage: array<string, int>, special_baggage: array<string, int>}|null
     */
    public function quoteBaggage(): ?array
    {
        $raw = $this->validated('quoteBaggage');
        if (! is_array($raw)) {
            return null;
        }

        return [
            'baggage' => $this->normalizeQtyMap($raw['baggage'] ?? []),
            'special_baggage' => $this->normalizeQtyMap($raw['special_baggage'] ?? []),
        ];
    }

    /**
     * @param  mixed  $raw
     * @return array<string, int>
     */
    private function normalizeQtyMap(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $normalized = [];
        foreach ($raw as $key => $value) {
            $qty = (int) $value;
            if ($qty > 0) {
                $normalized[(string) $key] = $qty;
            }
        }

        return $normalized;
    }
}
