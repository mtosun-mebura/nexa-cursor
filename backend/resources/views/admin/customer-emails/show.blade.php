@extends('admin.layouts.app')

@section('title', $email->subject)

@section('content')
@include('admin.partials.ajax-action-button-styles')
<div class="kt-container-fixed max-w-4xl min-w-0">
    <div class="flex flex-wrap items-start justify-between gap-3 pb-7.5">
        <div class="flex flex-col justify-center gap-2 min-w-0">
            <h1 class="text-xl font-medium leading-none text-mono">{{ $email->subject }}</h1>
            <div class="text-sm text-secondary-foreground">
                {{ $email->typeLabel() }}
                · {{ $email->recipient_name ?: $email->recipient_email }}
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.customer-emails.index') }}" class="kt-btn kt-btn-outline">Terug</a>
            <form method="POST"
                  action="{{ route('admin.customer-emails.resend', $email) }}"
                  class="customer-email-resend-form"
                  data-confirm-message="E-mail opnieuw versturen naar {{ $email->recipient_email }}?">
                @csrf
                <button type="submit" class="kt-btn kt-btn-primary admin-ajax-action-btn customer-email-resend-btn inline-flex items-center justify-center gap-2 min-w-[11.5rem]">
                    @include('admin.partials.ajax-action-spinner')
                    <span class="admin-ajax-action-idle-icon customer-email-resend-icon inline-flex items-center" aria-hidden="true">
                        <i class="ki-filled ki-send"></i>
                    </span>
                    <span class="customer-email-resend-label">Opnieuw versturen</span>
                </button>
            </form>
        </div>
    </div>

    <div class="kt-card p-6 text-sm space-y-2 mb-5">
        <p><strong>Type:</strong> {{ $email->typeLabel() }}</p>
        <p><strong>Status:</strong> {{ $email->statusLabel() }}</p>
        <p><strong>Aan:</strong> {{ $email->recipient_name ? $email->recipient_name.' <'.$email->recipient_email.'>' : $email->recipient_email }}</p>
        @if($email->company)
            <p><strong>Bedrijf:</strong> {{ $email->company->name }}</p>
        @endif
        <p><strong>Verzonden:</strong> {{ ($email->sent_at ?? $email->created_at)?->timezone(config('app.timezone', 'Europe/Amsterdam'))->format('d-m-Y H:i') ?? '—' }}</p>
        @if($email->isResend())
            <p><strong>Opnieuw verstuurd vanuit:</strong> bericht #{{ $email->resent_from_id }}</p>
        @endif
        @if((int) $email->resent_count > 0)
            <p><strong>Opnieuw verstuurd:</strong> {{ $email->resent_count }}×
                @if($email->last_resent_at)
                    (laatst {{ $email->last_resent_at->timezone(config('app.timezone', 'Europe/Amsterdam'))->format('d-m-Y H:i') }})
                @endif
            </p>
        @endif
        @if($email->error_message)
            <p class="text-destructive"><strong>Foutmelding:</strong> {{ $email->error_message }}</p>
        @endif
    </div>

    <div class="kt-card overflow-hidden">
        <div class="kt-card-header py-4 px-5">
            <div>
                <h3 class="kt-card-title text-sm mb-0">Inhoud</h3>
                <p class="text-xs text-muted-foreground mb-0 mt-1">Weergave zoals de ontvanger dit in de mailbox zag.</p>
            </div>
        </div>
        <div class="p-4 sm:p-6" style="background:#d1d5db;">
            <div class="mx-auto max-w-[680px] overflow-hidden rounded-lg border border-black/10 bg-white shadow-md" style="color-scheme:light;">
                <div class="border-b border-black/10 px-5 py-3 text-[13px] leading-5" style="background:#f8fafc;color:#111827;">
                    <div><span style="color:#6b7280;">Onderwerp</span> · {{ $email->subject }}</div>
                    <div class="mt-0.5"><span style="color:#6b7280;">Aan</span> · {{ $email->recipient_name ? $email->recipient_name.' <'.$email->recipient_email.'>' : $email->recipient_email }}</div>
                </div>
                @if($email->body_html || $email->body_text)
                    <iframe title="E-mail zoals in de mailbox"
                            id="customer-email-body"
                            src="{{ route('admin.customer-emails.preview', $email) }}"
                            class="block w-full border-0"
                            style="color-scheme:light;background:#ffffff;min-height:520px;height:520px;"></iframe>
                @else
                    <p class="px-5 py-8 text-sm" style="color:#374151;">Geen inhoud vastgelegd.</p>
                @endif
            </div>
        </div>
    </div>
</div>

