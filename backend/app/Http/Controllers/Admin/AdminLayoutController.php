<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Layout;
use Illuminate\Http\Request;

class AdminLayoutController extends Controller
{
    public function index()
    {
        $layouts = Layout::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.layouts.index', compact('layouts'));
    }

    public function create()
    {
        // Template variabelen voor de view
        $templateVariables = [
            'content' => 'Hoofdinhoud',
            'title' => 'Pagina titel',
            'company_name' => 'Bedrijfsnaam',
            'user_name' => 'Gebruikersnaam',
            'logo_url' => 'Logo URL',
            'footer_text' => 'Footer tekst'
        ];
        
        return view('admin.layouts.create', compact('templateVariables'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:email,landing_page,dashboard,profile,vacancy,custom',
            'version' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'html_content' => 'required|string',
            'css_content' => 'nullable|string',
            'header_color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'footer_color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'logo_url' => 'nullable|url|max:500',
            'footer_text' => 'nullable|string|max:255',
            'metadata' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        Layout::create($request->all());
        return redirect()->route('admin.layouts.index')->with('success', 'Layout succesvol aangemaakt.');
    }

    public function show(Layout $layout)
    {
        // Template variabelen voor de view
        $templateVariables = [
            'content' => 'Hoofdinhoud',
            'title' => 'Pagina titel',
            'company_name' => 'Bedrijfsnaam',
            'user_name' => 'Gebruikersnaam',
            'logo_url' => 'Logo URL',
            'footer_text' => 'Footer tekst'
        ];
        
        return view('admin.layouts.show', compact('layout', 'templateVariables'));
    }

    public function edit(Layout $layout)
    {
        // Template variabelen voor de view
        $templateVariables = [
            'content' => 'Hoofdinhoud',
            'title' => 'Pagina titel',
            'company_name' => 'Bedrijfsnaam',
            'user_name' => 'Gebruikersnaam',
            'logo_url' => 'Logo URL',
            'footer_text' => 'Footer tekst'
        ];
        
        return view('admin.layouts.edit', compact('layout', 'templateVariables'));
    }

    public function update(Request $request, Layout $layout)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:email,landing_page,dashboard,profile,vacancy,custom',
            'version' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'html_content' => 'required|string',
            'css_content' => 'nullable|string',
            'header_color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'footer_color' => 'nullable|string|regex:/^#[0-9A-F]{6}$/i',
            'logo_url' => 'nullable|url|max:500',
            'footer_text' => 'nullable|string|max:255',
            'metadata' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $layout->update($request->all());
        return redirect()->route('admin.layouts.index')->with('success', 'Layout succesvol bijgewerkt.');
    }

    public function destroy(Layout $layout)
    {
        $layout->delete();
        return redirect()->route('admin.layouts.index')->with('success', 'Layout succesvol verwijderd.');
    }
}
