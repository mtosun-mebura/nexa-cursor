<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Support\Tenancy\CentralDomains;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Interne sales/preview-hub op centrale hosts (localhost, nexasuite.nl).
 */
class MarketingPreviewController extends Controller
{
    /** @var list<string> */
    public const PAGES = [
        'index',
        'strategie',
        'taxi',
        'contractvervoer',
        'website',
        'prijzen',
        'website-copy',
    ];

    public function index(Request $request): View
    {
        $this->assertCentralHost($request);

        return view('marketing.index', [
            'pageKey' => 'index',
            'title' => 'NEXA Suite — Marketing & verkoop',
        ]);
    }

    public function show(Request $request, string $page): View
    {
        $this->assertCentralHost($request);

        $page = strtolower($page);
        if ($page === 'index' || ! in_array($page, self::PAGES, true)) {
            throw new NotFoundHttpException;
        }

        $titles = [
            'strategie' => 'Verkoopstrategie',
            'taxi' => 'Nexa Taxi',
            'contractvervoer' => 'Contractvervoer',
            'website' => 'Website builder',
            'prijzen' => 'Prijzen',
            'website-copy' => 'Website-copy nexasuite.nl',
        ];

        return view('marketing.'.$page, [
            'pageKey' => $page,
            'title' => ($titles[$page] ?? ucfirst($page)).' — NEXA Marketing',
        ]);
    }

    private function assertCentralHost(Request $request): void
    {
        $host = strtolower((string) $request->getHost());

        // Alleen de echte Host-header telt. Een gesimuleerde tenant via ?_tenant_host=
        // (sessie op localhost) mag /marketing niet verbergen.
        if (CentralDomains::isCentral($host)) {
            return;
        }

        if (! app()->isProduction() && in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return;
        }

        abort(404);
    }
}
