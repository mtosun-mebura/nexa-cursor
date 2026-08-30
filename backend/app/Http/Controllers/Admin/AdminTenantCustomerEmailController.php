<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Traits\TenantFilter;
use App\Http\Controllers\Controller;
use App\Models\TenantCustomerEmail;
use App\Services\EnvService;
use App\Services\TenantCustomerMailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RuntimeException;

class AdminTenantCustomerEmailController extends Controller
{
    use TenantFilter;

    public function index(): View
    {
        $this->ensureAccess();

        $query = TenantCustomerEmail::query()
            ->with('company')
            ->orderByDesc('id');
        $this->applyTenantFilter($query);

        $emails = $query->limit(2000)->get();
        $isSuperAdmin = (bool) auth()->user()?->hasRole('super-admin');
        $showCompany = $isSuperAdmin && ! session('selected_tenant');

        return view('admin.customer-emails.index', [
            'emails' => $emails,
            'showCompany' => $showCompany,
            'typeLabels' => TenantCustomerEmail::TYPE_LABELS,
            'statusLabels' => TenantCustomerEmail::STATUS_LABELS,
        ]);
    }

    public function show(TenantCustomerEmail $customerEmail): View
    {
        $this->authorizeEmail($customerEmail);

        return view('admin.customer-emails.show', [
            'email' => $customerEmail->load('company'),
        ]);
    }

    public function preview(TenantCustomerEmail $customerEmail, TenantCustomerMailService $mail): Response
    {
        $this->authorizeEmail($customerEmail);

        $html = $mail->mailboxPreviewHtml($customerEmail->load('company'));

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Robots-Tag' => 'noindex, nofollow',
            'Content-Security-Policy' => "default-src 'none'; img-src https: http: data: blob:; style-src 'unsafe-inline'; font-src https: data:; frame-ancestors 'self';",
        ]);
    }

    public function resend(Request $request, TenantCustomerEmail $customerEmail, TenantCustomerMailService $mail, EnvService $env): RedirectResponse|JsonResponse
    {
        $this->authorizeEmail($customerEmail);

        try {
            $copy = $mail->resend($customerEmail);
        } catch (RuntimeException $e) {
            return $this->resendResult($request, $customerEmail, false, $env->explainMailSendException($e));
        } catch (\Throwable $e) {
            return $this->resendResult($request, $customerEmail, false, $env->explainMailSendException($e));
        }

        if ($copy->status !== TenantCustomerEmail::STATUS_SENT) {
            $raw = $copy->error_message ?: 'De e-mail kon niet opnieuw worden verstuurd.';
            $explained = $env->explainMailSendException(new RuntimeException($raw));

            return $this->resendResult($request, $copy, false, $explained);
        }

        return $this->resendResult(
            $request,
            $copy,
            true,
            'E-mail opnieuw verstuurd naar '.$copy->recipient_email.'.'
        );
    }

    private function resendResult(Request $request, TenantCustomerEmail $email, bool $ok, string $message): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => $ok,
                'message' => $message,
                'redirect' => route('admin.customer-emails.show', $email),
            ], $ok ? 200 : 422);
        }

        $redirect = redirect()->route('admin.customer-emails.show', $email);

        return $ok
            ? $redirect->with('success', $message)
            : $redirect->with('error', $message);
    }

    private function ensureAccess(): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }
        if ($user->hasRole('super-admin') || $user->hasRole('company-admin')) {
            return;
        }
        abort(403, 'Alleen de bedrijfsbeheerder kan e-mailcommunicatie inzien.');
    }

    private function authorizeEmail(TenantCustomerEmail $email): void
    {
        $this->ensureAccess();
        if (! $this->canAccessResource($email)) {
            abort(403);
        }
    }
}
