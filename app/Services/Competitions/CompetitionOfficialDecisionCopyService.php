<?php

namespace App\Services\Competitions;

use App\Models\Competition;
use App\Models\CompetitionOfficialDecisionCopy;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class CompetitionOfficialDecisionCopyService
{
    public function store(Competition $competition, UploadedFile $file, User $uploader): CompetitionOfficialDecisionCopy
    {
        $directory = 'competitions/'.$competition->id.'/official-decisions';
        $storagePath = $file->store($directory, 'local');

        if (! is_string($storagePath) || $storagePath === '') {
            throw new RuntimeException('Potpisani primjerak nije mogao biti sačuvan.');
        }

        try {
            return CompetitionOfficialDecisionCopy::query()->create([
                'competition_id' => $competition->id,
                'storage_path' => $storagePath,
                'uploaded_by' => $uploader->id,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($storagePath);
            throw $exception;
        }
    }
}
