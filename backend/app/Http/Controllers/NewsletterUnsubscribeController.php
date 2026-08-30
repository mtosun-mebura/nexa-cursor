<?php

namespace App\Http\Controllers;

use App\Models\NewsletterProspect;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsletterUnsubscribeController extends Controller
{
    public function show(string $token): View
    {
        $prospect = NewsletterProspect::query()->where('unsubscribe_token', $token)->firstOrFail();
        if ($prospect->isSubscribed()) {
            $prospect->unsubscribe();
        }

        return view('newsletter.unsubscribed', [
            'companyName' => $prospect->company_name,
        ]);
    }

    public function store(Request $request, string $token): View
    {
        return $this->show($token);
    }
}
