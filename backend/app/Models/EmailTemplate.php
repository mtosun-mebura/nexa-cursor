<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'subject', 'type', 'html_content', 'text_content', 'description', 'is_active', 'company_id',
        'recipient_type', 'recipient_user_id', 'recipient_email',
        'form_field_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'form_field_order' => 'array',
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
        $rowStyle = 'padding: 8px 0; border-bottom: 1px solid #e5e7eb; background-color: #ffffff; text-align: left;';
        $rows = [];
        foreach ($formFields as $field) {
            $varKey = static::fieldNameToVariableKey($field->name);
            $rows[] = '<tr><td style="' . $rowStyle . '"><strong>' . e($field->label) . ':</strong></td><td style="' . $rowStyle . '">{{ ' . $varKey . ' }}</td></tr>';
        }
        $rows[] = '<tr><td style="' . $rowStyle . '"><strong>Datum aanvraag:</strong></td><td style="' . $rowStyle . '">{{ DATUM_AANVRAAG }}</td></tr>';
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


