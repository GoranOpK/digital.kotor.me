<?php

namespace App\Http\Requests\Concerns;

use App\Services\CulturalMedia\CulturalMediaFileValidator;

trait HandlesEventCoverUpload
{
    /**
     * @return array<string, mixed>
     */
    protected function eventCoverUploadRules(): array
    {
        return [
            'cover_file' => ['nullable', 'file', 'max:'.CulturalMediaFileValidator::MAX_KILOBYTES],
            'remove_cover' => ['sometimes', 'boolean'],
            'cover_media_id' => ['prohibited'],
            'proposed_cover_media_id' => ['prohibited'],
        ];
    }

    public function wantsCoverRemoved(): bool
    {
        return $this->boolean('remove_cover');
    }
}