<div id="customer-email-resend-dialog" class="fixed inset-0 z-[100000] hidden items-center justify-center bg-zinc-950/70 p-4 backdrop-blur-md" role="dialog" aria-modal="true" aria-labelledby="customer-email-resend-title">
    <div class="w-full max-w-md rounded-2xl border border-border bg-background p-6 shadow-2xl">
        <h2 id="customer-email-resend-title" class="text-lg font-semibold text-foreground mb-2">E-mail opnieuw versturen?</h2>
        <p class="text-sm text-muted-foreground mb-6" data-resend-dialog-message></p>
        <div class="flex justify-end gap-2">
            <button type="button" class="kt-btn kt-btn-outline" data-resend-dialog-cancel>Annuleren</button>
            <button type="button" class="kt-btn kt-btn-primary" data-resend-dialog-confirm>Versturen</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var iframe = document.getElementById('customer-email-body');
    if (iframe) {
        iframe.addEventListener('load', function () {
            try {
                var doc = iframe.contentDocument || iframe.contentWindow.document;
                var height = Math.max(
                    520,
                    doc.documentElement.scrollHeight || 0,
                    doc.body ? doc.body.scrollHeight : 0
                );
                iframe.style.height = height + 'px';
            } catch (e) {}
        });
    }

    var form = document.querySelector('.customer-email-resend-form');
    var btn = document.querySelector('.customer-email-resend-btn');
    var dialog = document.getElementById('customer-email-resend-dialog');
    if (!form || !btn || !dialog) {
        return;
    }
    var iconEl = btn.querySelector('.customer-email-resend-icon');
    var labelEl = btn.querySelector('.customer-email-resend-label');
    var idleIcon = iconEl ? iconEl.innerHTML : '';
    var idleLabel = labelEl ? labelEl.textContent : 'Opnieuw versturen';
    var messageEl = dialog.querySelector('[data-resend-dialog-message]');
    var cancelBtn = dialog.querySelector('[data-resend-dialog-cancel]');
    var confirmBtn = dialog.querySelector('[data-resend-dialog-confirm]');
    var resetTimer = null;
    var pendingSubmit = false;

    function setButtonState(state) {
        btn.classList.remove('is-loading', 'is-success', 'is-error');
        btn.disabled = state === 'loading';
        if (state === 'loading') {
            btn.classList.add('is-loading');
            btn.setAttribute('aria-busy', 'true');
            if (iconEl) {
                iconEl.innerHTML = idleIcon;
            }
            if (labelEl) {
                labelEl.textContent = 'Versturen…';
            }
            return;
        }
        btn.removeAttribute('aria-busy');
        if (state === 'success') {
            btn.classList.add('is-success');
            btn.disabled = false;
            if (iconEl) {
                iconEl.innerHTML = '<i class="ki-filled ki-check"></i>';
            }
            if (labelEl) {
                labelEl.textContent = idleLabel;
            }
            return;
        }
        if (state === 'error') {
            btn.classList.add('is-error');
            if (iconEl) {
                iconEl.innerHTML = '<i class="ki-filled ki-cross"></i>';
            }
            if (labelEl) {
                labelEl.textContent = idleLabel;
            }
            return;
        }
        if (iconEl) {
            iconEl.innerHTML = idleIcon;
        }
        if (labelEl) {
            labelEl.textContent = idleLabel;
        }
    }

    function openDialog() {
        if (messageEl) {
            messageEl.textContent = form.getAttribute('data-confirm-message') || 'E-mail opnieuw versturen?';
        }
        dialog.classList.remove('hidden');
        dialog.classList.add('flex');
        if (confirmBtn) {
            confirmBtn.focus();
        }
    }

    function closeDialog() {
        dialog.classList.add('hidden');
        dialog.classList.remove('flex');
        pendingSubmit = false;
    }

    function sendResend() {
        if (resetTimer) {
            window.clearTimeout(resetTimer);
            resetTimer = null;
        }
        setButtonState('loading');
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        }).then(function (res) {
            return res.json().then(function (data) {
                return { ok: res.ok, data: data || {} };
            }).catch(function () {
                return { ok: res.ok, data: {} };
            });
        }).then(function (result) {
            var success = result.ok && result.data.success !== false;
            setButtonState(success ? 'success' : 'error');
            if (success) {
                return;
            }
            resetTimer = window.setTimeout(function () {
                setButtonState('idle');
            }, 4000);
        }).catch(function () {
            setButtonState('error');
            resetTimer = window.setTimeout(function () {
                setButtonState('idle');
            }, 4000);
        });
    }

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        if (btn.disabled) {
            return;
        }
        pendingSubmit = true;
        openDialog();
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeDialog);
    }
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            if (!pendingSubmit) {
                return;
            }
            closeDialog();
            sendResend();
        });
    }
    dialog.addEventListener('click', function (ev) {
        if (ev.target === dialog) {
            closeDialog();
        }
    });
    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && !dialog.classList.contains('hidden')) {
            closeDialog();
        }
    });
})();
</script>
@endpush
