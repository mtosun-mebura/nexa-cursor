<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfoRequestFormField extends Model
{
    use HasFactory;

    public const TEXTAREA_MAX_LENGTH = 500;

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

    /**
     * Laravel validation rules voor dit veld (voor request).
     */
    public function getValidationRules(): array
    {
        $rules = $this->is_required ? ['required'] : ['nullable'];
        if ($this->validation_rule) {
            if ($this->validation_rule === 'email') {
                $rules[] = 'email';
            } elseif ($this->validation_rule === 'tel') {
                $rules[] = 'string';
                $rules[] = 'max:100';
            } elseif ($this->validation_rule === 'number') {
                $rules[] = 'numeric';
            } elseif (str_starts_with($this->validation_rule, 'regex:')) {
                $rules[] = $this->validation_rule;
            }
        } else {
            $rules[] = 'string';
            $rules[] = 'max:' . ($this->isTextareaField() ? self::TEXTAREA_MAX_LENGTH : 5000);
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
