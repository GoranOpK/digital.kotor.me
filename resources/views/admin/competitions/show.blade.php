@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
    }
    .admin-page {
        background: #f9fafb;
        min-height: 100vh;
        padding: 24px 0;
    }
    .page-header {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 24px;
        border-radius: 16px;
        margin-bottom: 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .page-header h1 {
        color: #fff;
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    .btn {
        padding: 10px 20px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        margin-left: 8px;
    }
    .btn-primary {
        background: #fff;
        color: var(--primary);
    }
    .btn-success {
        background: #10b981;
        color: #fff;
    }
    .btn-danger {
        background: #ef4444;
        color: #fff;
    }
    .info-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .status-badge {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 9999px;
        font-size: 14px;
        font-weight: 600;
    }
    .status-draft { background: #fef3c7; color: #92400e; }
    .status-published { background: #d1fae5; color: #065f46; }
    .status-closed { background: #fee2e2; color: #991b1b; }
    .status-submitted { background: #dbeafe; color: #1e40af; }
    .status-evaluated { background: #d1fae5; color: #065f46; }
    .status-approved { background: #d1fae5; color: #065f46; }
    .status-rejected { background: #fee2e2; color: #991b1b; }
    .countdown-timer {
        font-family: monospace;
        font-weight: 700;
        color: #ef4444;
        font-size: 18px;
        background: #fef2f2;
        padding: 8px 16px;
        border-radius: 8px;
        display: inline-block;
        border: 1px solid #fee2e2;
    }
</style>

<div class="admin-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>{{ $competition->title }}</h1>
            <div>
                @if(isset($isAdmin) && $isAdmin)
                    @if($competition->status === 'draft')
                        <form method="POST" action="{{ route('admin.competitions.publish', $competition) }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-success">Objavi konkurs</button>
                        </form>
                    @endif
                @endif
                @if($competition->status === 'published' && (isset($isSuperAdmin) && $isSuperAdmin || isset($isChairman) && $isChairman) && isset($isDeadlinePassed) && $isDeadlinePassed)
                    <form method="POST" action="{{ route('admin.competitions.close', $competition) }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-danger">Zatvori konkurs</button>
                    </form>
                @endif
                @php
                    $showRankingLink = false;
                    if ($competition->status === 'closed') {
                        $showRankingLink = (isset($isSuperAdmin) && $isSuperAdmin) || (isset($isChairman) && $isChairman) || (isset($isCommissionMember) && $isCommissionMember);
                    } elseif ($competition->status === 'published' && isset($isDeadlinePassed) && $isDeadlinePassed) {
                        // Rok za prijave istekao - svi članovi komisije mogu vidjeti rang listu
                        $showRankingLink = (isset($isSuperAdmin) && $isSuperAdmin) || (isset($isChairman) && $isChairman) || (isset($isCommissionMember) && $isCommissionMember);
                    } elseif ($competition->status === 'published') {
                        // Rok još nije istekao - samo superadmin i predsjednik
                        $showRankingLink = (isset($isSuperAdmin) && $isSuperAdmin) || (isset($isChairman) && $isChairman);
                    }
                @endphp
                @if($showRankingLink)
                    <a href="{{ route('admin.competitions.ranking', $competition) }}" class="btn" style="background: #8b5cf6; color: #fff;">Rang lista</a>
                @endif
                @if(isset($isAdmin) && $isAdmin && !in_array($competition->status, ['closed', 'completed']))
                    <a href="{{ route('admin.competitions.edit', $competition) }}" class="btn btn-primary">Izmijeni</a>
                    <form action="{{ route('admin.competitions.destroy', $competition) }}" method="POST" style="display: inline;" onsubmit="return confirm('Da li ste sigurni da želite da obrišete ovaj konkurs?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Obriši</button>
                    </form>
                @else
                    {{-- Članovi komisije mogu da vide i pristupaju, ali ne mogu da edituju i brišu --}}
                @endif
            </div>
        </div>

        @if(session('success'))
            <div style="background: #d1fae5; border: 1px solid #10b981; color: #065f46; padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                <ul style="margin: 0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="info-card">
            <h2 style="font-size: 20px; margin-bottom: 16px;">Osnovne informacije</h2>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                <div>
                    <p><strong>Status:</strong> <span class="status-badge status-{{ $competition->status }}">{{ $competition->status }}</span></p>
                    <p><strong>Budžet:</strong> {{ number_format($competition->budget ?? 0, 2, ',', '.') }} €</p>
                    <p><strong>Maksimalna podrška:</strong> {{ $competition->max_support_percentage ?? 30 }}%</p>
                    @if(!isset($isCompetitionAdmin) || !$isCompetitionAdmin)
                        <p><strong>Broj prijava:</strong> {{ $applications->total() }}</p>
                    @endif
                    @if($competition->commission)
                        <p><strong>Komisija:</strong> 
                            <a href="{{ route('admin.commissions.show', $competition->commission) }}" style="color: var(--primary); text-decoration: underline;">
                                {{ $competition->commission->name }} ({{ $competition->commission->year }})
                            </a>
                        </p>
                    @else
                        <p><strong>Komisija:</strong> <span style="color: #6b7280;">Nije dodijeljena</span></p>
                    @endif
                </div>
                <div>
                    <p><strong>Rok za prijave:</strong> {{ $competition->deadline_days ?? 20 }} dana</p>
                    @if($competition->published_at && $competition->deadline)
                        <p><strong>Datum objave:</strong> {{ $competition->published_at->format('d.m.Y H:i') }}</p>
                        <p><strong>Datum početka:</strong> {{ $competition->start_date ? $competition->start_date->format('d.m.Y') : 'N/A' }}</p>
                        <p><strong>Datum isteka:</strong> {{ $competition->deadline->subSecond()->format('d.m.Y H:i') }}</p>
                        @if($competition->status === 'published')
                            @if($competition->is_upcoming)
                                <p><strong>Počinje za:</strong><br>
                                    <span class="countdown-timer" data-deadline="{{ $competition->start_date->startOfDay()->format('Y-m-d H:i:s') }}" style="color: #3b82f6; background: #eff6ff; border-color: #dbeafe;">
                                        Učitavanje...
                                    </span>
                                </p>
                            @else
                                <p><strong>Preostalo vremena:</strong><br>
                                    <span class="countdown-timer" data-deadline="{{ $competition->deadline->format('Y-m-d H:i:s') }}">
                                        Učitavanje...
                                    </span>
                                </p>
                            @endif
                        @endif
                    @endif
                    @if($competition->closed_at)
                        <p><strong>Datum zatvaranja:</strong> {{ $competition->closed_at->format('d.m.Y H:i') }}</p>
                    @endif
                </div>
            </div>
        </div>

        @php
            $daysUntilApplicationDeadline = $competition->getDaysUntilApplicationDeadline();
            $daysUntilEvaluationDeadline = $competition->getDaysUntilEvaluationDeadline();
            $isApplicationDeadlinePassed = $competition->isApplicationDeadlinePassed();
            $isEvaluationDeadlinePassed = $competition->isEvaluationDeadlinePassed();
        @endphp

        @if($competition->status === 'published' && $daysUntilApplicationDeadline !== null && !$isApplicationDeadlinePassed)
            <div class="info-card" style="border-left: 4px solid {{ $daysUntilApplicationDeadline <= 3 ? '#ef4444' : ($daysUntilApplicationDeadline <= 7 ? '#f59e0b' : '#10b981') }};">
                <h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
                    <span>📅</span>
                    <span>Rok za prijave na konkurs</span>
                </h3>
                <p style="color: {{ $daysUntilApplicationDeadline <= 3 ? '#991b1b' : ($daysUntilApplicationDeadline <= 7 ? '#92400e' : '#065f46') }}; font-weight: 600; font-size: 18px; margin: 8px 0;">
                    Preostalo vremena: <strong>{{ $daysUntilApplicationDeadline }} {{ $daysUntilApplicationDeadline == 1 ? 'dan' : ($daysUntilApplicationDeadline < 5 ? 'dana' : 'dana') }}</strong>
                </p>
                <p style="color: #6b7280; margin: 0;">
                    Rok za prijave: <strong>{{ $competition->deadline ? $competition->deadline->format('d.m.Y H:i') : 'N/A' }}</strong>
                </p>
            </div>
        @endif

        @if((($competition->status === 'published' && $isApplicationDeadlinePassed) || $competition->status === 'closed') && $daysUntilEvaluationDeadline !== null)
            <div class="info-card" style="border-left: 4px solid {{ $daysUntilEvaluationDeadline <= 3 ? '#ef4444' : ($daysUntilEvaluationDeadline <= 7 ? '#f59e0b' : '#10b981') }};">
                <h3 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
                    <span>⏰</span>
                    <span>Rok za donošenje odluke i zatvaranje konkursa</span>
                </h3>
                @if($isEvaluationDeadlinePassed)
                    <p style="color: #991b1b; font-weight: 600; font-size: 16px; margin: 8px 0;">
                        ❌ Rok za donošenje odluke je istekao
                    </p>
                    <p style="color: #6b7280; margin: 0;">
                        Komisija je dužna donijeti odluku u roku od 30 dana od dana zatvaranja prijava na konkurs. Preostalo vremena: <strong>0 dana</strong>
                    </p>
                @else
                    <p style="color: {{ $daysUntilEvaluationDeadline <= 3 ? '#991b1b' : ($daysUntilEvaluationDeadline <= 7 ? '#92400e' : '#065f46') }}; font-weight: 600; font-size: 18px; margin: 8px 0;">
                        Preostalo vremena: <strong>{{ $daysUntilEvaluationDeadline }} {{ $daysUntilEvaluationDeadline == 1 ? 'dan' : ($daysUntilEvaluationDeadline < 5 ? 'dana' : 'dana') }}</strong>
                    </p>
                    <p style="color: #6b7280; margin: 0;">
                        Komisija je dužna donijeti odluku u roku od 30 dana od dana zatvaranja prijava na konkurs.
                        @php $evalDeadline = $competition->getEvaluationDeadlineDate(); $closedAt = $competition->getApplicationsClosedAt(); @endphp
                        @if($evalDeadline && $closedAt)
                            Datum zatvaranja prijava: <strong>{{ $closedAt->format('d.m.Y H:i') }}</strong> | 
                            Rok za odluku: <strong>{{ $evalDeadline->format('d.m.Y H:i') }}</strong>
                        @endif
                    </p>
                @endif
            </div>
        @endif

        @if(!isset($isCompetitionAdmin) || !$isCompetitionAdmin)
        <div class="info-card">
            <h2 style="font-size: 20px; margin-bottom: 16px;">Prijave</h2>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 12px; text-align: left;">Naziv biznis plana</th>
                            <th style="padding: 12px; text-align: left;">Podnosilac</th>
                            <th style="padding: 12px; text-align: left;">Status prijave</th>
                            <th style="padding: 12px; text-align: left;">Obrazac</th>
                            <th style="padding: 12px; text-align: left;">Biznis Plan</th>
                            <th style="padding: 12px; text-align: left;">Datum podnošenja</th>
                            <th style="padding: 12px; text-align: left;">Akcije</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $app)
                            @php
                                $isObrazacComplete = $app->isObrazacComplete();
                                $statusLabels = [
                                    'draft' => 'Nacrt',
                                    'submitted' => 'U obradi',
                                    'evaluated' => 'Ocjenjena',
                                    'approved' => 'Odobrena',
                                    'rejected' => 'Odbijena',
                                ];
                                $statusClass = 'status-' . $app->status;
                                
                                // Badge za Obrazac
                                $obrazacLabel = null;
                                $obrazacClass = 'status-draft';
                                $obrazacUrl = null;
                                if ($isObrazacComplete) {
                                    if ($app->applicant_type === 'preduzetnica') {
                                        $obrazacLabel = 'Obrazac 1a popunjen';
                                        $obrazacClass = 'status-evaluated';
                                    } elseif (in_array($app->applicant_type, ['doo', 'ostalo'])) {
                                        $obrazacLabel = 'Obrazac 1b popunjen';
                                        $obrazacClass = 'status-evaluated';
                                    }
                                    $obrazacUrl = route('applications.create', $app->competition_id) . '?application_id=' . $app->id;
                                } else {
                                    if ($app->applicant_type === 'preduzetnica') {
                                        $obrazacLabel = 'Obrazac 1a - Nacrt';
                                    } elseif (in_array($app->applicant_type, ['doo', 'ostalo'])) {
                                        $obrazacLabel = 'Obrazac 1b - Nacrt';
                                    }
                                    $obrazacUrl = route('applications.create', $app->competition_id) . '?application_id=' . $app->id;
                                }
                                
                                // Badge za Biznis Plan
                                $bizPlanLabel = null;
                                $bizPlanClass = 'status-draft';
                                $bizPlanUrl = null;
                                if ($app->businessPlan) {
                                    if ($app->businessPlan->isComplete()) {
                                        $bizPlanLabel = 'Biznis Plan - popunjen';
                                        $bizPlanClass = 'status-evaluated';
                                    } else {
                                        $bizPlanLabel = 'Biznis Plan - nacrt';
                                        $bizPlanClass = 'status-draft';
                                    }
                                    $bizPlanUrl = route('applications.business-plan.create', $app);
                                } elseif ($isObrazacComplete) {
                                    $bizPlanLabel = 'Biznis Plan - nacrt';
                                    $bizPlanClass = 'status-draft';
                                    $bizPlanUrl = route('applications.business-plan.create', $app);
                                }
                            @endphp
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 12px; vertical-align: top;">{{ $app->business_plan_name }}</td>
                                <td style="padding: 12px; vertical-align: top;">{{ $app->user->name ?? 'N/A' }}</td>
                                <td style="padding: 12px; vertical-align: top;">
                                    <span class="status-badge {{ $statusClass }}" style="font-size: 11px; padding: 3px 10px;">
                                        {{ $statusLabels[$app->status] ?? $app->status }}
                                    </span>
                                </td>
                                <td style="padding: 12px; vertical-align: top;">
                                    @if($obrazacLabel && $obrazacUrl)
                                        <a href="{{ $obrazacUrl }}" class="status-badge {{ $obrazacClass }}" style="font-size: 11px; padding: 3px 10px; text-decoration: none; cursor: pointer; display: inline-block;">
                                            {{ $obrazacLabel }}
                                        </a>
                                    @else
                                        <span class="status-badge status-draft" style="font-size: 11px; padding: 3px 10px;">Nije popunjen</span>
                                    @endif
                                </td>
                                <td style="padding: 12px; vertical-align: top;">
                                    @if($bizPlanLabel && $bizPlanUrl)
                                        <a href="{{ $bizPlanUrl }}" class="status-badge {{ $bizPlanClass }}" style="font-size: 11px; padding: 3px 10px; text-decoration: none; cursor: pointer; display: inline-block;">
                                            {{ $bizPlanLabel }}
                                        </a>
                                    @else
                                        <span class="status-badge status-draft" style="font-size: 11px; padding: 3px 10px;">Nije dostupan</span>
                                    @endif
                                </td>
                                <td style="padding: 12px; vertical-align: top; font-size: 12px;">
                                    {{ $app->submitted_at ? $app->submitted_at->format('d.m.Y H:i') : '-' }}
                                </td>
                                <td style="padding: 12px; vertical-align: top;">
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                        <a href="{{ route('admin.applications.show', $app) }}" class="btn" style="background: #6b7280; color: #fff; padding: 4px 12px; font-size: 12px; text-decoration: none;">Pregled prijave</a>
                                        @php
                                            $userRole = auth()->user()->role ? auth()->user()->role->name : null;
                                            $isKomisija = $userRole === 'komisija';
                                            
                                            // Provjeri da li su svi članovi komisije ocjenili prijavu
                                            $allEvaluated = false;
                                            $buttonText = 'Ocijeni';
                                            
                                            if ($isKomisija && $competition->commission) {
                                                $commission = $competition->commission;
                                                $totalMembers = $commission->activeMembers()->count();
                                                
                                                // Broj različitih članova koji su ocjenili prijavu
                                                $evaluatedMemberIds = \App\Models\EvaluationScore::where('application_id', $app->id)
                                                    ->whereIn('commission_member_id', $commission->activeMembers()->pluck('id'))
                                                    ->pluck('commission_member_id')
                                                    ->unique()
                                                    ->count();
                                                
                                                $allEvaluated = $evaluatedMemberIds >= $totalMembers;
                                                
                                                // Ako su svi ocjenili ILI ako je prijava odbijena, promijeni tekst dugmeta
                                                if ($allEvaluated || $app->status === 'rejected') {
                                                    $buttonText = 'Ocjene';
                                                }
                                            }
                                        @endphp
                                        @if($isKomisija)
                                            <a href="{{ route('evaluation.create', $app) }}" class="btn" style="background: var(--primary); color: #fff; padding: 4px 12px; font-size: 12px; text-decoration: none;">{{ $buttonText }}</a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" style="padding: 40px; text-align: center; color: #6b7280;">
                                    Nema prijava na ovaj konkurs.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 20px;">
                {{ $applications->links() }}
            </div>
        </div>
        @endif
    </div>
</div>

<script>
    function updateCountdowns() {
        const timers = document.querySelectorAll('.countdown-timer');
        
        timers.forEach(timer => {
            const deadlineStr = timer.getAttribute('data-deadline');
            const deadline = new Date(deadlineStr).getTime();
            const now = new Date().getTime();
            const distance = deadline - now;
            
            if (distance < 0) {
                timer.innerHTML = "ISTEKLO";
                timer.style.color = "#dc2626";
                timer.style.background = "#fee2e2";
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            let html = "";
            if (days > 0) html += days + "d ";
            html += (hours < 10 ? "0" + hours : hours) + "h " + 
                    (minutes < 10 ? "0" + minutes : minutes) + "m " + 
                    (seconds < 10 ? "0" + seconds : seconds) + "s";
            
            timer.innerHTML = html;
            
            // Boja upozorenja ako je manje od 24h
            if (distance < (1000 * 60 * 60 * 24)) {
                timer.style.color = "#ef4444";
                timer.style.background = "#fef2f2";
            } else {
                timer.style.color = "#059669";
                timer.style.background = "#f0fdf4";
            }
        });
    }

    // Pokreni odmah i postavi interval
    updateCountdowns();
    setInterval(updateCountdowns, 1000);
</script>
@endsection

