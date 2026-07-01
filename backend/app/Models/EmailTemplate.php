<?php

namespace App\Models;

use App\Services\InformatieaanvraagEmailHtmlNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'subject', 'type', 'html_content', 'text_content', 'description', 'is_active', 'company_id',
        'recipient_type', 'recipient_user_id', 'recipient_email',
        'form_field_order', 'form_field_required',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'form_field_order' => 'array',
        'form_field_required' => 'array',
    ];

    /**
     * Formuliervelden voor dit template in de opgeslagen volgorde (alleen bij type informatieaanvraag).
     * Als form_field_order is gezet: alleen die velden in die volgorde. Anders: alle velden via sort_order.
     */
    public function getOrderedFormFields(): \Illuminate\Support\Collection
    {
        if ($this->type !== 'informatieaanvraag') {
            return collect();
        }
        $order = $this->form_field_order;
        if (is_array($order) && count($order) > 0) {
            $ids = array_map('intval', $order);
            $fields = InfoRequestFormField::whereIn('id', $ids)->get()->keyBy('id');
            return collect($ids)->map(fn ($id) => $fields->get($id))->filter()->values();
        }
        return InfoRequestFormField::ordered()->get();
    }

    public function isFormFieldRequired(InfoRequestFormField $field): bool
    {
        $overrides = $this->form_field_required;
        if (is_array($overrides)) {
            $id = (string) $field->id;
            if (array_key_exists($id, $overrides)) {
                return (bool) $overrides[$id];
            }
            if (array_key_exists($field->id, $overrides)) {
                return (bool) $overrides[$field->id];
            }
        }

        return (bool) $field->is_required;
    }

    /**
     * @return array<int, string|callable>
     */
    public function validationRulesForFormField(InfoRequestFormField $field): array
    {
        $rules = $field->getValidationRules();
        $required = $this->isFormFieldRequired($field);

        if ($required) {
            $rules = array_values(array_filter($rules, fn ($rule) => $rule !== 'nullable'));
            if (! in_array('required', $rules, true)) {
                array_unshift($rules, 'required');
            }
        } else {
            $rules = array_values(array_filter($rules, fn ($rule) => $rule !== 'required'));
            if (! in_array('nullable', $rules, true)) {
                array_unshift($rules, 'nullable');
            }
        }

        return $rules;
    }

    /**
     * Variabelenaam voor in de e-mailtemplate (slug in HOOFDLETTERS met underscores).
     */
    public static function fieldNameToVariableKey(string $name): string
    {
        return strtoupper(str_replace('-', '_', $name));
    }

    /**
     * HTML voor de dynamische formuliervelden-tabel (voor placeholder {{ DYNAMIC_FORM_FIELDS }}).
     * Gebruikt de bij dit template gekoppelde formuliervelden + systeemveld Datum aanvraag.
     * Nieuwe velden uit Formulier velden verschijnen hier automatisch.
     */
    public function renderDynamicFormFieldsHtml(): string
    {
        if ($this->type !== 'informatieaanvraag') {
            return '';
        }
        $formFields = $this->getOrderedFormFields();
        $labelStyle = 'padding: 6px 10px 6px 14px; background-color: #ffffff; color: #374151; text-align: right; vertical-align: top; width: 175px; white-space: nowrap;';
        $valueStyle = 'padding: 6px 10px 6px 10px; background-color: #ffffff; color: #111827; text-align: left; vertical-align: top;';
        $textareaValueStyle = $valueStyle.' white-space: pre-wrap; word-break: break-word;';
        $divider = InformatieaanvraagEmailHtmlNormalizer::fieldDividerRowHtml();
        $fieldRows = [];
        foreach ($formFields as $field) {
            $varKey = static::fieldNameToVariableKey($field->name);
            $cellStyle = $field->isTextareaField() ? $textareaValueStyle : $valueStyle;
            $fieldRows[] = '<tr class="info-request-field-row"><td class="info-request-field-label" style="' . $labelStyle . '"><strong>' . e($field->label) . ':</strong></td><td class="info-request-field-value' . ($field->isTextareaField() ? ' info-request-field-value--multiline' : '') . '" style="' . $cellStyle . '">{{ ' . $varKey . ' }}</td></tr>';
        }
        $fieldRows[] = '<tr class="info-request-field-row"><td class="info-request-field-label" style="' . $labelStyle . '"><strong>Datum aanvraag:</strong></td><td class="info-request-field-value" style="' . $valueStyle . '">{{ DATUM_AANVRAAG }}</td></tr>';

        $rows = [];
        foreach ($fieldRows as $index => $row) {
            $rows[] = $row;
            if ($index < count($fieldRows) - 1) {
                $rows[] = $divider;
            }
        }

        return implode("\n", $rows);
    }

    /**
     * Ontvanger (gebruiker) wanneer recipient_type === 'user'.
     */
    public function recipientUser()
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    /**
     * Geef het e-mailadres van de ontvanger (opgeslagen bij template).
     * Gebruikt bij testmail en bij informatieaanvraag van het websiteformulier.
     */
    public function getRecipientEmailAddress(): ?string
    {
        if ($this->recipient_type === 'email' && $this->recipient_email) {
            return $this->recipient_email;
        }
        if ($this->recipient_type === 'user' && $this->recipient_user_id) {
            $user = $this->recipientUser ?? $this->recipientUser()->first();

            return $user ? $user->email : null;
        }

        return null;
    }

    /**
     * Get the company that owns the email template.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the notifications that use this email template.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}


