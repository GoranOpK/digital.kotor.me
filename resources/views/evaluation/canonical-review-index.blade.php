@extends('layouts.app')

@section('content')
@php
    $box = 'border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; margin-bottom: 16px; background: #fff;';
    $warn = 'background: #fffbeb; border: 1px solid #f59e0b; padding: 10px; border-radius: 8px; margin-bottom: 8px;';
    $labels = \App\Services\Competitions\CanonicalReviewState::LABELS;
@endphp
<div class="container mx-auto px-4 py-6" style="max-width: 1100px;">
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 4px;">Provjera potpunosti i prigovori</h1>
    <p style="color: #4b5563; margin-bottom: 16px;">
        {{ $competition->title }}. Provjera se vrši nad zamrznutim podnesenim dosijeom. Potpunost evidentira i o prigovoru odlučuje
        isključivo predsjednik kanonske Komisije.
    </p>
    @if($readOnly)
        <div style="{{ $warn }}">Pregled za administratora: samo čitanje. Radnje provjere i odluke o prigovoru vrši isključivo Komisija.</div>
    @endif

    <div style="{{ $box }}" data-section="canonical-review-list">
        @if($rows->isEmpty())
            <p>Nema uspješno podnesenih prijava.</p>
        @else
            <table style="width: 100%; border-collapse: collapse;">
                <thead><tr><th align="left">Redni broj</th><th align="left">Prijava</th><th align="left">Stanje provjere</th><th></th></tr></thead>
                <tbody>
                    @foreach($rows as $row)
                        @php $application = $row['application']; @endphp
                        <tr data-application-id="{{ $application->id }}">
                            <td>{{ $application->redni_broj ?? '—' }}</td>
                            <td>{{ $application->business_plan_name ?? ('Prijava #'.$application->id) }}</td>
                            <td>{{ $labels[$row['state']] ?? '—' }}</td>
                            <td>
                                <a href="{{ $readOnly ? route('admin.competitions.canonical-review.show', [$competition, $application]) : route('evaluation.canonical-review.show', $application) }}">Otvori</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
