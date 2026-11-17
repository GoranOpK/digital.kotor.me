<?php

namespace App\Services\Notices;

use App\Models\Notice;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StandalonePdfNoticePublisher
{
    public const SOURCE_TYPE = 'standalone_pdf';

    public const CONTENT_DELIVERY = 'standalone_pdf';

    public function __construct(
        private readonly NoticePublicationService $notices,
    ) {}

    public static function storagePath(Notice $notice): string
    {
        return 'notices/standalone/'.$notice->id.'.pdf';
    }

    /**
     * @throws ValidationException
     */
    public function publish(string $title, string $publicDisplayDate, UploadedFile|string $pdf): Notice
    {
        $validated = Validator::make([
            'title' => $title,
            'public_display_date' => $publicDisplayDate,
        ], [
            'title' => ['required', 'string', 'max:255'],
            'public_display_date' => ['required', 'date'],
        ])->validate();

        $existing = Notice::query()
            ->where('content_delivery', self::CONTENT_DELIVERY)
            ->where('title', $validated['title'])
            ->whereDate('public_display_date', $validated['public_display_date'])
            ->where('publicly_available', true)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($validated, $pdf) {
            $notice = $this->notices->publish([
                'title' => $validated['title'],
                'short_description' => null,
                'source_type' => self::SOURCE_TYPE,
                'source_id' => 0,
                'content_delivery' => self::CONTENT_DELIVERY,
                'public_display_date' => $validated['public_display_date'],
            ]);

            $path = self::storagePath($notice);

            if ($pdf instanceof UploadedFile) {
                $stored = $pdf->storeAs('notices/standalone', $notice->id.'.pdf', 'local');
                if (! is_string($stored) || $stored === '') {
                    throw new RuntimeException('PDF obavještenja nije mogao biti sačuvan.');
                }
            } else {
                if (! is_file($pdf) || ! is_readable($pdf)) {
                    throw new RuntimeException('PDF fajl nije pronađen ili nije čitljiv.');
                }

                $contents = file_get_contents($pdf);
                if ($contents === false || ! Storage::disk('local')->put($path, $contents)) {
                    throw new RuntimeException('PDF obavještenja nije mogao biti sačuvan.');
                }
            }

            return $notice;
        });
    }
}
