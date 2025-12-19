@extends('layouts.app')

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
        --secondary: #B8860B;
    }
    .competitions-page {
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
    }
    .page-header h1 {
        color: #fff;
        font-size: 32px;
        font-weight: 700;
        margin: 0 0 8px;
    }
    .competitions-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 24px;
    }
    @media (min-width: 768px) {
        .competitions-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    .competition-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: all 0.2s;
        text-decoration: none;
        display: block;
    }
    .competition-card:hover {
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transform: translateY(-2px);
        border-color: var(--primary);
    }
    .competition-card h3 {
        font-size: 20px;
        font-weight: 700;
        color: var(--primary);
        margin: 0 0 12px;
    }
    .competition-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 16px;
        font-size: 14px;
        color: #6b7280;
    }
    .meta-item {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 9999px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-open {
        background: #d1fae5;
        color: #065f46;
    }
    .status-closed {
        background: #fee2e2;
        color: #991b1b;
    }
    .deadline-info {
        background: #fef3c7;
        border-left: 4px solid #f59e0b;
        padding: 12px 16px;
        border-radius: 8px;
        margin-top: 16px;
    }
    .deadline-info strong {
        color: #92400e;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: #fff;
        border-radius: 16px;
        border: 1px solid #e5e7eb;
    }
    .empty-state-icon {
        font-size: 64px;
        margin-bottom: 16px;
    }
</style>

<div class="competitions-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Konkursi za podršku ženskom preduzetništvu</h1>
            <p style="color: rgba(255,255,255,0.9); margin: 0;">Pregled aktivnih konkursa za podršku ženskom preduzetništvu iz budžeta Opštine Kotor</p>
        </div>

        @if($competitions->count() > 0)
            <div class="competitions-grid">
                @foreach($competitions as $competition)
                    <a href="{{ route('competitions.show', $competition) }}" class="competition-card">
                        <h3>{{ $competition->title }}</h3>
                        
                        <div class="competition-meta">
                            <div class="meta-item">
                                <span>📅</span>
                                <span>Objavljen: {{ $competition->published_at ? $competition->published_at->format('d.m.Y') : 'N/A' }}</span>
                            </div>
                            <div class="meta-item">
                                <span>💰</span>
                                <span>Budžet: {{ number_format($competition->budget ?? 0, 2, ',', '.') }} €</span>
                            </div>
                            <div class="meta-item">
                                <span class="status-badge {{ $competition->is_open ? 'status-open' : ($competition->is_upcoming ? 'status-upcoming' : 'status-closed') }}" 
                                      style="{{ $competition->is_upcoming ? 'background: #dbeafe; color: #1e40af;' : '' }}">
                                    @if($competition->is_open)
                                        Otvoren
                                    @elseif($competition->is_upcoming)
                                        Uskoro počinje
                                    @else
                                        Zatvoren
                                    @endif
                                </span>
                            </div>
                        </div>

                        @if($competition->description)
                            <p style="color: #374151; margin: 0 0 16px; line-height: 1.6;">
                                {{ Str::limit($competition->description, 150) }}
                            </p>
                        @endif

                        @if($competition->is_upcoming)
                            <div style="background: #eff6ff; border-left: 4px solid #3b82f6; padding: 12px 16px; border-radius: 8px; margin-top: 16px;">
                                <strong style="color: #1e40af;">Počinje: {{ $competition->start_date->format('d.m.Y') }}</strong>
                            </div>
                        @elseif($competition->is_open && ($competition->days_remaining > 0 || $competition->hours_remaining > 0 || $competition->minutes_remaining > 0))
                            <div class="deadline-info" data-deadline="{{ $competition->deadline->format('Y-m-d H:i:s') }}">
                                <strong>Preostalo vreme za prijavu: <span class="countdown-{{ $competition->id }}">Učitavanje...</span></strong>
                                <div style="font-size: 12px; color: #92400e; margin-top: 4px;">
                                    Rok za prijave: {{ $competition->deadline->format('d.m.Y H:i') }}
                                </div>
                            </div>
                        @elseif(!$competition->is_open)
                            <div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 12px 16px; border-radius: 8px; margin-top: 16px;">
                                <strong style="color: #991b1b;">Konkurs je zatvoren</strong>
                            </div>
                        @endif
                    </a>
                @endforeach
            </div>
        @else
            <div class="empty-state">
                <div class="empty-state-icon">📋</div>
                <h3 style="color: #374151; margin-bottom: 8px;">Trenutno nema aktivnih konkursa</h3>
                <p style="color: #6b7280; margin: 0;">Novi konkursi će biti objavljeni kada budu raspisani.</p>
            </div>
        @endif

        <!-- Prioriteti za raspodjelu sredstava -->
        <div style="background: #fff; border-radius: 16px; padding: 24px; margin-top: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); background: #f0f9ff; border-left: 4px solid var(--primary);">
            <h2 style="color: var(--primary); font-size: 20px; font-weight: 700; margin: 0 0 16px;">Prioriteti za raspodjelu sredstava</h2>
            <p style="color: #374151; line-height: 1.8; margin-bottom: 12px;">
                Sredstva opredijeljena Budžetom Opštine Kotor raspodjeljuju se za biznis planove koji:
            </p>
            <ul style="color: #374151; line-height: 1.8; margin: 0; padding-left: 20px;">
                <li><strong>Podstiču ekonomski razvoj opštine</strong> (započinjanje biznisa, povećanje zaposlenosti i kreiranje novih radnih mjesta, smanjenje sive ekonomije, povećanje životnog standarda, razvoj lokalne zajednice, osvajanje novih tržišta i povećanje konkurentnosti, kreiranje nove ponude, osnaživanje žena u biznisu itd);</li>
                <li><strong>Podstiču razvoj turizma</strong> (naročito razvoj ruralnog turizma - pružanje usluga u seoskom domaćinstvu, etno sela, turistička valorizacija kulturnog potencijala, tradicije i kulturne posebnosti, bogatija i raznovrsnija turistička ponuda);</li>
                <li><strong>Podstiču razvoj trgovine</strong>;</li>
                <li><strong>Podstiču razvoj kreativnih industrija</strong> (aktivnosti koje su bazirane na individualnoj kreativnosti, vještini i talentu: zanati, arhitektura, umjetnost, dizajn, produkcija, mediji, izdavaštvo, razvoj software-a);</li>
                <li><strong>Podstiču razvoj start-up-ova</strong> (inovativnih tehnoloških biznisa koji imaju potencijal brzog rasta i velikih dometa);</li>
                <li><strong>Doprinose razvoju fizičke kulture i sporta i zdravih stilova života</strong>;</li>
                <li><strong>Doprinose očuvanju životne sredine i održivog razvoja</strong>.</li>
            </ul>
        </div>

        <!-- Biznis planovi koji se neće podržati -->
        <div style="background: #fff; border-radius: 16px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); background: #fef2f2; border-left: 4px solid #ef4444;">
            <h2 style="color: #991b1b; font-size: 20px; font-weight: 700; margin: 0 0 16px;">Biznis planovi koji se neće podržati</h2>
            <ul style="color: #374151; line-height: 1.8; margin: 0; padding-left: 20px;">
                <li>Aktivnosti koje su u nadležnosti ili odgovornosti Vlade, kao što je formalno obrazovanje, formalna zdravstvena zaštita i sl.;</li>
                <li>Biznis planovi kojim se traže finansijska sredstva za kupovinu i raspodjelu humanitarne pomoći;</li>
                <li>Biznis planovi koji se isključivo temelje na jednokratnoj izradi, pripremi i štampanju knjiga, brošura, biltena, časopisa i slično, ukoliko objava takvih publikacija nije dio nekog šireg programa ili sveobuhvatnijih i kontinuiranih aktivnosti;</li>
                <li>Aktivnost koja se smatra nezakonitom ili štetnom po okolinu i opasnom za ljudsko zdravlje: igre na sreću, duvan, alkoholna pića (izuzev proizvodnje vina i voćnih rakija).</li>
            </ul>
        </div>
    </div>
</div>

<script>
    (function() {
        document.querySelectorAll('[data-deadline]').forEach(function(element) {
            const deadline = new Date(element.getAttribute('data-deadline')).getTime();
            const countdownEl = element.querySelector('[class^="countdown-"]');
            
            if (!countdownEl) return;
            
            function updateCountdown() {
                const now = new Date().getTime();
                const distance = deadline - now;
                
                if (distance < 0) {
                    countdownEl.textContent = 'Rok je istekao';
                    return;
                }
                
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                let text = '';
                if (days > 0) {
                    text += days + ' ' + (days === 1 ? 'dan' : 'dana');
                    if (hours > 0 || minutes > 0) text += ', ';
                }
                if (hours > 0) {
                    text += hours + ' ' + (hours === 1 ? 'sat' : (hours < 5 ? 'sata' : 'sati'));
                    if (minutes > 0) text += ', ';
                }
                if (minutes > 0) {
                    text += minutes + ' ' + (minutes === 1 ? 'minut' : (minutes < 5 ? 'minuta' : 'minuta'));
                }
                if (days === 0 && hours === 0 && minutes === 0) {
                    text = seconds + ' ' + (seconds === 1 ? 'sekund' : (seconds < 5 ? 'sekunda' : 'sekundi'));
                }
                
                countdownEl.textContent = text;
            }
            
            updateCountdown();
            setInterval(updateCountdown, 1000);
        });
    })();
</script>
@endsection
