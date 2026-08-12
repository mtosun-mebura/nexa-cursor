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
        'skillmatching',
        'website',
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
            throw new NotFoundHttpException();
        }

        $titles = [
            'strategie' => 'Verkoopstrategie',
            'taxi' => 'Nexa Taxi',
            'contractvervoer' => 'Contractvervoer',
            'skillmatching' => 'Skillmatching',
            'website' => 'Website builder',
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
        $isTenant = app()->bound('resolved_tenant') && app('resolved_tenant') !== null;

        if ($isTenant) {
            abort(404);
        }

        if (CentralDomains::isCentral($host)) {
            return;
        }

        // Extra lokale hostnamen (docker / hosts-bestand).
        if (! app()->isProduction() && in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return;
        }

        abort(404);
    }
}
