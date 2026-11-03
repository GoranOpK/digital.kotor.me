<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Competitions\ArchivedTestCompetitionRemoval;
use App\Services\Competitions\ArchivedTestCompetitionRemovalException;
use Illuminate\Http\Request;
use Throwable;

class ArchivedTestCompetitionRemovalController extends Controller
{
    public function show()
    {
        $this->guard();

        return view('admin.competitions.archive-removal', [
            'preview' => null,
        ]);
    }

    public function preview(Request $request, ArchivedTestCompetitionRemoval $removal)
    {
        $this->guard();
        $competitionId = $this->singleCompetitionId($request);

        try {
            $preview = $removal->preview($request->user(), $competitionId);
        } catch (ArchivedTestCompetitionRemovalException $exception) {
            abort($exception->status, $exception->getMessage());
        }

        return view('admin.competitions.archive-removal', [
            'preview' => $preview,
        ]);
    }

    public function destroy(Request $request, ArchivedTestCompetitionRemoval $removal)
    {
        $this->guard();
        $competitionId = $this->singleCompetitionId($request);

        try {
            $result = $removal->execute(
                $request->user(),
                $competitionId,
                (string) $request->input('confirmation', '')
            );
        } catch (ArchivedTestCompetitionRemovalException $exception) {
            abort($exception->status, $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'competition_id' => 'Uklanjanje je prekinuto. Podaci konkursa nijesu djelimično obrisani.',
            ]);
        }

        $message = 'Konkurs '.$competitionId.' je uklonjen iz baze.';
        if ($result['failed'] !== []) {
            $message .= ' Ovi fajlovi su ostali i mogu se ručno ukloniti nakon provjere: '.implode(', ', $result['failed']).'.';
        }
        if ($result['kept'] !== []) {
            $message .= ' Zadržani fajlovi koji pripadaju drugom zapisu: '.implode(', ', $result['kept']).'.';
        }
        if ($result['audit_warning'] !== null) {
            $message .= ' '.$result['audit_warning'];
        }

        return redirect()
            ->route('admin.archive-test-competition.show')
            ->with('success', $message);
    }

    private function guard(): void
    {
        $user = auth()->user();
        if ($user === null || $user->role === null || $user->role->name !== 'superadmin') {
            abort(403, 'Samo super administrator može ukloniti odobreni testni konkurs iz arhive.');
        }
    }

    private function singleCompetitionId(Request $request): int
    {
        $value = $request->input('competition_id');
        if (is_array($value) || ! is_numeric($value) || (string) (int) $value !== (string) $value) {
            abort(422, 'Mora se navesti tačno jedan ID konkursa.');
        }

        return (int) $value;
    }
}
