<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminHandleiding;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

class AdminHandleidingController extends Controller
{
    public function index(): View
    {
        return view('admin.handleiding.index', [
            'pages' => AdminHandleiding::pages(),
        ]);
    }

    public function show(string $slug): View|Response
    {
        $page = AdminHandleiding::page($slug);

        if ($page === null || empty($page['view']) || ! view()->exists($page['view'])) {
            abort(404);
        }

        return view('admin.handleiding.show', [
            'slug' => $slug,
            'page' => $page,
            'pages' => AdminHandleiding::pages(),
        ]);
    }
}
