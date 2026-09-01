<?php

namespace App\Services\Notices;

use App\Models\Notice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class NoticePublicationService
{
    /**
     * Create a visible Notice and optionally hide or publicly revoke a predecessor.
     *
     * @param  array{
     *     title: string,
     *     short_description?: string|null,
     *     source_type: string,
     *     source_id: int,
     *     content_delivery: string,
     *     supersedes_notice_id?: int|null,
     *     public_revoke?: bool,
     *     source_object_id?: int|null,
     *     public_display_date?: string|null
     * }  $payload
     *
     * @throws ValidationException
     */
    public function publish(array $payload): Notice
    {
        $payload['public_revoke'] = (bool) ($payload['public_revoke'] ?? false);

        $validated = Validator::make($payload, [
            'title' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string'],
            'source_type' => ['required', 'string', 'max:64'],
            'source_id' => ['required', 'integer'],
            'content_delivery' => ['required', 'string', 'max:64'],
            'supersedes_notice_id' => ['nullable', 'integer', 'exists:notices,id', 'required_if:public_revoke,true'],
            'public_revoke' => ['boolean'],
            'source_object_id' => ['nullable', 'integer'],
            'public_display_date' => ['nullable', 'date'],
        ])->validate();

        $predecessorId = $validated['supersedes_notice_id'] ?? null;
        $publicRevoke = (bool) $validated['public_revoke'];

        return DB::transaction(function () use ($validated, $predecessorId, $publicRevoke) {
            if ($predecessorId) {
                $predecessorUpdate = [
                    'visible_in_active_panel' => false,
                ];

                if ($publicRevoke) {
                    $predecessorUpdate['publicly_available'] = false;
                }

                Notice::query()
                    ->whereKey($predecessorId)
                    ->update($predecessorUpdate);
            }

            return Notice::create([
                'title' => $validated['title'],
                'short_description' => $validated['short_description'] ?? null,
                'visible_in_active_panel' => true,
                'publicly_available' => true,
                'source_type' => $validated['source_type'],
                'source_id' => $validated['source_id'],
                'content_delivery' => $validated['content_delivery'],
                'superseded_notice_id' => $predecessorId,
                'source_object_id' => $validated['source_object_id'] ?? null,
                'published_at' => now(),
                'public_display_date' => $validated['public_display_date'] ?? null,
            ]);
        });
    }
}
