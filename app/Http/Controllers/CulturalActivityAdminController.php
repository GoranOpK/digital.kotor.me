<?php

namespace App\Http\Controllers;

use App\Models\CulturalActivityRecord;
use Illuminate\View\View;

class CulturalActivityAdminController extends Controller
{
    public function index(): View
    {
        $records = CulturalActivityRecord::query()
            ->with('actorUser')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.cultural-activity.index', [
            'records' => $records,
        ]);
    }
}
