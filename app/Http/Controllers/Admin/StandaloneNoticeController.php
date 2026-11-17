<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Notices\StandalonePdfNoticePublisher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StandaloneNoticeController extends Controller
{
    public function create(): View
    {
        return view('admin.notices.create');
    }

    public function store(Request $request, StandalonePdfNoticePublisher $publisher): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'public_display_date' => ['required', 'date'],
            'pdf' => ['required', 'file', 'mimes:pdf', 'max:5120'],
        ], [
            'title.required' => 'Naslov obavještenja je obavezan.',
            'public_display_date.required' => 'Datum objave je obavezan.',
            'pdf.required' => 'PDF dokument je obavezan.',
            'pdf.mimes' => 'Dokument mora biti PDF fajl.',
            'pdf.max' => 'PDF ne može biti veći od 5 MB.',
        ]);

        $publisher->publish(
            $validated['title'],
            $validated['public_display_date'],
            $validated['pdf'],
        );

        return redirect()
            ->route('admin.notices.create')
            ->with('success', 'Obavještenje je objavljeno na početnoj stranici.');
    }
}
