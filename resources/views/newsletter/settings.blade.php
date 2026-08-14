@extends('layouts.app')

@php
    $isActive = $subscription && $subscription->isActive();
    $isUnsubscribed = $subscription && $subscription->isUnsubscribed();
    $neverSubscribed = $subscription === null;
    $oldScope = old('scope_mode', $isActive ? $subscription->scope_mode : null);
    $oldInclude = (bool) old('include_without_organizer', $isActive ? $subscription->include_without_organizer : false);
    $oldOrganizerIds = old('organizer_ids', $isActive ? $selectedIds : []);
    if (! is_array($oldOrganizerIds)) {
        $oldOrganizerIds = [];
    }
    $oldOrganizerIds = array_map('intval', $oldOrganizerIds);
@endphp

@section('content')
<style>
    :root {
        --primary: #0B3D91;
        --primary-dark: #0A347B;
    }
    .newsletter-settings {
        background: #f9fafb;
        min-height: 100vh;
        padding: 24px 0;
    }
    .newsletter-header {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        color: #fff;
        padding: 24px;
        border-radius: 16px;
        margin-bottom: 24px;
    }
    .newsletter-header h1 {
        color: #fff;
        font-size: 32px;
        font-weight: 700;
        margin: 0;
    }
    .newsletter-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 2px rgba(0,0,0,.06);
        margin-bottom: 24px;
    }
    .newsletter-card h2 {
        font-size: 20px;
        font-weight: 700;
        color: #111827;
        margin: 0 0 12px;
    }
    .newsletter-lead {
        color: #4b5563;
        margin: 0 0 20px;
        font-size: 15px;
    }
    .newsletter-status {
        font-weight: 600;
        margin-bottom: 16px;
        color: #111827;
    }
    .scope-option {
        display: block;
        margin-bottom: 12px;
        font-weight: 600;
        color: #374151;
    }
    .organizer-list {
        margin: 8px 0 16px 28px;
        display: grid;
        gap: 8px;
    }
    @media (min-width: 640px) {
        .organizer-list {
            grid-template-columns: 1fr 1fr;
        }
    }
    .organizer-item {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-weight: 400;
        color: #374151;
    }
    .organizer-item--inactive {
        color: #6b7280;
    }
    .btn {
        display: inline-block;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        border: 1px solid transparent;
        cursor: pointer;
        font-size: 14px;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
        border: none;
    }
    .btn-danger {
        background: #b91c1c;
        color: #fff;
        border: none;
    }
    .alert {
        border-radius: 12px;
        padding: 16px 20px;
        margin-bottom: 20px;
        border: 1px solid;
    }
    .alert-success {
        background: #d1fae5;
        border-color: #10b981;
        color: #065f46;
    }
    .alert-error {
        background: #fee2e2;
        border-color: #dc2626;
        color: #991b1b;
    }
    .form-error {
        color: #dc2626;
        font-size: 13px;
        margin: 8px 0 16px;
    }
    .action-row {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        margin-top: 20px;
    }
</style>

<div class="newsletter-settings">
    <div class="container mx-auto px-4">
        <div class="newsletter-header">
            <h1>Newsletter</h1>
        </div>

        @if(session('success'))
            <div class="alert alert-success" role="status">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-error" role="alert">
                <strong>Greška:</strong> Molimo provjerite izbor.
                <ul style="margin: 8px 0 0 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="newsletter-card">
            @if($isActive)
                <p class="newsletter-status">Newsletter pretplata je aktivna.</p>
            @elseif($isUnsubscribed)
                <p class="newsletter-status">Niste pretplaćeni.</p>
                <p class="newsletter-lead">Odaberite sadržaj koji želite da pratite.</p>
            @else
                <p class="newsletter-status">Niste pretplaćeni.</p>
                <p class="newsletter-lead">Odaberite sadržaj koji želite da pratite.</p>
            @endif

            <form
                method="POST"
                action="{{ $isActive ? route('newsletter.update') : route('newsletter.subscribe') }}"
                novalidate
            >
                @csrf
                @if($isActive)
                    @method('PATCH')
                @endif

                <fieldset style="border: 0; padding: 0; margin: 0;">
                    <legend class="sr-only">Opseg sadržaja</legend>

                    <label class="scope-option" for="scope_all_events">
                        <input
                            type="radio"
                            name="scope_mode"
                            id="scope_all_events"
                            value="all_events"
                            @checked($oldScope === 'all_events')
                        >
                        Svi događaji
                    </label>

                    <label class="scope-option" for="scope_selected_organizers">
                        <input
                            type="radio"
                            name="scope_mode"
                            id="scope_selected_organizers"
                            value="selected_organizers"
                            @checked($oldScope === 'selected_organizers')
                        >
                        Odabrani organizatori
                    </label>
                    @error('scope_mode')
                        <div class="form-error" id="scope_mode_error">{{ $message }}</div>
                    @enderror
                </fieldset>

                <fieldset class="organizer-list" aria-labelledby="scope_selected_organizers">
                    @foreach($selectableOrganizers as $organizer)
                        <label class="organizer-item" for="organizer_{{ $organizer->id }}">
                            <input
                                type="checkbox"
                                name="organizer_ids[]"
                                id="organizer_{{ $organizer->id }}"
                                value="{{ $organizer->id }}"
                                @checked(in_array($organizer->id, $oldOrganizerIds, true))
                            >
                            {{ $organizer->naziv }}
                        </label>
                    @endforeach

                    @foreach($preservedInactiveOrganizers as $organizer)
                        <label class="organizer-item organizer-item--inactive" for="organizer_{{ $organizer->id }}">
                            <input
                                type="checkbox"
                                name="organizer_ids[]"
                                id="organizer_{{ $organizer->id }}"
                                value="{{ $organizer->id }}"
                                @checked(in_array($organizer->id, $oldOrganizerIds, true))
                            >
                            {{ $organizer->naziv }} (neaktivan)
                        </label>
                    @endforeach

                    <label class="organizer-item" for="include_without_organizer">
                        <input
                            type="checkbox"
                            name="include_without_organizer"
                            id="include_without_organizer"
                            value="1"
                            @checked($oldInclude)
                        >
                        Bez organizatora
                    </label>
                </fieldset>
                @error('organizer_ids')
                    <div class="form-error" id="organizer_ids_error">{{ $message }}</div>
                @enderror

                <div class="action-row">
                    @if($isActive)
                        <button type="submit" class="btn btn-primary">Sačuvaj izmjene</button>
                    @else
                        <button type="submit" class="btn btn-primary">Pretplati se</button>
                    @endif
                </div>
            </form>
        </div>

        @if($isActive)
            <div class="newsletter-card">
                <form
                    method="POST"
                    action="{{ route('newsletter.unsubscribe') }}"
                    onsubmit="return confirm('Da li ste sigurni da želite da se odjavite sa Newslettera?');"
                >
                    @csrf
                    <input type="hidden" name="confirm_unsubscribe" value="1">
                    <button type="submit" class="btn btn-danger">Odjavi se</button>
                </form>
            </div>
        @endif
    </div>
</div>
@endsection
