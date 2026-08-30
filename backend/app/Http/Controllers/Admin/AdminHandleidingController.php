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
        $this->markWelcomeHandleidingSeen();

        return view('admin.handleiding.index', [
            'pages' => AdminHandleiding::pagesForCurrentUser(),
        ]);
    }

    public function show(string $slug): View|Response
    {
        $page = AdminHandleiding::page($slug);
        $pages = AdminHandleiding::pagesForCurrentUser();

        if ($page === null || empty($page['view']) || ! view()->exists($page['view']) || ! array_key_exists($slug, $pages)) {
            abort(404);
        }

        $this->markWelcomeHandleidingSeen();

        return view('admin.handleiding.show', [
            'slug' => $slug,
            'page' => $page,
            'pages' => $pages,
        ]);
    }

    private function markWelcomeHandleidingSeen(): void
    {
        $user = auth()->user();
        if ($user && $user->welcome_handleiding_pending) {
            $user->forceFill(['welcome_handleiding_pending' => false])->save();
        }
    }
}
