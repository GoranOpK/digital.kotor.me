<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Kontroler za upravljanje povratnim informacijama
 * 
 * Omogućava korisnicima da pošalju feedback i
 * administratorima da ga pregledaju i odgovaraju
 */
class FeedbackController extends Controller
{
    /**
     * Prikaži formu za slanje povratne informacije
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('feedback.create');
    }

    /**
     * Sačuvaj novu povratnu informaciju
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        $feedback = new Feedback($validated);
        
        // Ako je korisnik prijavljen, automatski dodijeli user_id
        if (Auth::check()) {
            $feedback->user_id = Auth::id();
        }

        $feedback->save();

        return redirect()->route('feedback.create')
            ->with('success', 'Hvala vam na povratnoj informaciji! Odgovorićemo u najkraćem mogućem roku.');
    }

    /**
     * Prikaži listu svih povratnih informacija (samo za administratore)
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $feedbacks = Feedback::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('feedback.index', compact('feedbacks'));
    }

    /**
     * Prikaži pojedinačnu povratnu informaciju (samo za administratore)
     * 
     * @param  \App\Models\Feedback  $feedback
     * @return \Illuminate\View\View
     */
    public function show(Feedback $feedback)
    {
        $feedback->load('user');
        return view('feedback.show', compact('feedback'));
    }

    /**
     * Ažuriraj status i odgovor na povratnu informaciju (samo za administratore)
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Feedback  $feedback
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Feedback $feedback)
    {
        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(Feedback::getStatuses())),
            'admin_response' => 'nullable|string|max:2000',
        ]);

        $feedback->status = $validated['status'];
        $feedback->admin_response = $validated['admin_response'];
        
        if ($validated['admin_response'] && !$feedback->responded_at) {
            $feedback->responded_at = now();
        }

        $feedback->save();

        return redirect()->route('admin.feedback.show', $feedback)
            ->with('success', 'Povratna informacija je ažurirana.');
    }
}
