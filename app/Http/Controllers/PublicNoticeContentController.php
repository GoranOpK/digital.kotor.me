<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\CompetitionOfficialDecisionCopy;
use App\Models\Notice;
use App\Services\Competitions\CompetitionDecisionDocumentBuilder;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicNoticeContentController extends Controller
{
    public function __construct(
        private readonly CompetitionDecisionDocumentBuilder $decisionDocumentBuilder,
    ) {}

    public function show(Notice $notice): View|Response|BinaryFileResponse
    {
        if (! $notice->publicly_available) {
            abort(404);
        }

        return match ($notice->content_delivery) {
            'competition_decision_html' => $this->renderCompetitionDecision($notice),
            'competition_decision_signed_copy' => $this->serveCompetitionDecisionSignedCopy($notice),
            default => $this->unsupportedDelivery($notice),
        };
    }

    private function renderCompetitionDecision(Notice $notice): View
    {
        $competition = Competition::query()->find($notice->source_id);

        if (! $competition) {
            abort(404);
        }

        $data = $this->decisionDocumentBuilder->build($competition);
        $data['notice'] = $notice;

        return view('notices.competition-decision', $data);
    }

    private function serveCompetitionDecisionSignedCopy(Notice $notice): BinaryFileResponse
    {
        if ($notice->source_object_id === null) {
            abort(404);
        }

        $copy = CompetitionOfficialDecisionCopy::query()->find($notice->source_object_id);

        if (! $copy) {
            abort(404);
        }

        if ((int) $copy->competition_id !== (int) $notice->source_id) {
            abort(404);
        }

        $storagePath = $copy->storage_path;

        if (! is_string($storagePath) || $storagePath === '' || ! Storage::disk('local')->exists($storagePath)) {
            abort(404);
        }

        $path = Storage::disk('local')->path($storagePath);
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mimeType,
        ]);
    }

    private function unsupportedDelivery(Notice $notice): Response
    {
        return response()->view('notices.unsupported-delivery', [
            'notice' => $notice,
        ], 404);
    }
}
