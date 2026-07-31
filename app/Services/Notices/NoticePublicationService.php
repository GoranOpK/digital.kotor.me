<?php

namespace App\Services\Notices;

use App\Models\Notice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class NoticePublicationService
{
    /**
     * Create a visible Notice and optionally hide a superseded Notice from the active panel.
     *
     * @param  array{
     *     title: string,
     *     short_description?: string|null,
     *     source_type: string,
     *     source_id: int,
     *     content_delivery: string,
     *     supersedes_notice_id?: int|null
     * }  $payload
     *
     * @throws ValidationException
     */
    public function publish(array $payload): Notice
    {
        $validated = Validator::make($payload, [
            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string'],
            'source_type' => ['required', 'string', 'max:64'],
            'source_id' => ['required', 'integer'],
            'content_delivery' => ['required', 'string', 'max:64'],
            'supersedes_notice_id' => ['nullable', 'integer', 'exists:notices,id'],
        ])->validate();

        return DB::transaction(function () use ($validated) {
            if (! empty($validated['supersedes_notice_id'])) {
                Notice::query()
                    ->whereKey($validated['supersedes_notice_id'])
                    ->update(['visible_in_active_panel' => false]);
            }

            return Notice::create([
                'title' => $validated['title'],
                'short_description' => $validated['short_description'] ?? null,
                'visible_in_active_panel' => true,
                'source_type' => $validated['source_type'],
                'source_id' => $validated['source_id'],
                'content_delivery' => $validated['content_delivery'],
                'published_at' => now(),
            ]);
        });
    }
}
