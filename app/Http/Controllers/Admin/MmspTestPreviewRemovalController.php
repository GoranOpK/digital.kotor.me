<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Services\Competitions\MmspTestPreviewRemoval;
use App\Services\Competitions\MmspTestPreviewRemovalException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class MmspTestPreviewRemovalController extends Controller
{
    public function destroy(
        Request $request,
        Competition $competition,
        MmspTestPreviewRemoval $removal,
    ): RedirectResponse {
        try {
            $result = $removal->execute($request->user(), $competition, (string) $request->input('confirmation', ''));
        } catch (MmspTestPreviewRemovalException $exception) {
            return back()->withErrors(['confirmation' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'confirmation' => 'Uklanjanje je prekinuto. Konkurs, prijave i dokumenti nijesu obrisani.',
            ]);
        }

        $message = 'Testni MMSP konkurs i njegove testne prijave su uklonjeni. Zvanični konkursi i komisija nijesu dirani.';
        if ($result['failed'] !== []) {
            $message .= ' Ovi fajlovi su ostali i mogu se ručno ukloniti nakon provjere: '.implode(', ', $result['failed']).'.';
        }
        if ($result['audit_warning'] !== null) {
            $message .= ' '.$result['audit_warning'];
        }

        return redirect()
            ->route('admin.competitions.index')
            ->with('success', $message);
    }
}
