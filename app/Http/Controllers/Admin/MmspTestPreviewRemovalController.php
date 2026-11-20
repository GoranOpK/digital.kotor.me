<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Services\Competitions\MmspTestPreviewRemoval;
use App\Services\Competitions\MmspTestPreviewRemovalException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MmspTestPreviewRemovalController extends Controller
{
    public function destroy(
        Request $request,
        Competition $competition,
        MmspTestPreviewRemoval $removal,
    ): RedirectResponse {
        try {
            $removal->execute($request->user(), $competition, (string) $request->input('confirmation', ''));
        } catch (MmspTestPreviewRemovalException $exception) {
            return back()->withErrors(['confirmation' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.competitions.index')
            ->with('success', 'Testni MMSP konkurs i njegove testne prijave su uklonjeni. Zvanični konkursi i komisija nijesu dirani.');
    }
}
