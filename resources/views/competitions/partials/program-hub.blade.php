<style>
    .programs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 24px;
        margin-bottom: 24px;
    }
    .program-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 2px rgba(0,0,0,.06);
        display: flex;
        flex-direction: column;
        gap: 16px;
        transition: box-shadow 0.2s, transform 0.2s;
        text-decoration: none;
        color: inherit;
    }
    a.program-card:hover {
        box-shadow: 0 8px 24px rgba(11, 61, 145, 0.12);
        transform: translateY(-2px);
    }
    .program-card.is-development {
        border-style: dashed;
        border-color: #d1d5db;
    }
    .program-card-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
    }
    .program-card-icon {
        font-size: 36px;
        line-height: 1;
    }
    .program-card h2 {
        font-size: 20px;
        font-weight: 700;
        color: #111827;
        margin: 0 0 8px;
    }
    .program-card p {
        color: #6b7280;
        font-size: 14px;
        margin: 0;
        line-height: 1.5;
    }
    .program-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        white-space: nowrap;
    }
    .program-badge-active {
        background: #d1fae5;
        color: #065f46;
    }
    .program-badge-development {
        background: #fef3c7;
        color: #92400e;
    }
    .program-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        padding-top: 8px;
        border-top: 1px solid #f3f4f6;
    }
    .program-stat-label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 4px;
    }
    .program-stat-value {
        font-size: 22px;
        font-weight: 800;
        color: var(--primary);
        line-height: 1;
    }
</style>

<div class="programs-grid">
    @foreach($programs as $program)
        @php
            $isAvailable = ($program['status'] ?? '') === \App\Support\CompetitionProgramCatalog::STATUS_ACTIVE
                && \App\Support\CompetitionProgramCatalog::isPubliclyAvailable($program['type'] ?? null);
            $badgeClass = ($program['status'] ?? '') === \App\Support\CompetitionProgramCatalog::STATUS_ACTIVE
                ? 'program-badge-active'
                : 'program-badge-development';
            $badgeLabel = ($program['status'] ?? '') === \App\Support\CompetitionProgramCatalog::STATUS_ACTIVE
                ? 'Aktivan modul'
                : 'U razvoju';
        @endphp
        @if($isAvailable)
            <a href="{{ route('competitions.index', ['type' => $program['type']]) }}" class="program-card">
        @else
            <div class="program-card is-development">
        @endif
                <div class="program-card-header">
                    <div>
                        <div class="program-card-icon">{{ $program['icon'] }}</div>
                    </div>
                    <span class="program-badge {{ $badgeClass }}">{{ $badgeLabel }}</span>
                </div>
                <div>
                    <h2>{{ $program['title'] }}</h2>
                    <p>{{ $program['description'] }}</p>
                </div>
                <div class="program-stats">
                    <div>
                        <span class="program-stat-label">Aktivni</span>
                        <span class="program-stat-value">{{ $program['active_competitions'] ?? 0 }}</span>
                    </div>
                    <div>
                        <span class="program-stat-label">Ukupno</span>
                        <span class="program-stat-value">{{ $program['total_competitions'] ?? 0 }}</span>
                    </div>
                </div>
        @if($isAvailable)
            </a>
        @else
            </div>
        @endif
    @endforeach
</div>
