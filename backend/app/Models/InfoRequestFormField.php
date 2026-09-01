<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfoRequestFormField extends Model
{
    use HasFactory;

    public const TEXTAREA_MAX_LENGTH = 500;

    public const TEXT_MAX_LENGTH = 255;

    protected $table = 'info_request_form_fields';

    protected $fillable = [
        'name',
        'label',
        'is_required',
        'validation_rule',
        'sort_order',
    ];

    protected $casts = [
        'is_required' => 'boolean',
    ];

    public function isTextareaField(): bool
    {
        return in_array($this->validation_rule, [null, ''], true)
            && str_contains(strtolower((string) $this->label), 'omschrijving');
    }

    public function isNexaPackageField(): bool
    {
        return $this->validation_rule === 'nexa_package' || $this->name === 'pakket';
    }

    /**
     * Laravel validation rules voor dit veld (voor request).
     */
    public function getValidationRules(): array
    {
        $rules = $this->is_required ? ['required'] : ['nullable'];
        if ($this->validation_rule) {
            if ($this->validation_rule === 'email') {
                $rules[] = 'email';
                $rules[] = 'max:255';
            } elseif ($this->validation_rule === 'tel') {
                $rules[] = 'string';
                $rules[] = 'max:20';
            } elseif ($this->validation_rule === 'number') {
                $rules[] = 'numeric';
            } elseif ($this->isNexaPackageField()) {
                $rules[] = 'string';
                $rules[] = 'max:80';
                $allowed = app(\App\Services\NexaPricingService::class)->packageNames();
                if ($allowed !== []) {
                    $rules[] = \Illuminate\Validation\Rule::in($allowed);
                }
            } elseif (str_starts_with($this->validation_rule, 'regex:')) {
                $rules[] = $this->validation_rule;
            }
        } else {
            $rules[] = 'string';
            $rules[] = 'max:'.($this->isTextareaField() ? self::TEXTAREA_MAX_LENGTH : self::TEXT_MAX_LENGTH);
        }

        return $rules;
    }

    /**
     * Alle velden voor het informatieaanvraag-formulier, op sort_order.
     */
    public static function ordered(): \Illuminate\Database\Eloquent\Builder
    {
        return static::orderBy('sort_order')->orderBy('id');
    }
}
