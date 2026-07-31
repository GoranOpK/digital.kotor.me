<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Notice;
use App\Services\Competitions\CompetitionDecisionDocumentBuilder;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PublicNoticeContentController extends Controller
{
    public function __construct(
        private readonly CompetitionDecisionDocumentBuilder $decisionDocumentBuilder,
    ) {}

    public function show(Notice $notice): View|Response
    {
        return match ($notice->content_delivery) {
            'competition_decision_html' => $this->renderCompetitionDecision($notice),
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

    private function unsupportedDelivery(Notice $notice): Response
    {
        return response()->view('notices.unsupported-delivery', [
            'notice' => $notice,
        ], 404);
    }
}
