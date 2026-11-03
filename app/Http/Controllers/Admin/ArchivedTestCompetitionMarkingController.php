<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Competitions\ArchivedTestCompetitionMarking;
use App\Services\Competitions\ArchivedTestCompetitionRemovalException;
use Illuminate\Http\Request;

class ArchivedTestCompetitionMarkingController extends Controller
{
    public function show()
    {
        $this->guard();

        return view('admin.competitions.archive-test-mark', [
            'preview' => null,
        ]);
    }

    public function preview(Request $request, ArchivedTestCompetitionMarking $marking)
    {
        $this->guard();

        try {
            $preview = $marking->preview($request->user(), $this->singleCompetitionId($request));
        } catch (ArchivedTestCompetitionRemovalException $exception) {
            abort($exception->status, $exception->getMessage());
        }

        return view('admin.competitions.archive-test-mark', [
            'preview' => $preview,
        ]);
    }

    public function store(Request $request, ArchivedTestCompetitionMarking $marking)
    {
        $this->guard();
        $competitionId = $this->singleCompetitionId($request);

        try {
            $marking->execute(
                $request->user(),
                $competitionId,
                (string) $request->input('confirmation', '')
            );
        } catch (ArchivedTestCompetitionRemovalException $exception) {
            abort($exception->status, $exception->getMessage());
        }

        return redirect()
            ->route('admin.archive-test-mark.show')
            ->with('success', 'Konkurs '.$competitionId.' je označen kao testni. Podaci konkursa nijesu obrisani.');
    }

    private function guard(): void
    {
        $user = auth()->user();
        if ($user === null || $user->role === null || $user->role->name !== 'superadmin') {
            abort(403, 'Samo super administrator može označiti raniji konkurs kao testni.');
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
