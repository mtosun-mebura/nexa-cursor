@php
    $paymentsReturnPath = isset($paymentsReturnUrl) ? \App\Support\AdminReturnUrl::sanitize($paymentsReturnUrl) : null;
    $paymentDetailUrl = function ($url) use ($paymentsReturnPath) {
        if (! $url || ! $paymentsReturnPath) {
            return $url;
        }

        return \App\Support\AdminReturnUrl::appendReturnParam($url, $paymentsReturnPath);
    };
@endphp
@forelse($payments as $payment)
    <tr>
        <td>
            <div class="flex flex-col gap-1">
                <a class="leading-none font-medium text-sm text-mono hover:text-primary" href="{{ route('admin.companies.show', $payment->company_id) }}">
                    {{ $payment->company_name }}
                </a>
                <span class="text-xs text-secondary-foreground">{{ $payment->source_label }} · #{{ $payment->id }}</span>
            </div>
        </td>
        <td>
            <span class="text-sm text-secondary-foreground">{{ $payment->source_label }}</span>
        </td>
        <td>
            <span class="text-sm font-medium">
                €{{ number_format($payment->amount, 2, ',', '.') }}
            </span>
        </td>
        <td>
            @if($payment->reference_url)
                <a class="text-sm text-primary hover:underline" href="{{ $paymentDetailUrl($payment->reference_url) }}">{{ $payment->reference }}</a>
            @else
                <span class="text-sm text-secondary-foreground">{{ $payment->reference }}</span>
            @endif
        </td>
        <td>
            <span class="text-sm text-secondary-foreground">
                @if($payment->occurred_at)
                    {{ \Carbon\Carbon::parse($payment->occurred_at)->format('d M, Y H:i') }}
                @else
                    —
                @endif
            </span>
        </td>
        <td>
            <span class="kt-badge kt-badge-sm kt-badge-outline rounded-[30px] {{ $payment->status === 'paid' || $payment->status_label === 'Voldaan' ? 'kt-badge-success' : 'kt-badge-warning' }}">
                {{ $payment->status_label }}
            </span>
            @if($payment->status !== 'paid' && $payment->status !== 'pending' && $payment->status !== 'open')
                <span class="text-xs text-muted-foreground block mt-0.5">{{ $payment->status }}</span>
            @endif
        </td>
        <td>
            @if($payment->reference_url)
                <a href="{{ $paymentDetailUrl($payment->reference_url) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost" title="Details">
                    <i class="ki-filled ki-eye"></i>
                </a>
            @endif
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="text-center py-10 text-sm text-secondary-foreground">
            {{ $emptyMessage ?? 'Geen betalingen gevonden' }}
        </td>
    </tr>
@endforelse
