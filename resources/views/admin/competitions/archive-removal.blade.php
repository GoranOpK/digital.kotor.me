@extends('layouts.app')

@section('content')
<div class="container" style="padding: 24px 0 48px;">
    <h1 style="font-size: 1.4rem; margin-bottom: 8px;">Uklanjanje jednog arhiviranog testnog konkursa</h1>
    <p style="max-width: 46rem; margin-bottom: 20px;">
        Postupak je odvojen od redovnog brisanja. Super administrator unosi ID jednog zatvorenog ili završenog
        konkursa koji je izričito označen kao testni. Naziv, datum i ID ne određuju tu oznaku.
    </p>

    @if(session('success'))
        <p style="padding: 12px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px;">{{ session('success') }}</p>
    @endif
    @if($errors->any())
        <p style="padding: 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;">{{ $errors->first() }}</p>
    @endif

    <form method="POST" action="{{ route('admin.archive-test-competition.preview') }}" style="margin-bottom: 24px;">
        @csrf
        <label for="competition_id">ID konkursa</label>
        <input id="competition_id" name="competition_id" type="number" min="1" step="1" required
               value="{{ old('competition_id', $preview['competition']->id ?? '') }}"
               style="display: block; margin: 8px 0 12px; padding: 8px; width: 12rem;">
        <button type="submit">Prikaži šta će biti uklonjeno</button>
    </form>

    @if($preview)
        <section style="border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; max-width: 52rem;">
            <h2 style="font-size: 1.1rem;">Konkurs {{ $preview['competition']->id }}</h2>
            <p><strong>Naziv:</strong> {{ $preview['competition']->title }}</p>
            <p><strong>Tip:</strong> {{ $preview['competition']->type }}</p>
            <p><strong>Status:</strong> {{ $preview['competition']->status }}</p>
            <p><strong>Oznaka testnog konkursa:</strong> {{ $preview['competition']->is_test ? 'da' : 'ne' }}</p>
            @if(! $preview['eligible'])
                <p style="color: #991b1b;"><strong>Ne može se ukloniti:</strong> {{ $preview['reason'] }}</p>
            @endif

            <h3 style="font-size: 1rem; margin-top: 16px;">Povezani zapisi</h3>
            <ul>
                @foreach($preview['counts'] as $table => $count)
                    <li>{{ $table }}: {{ $count }}</li>
                @endforeach
            </ul>

            <h3 style="font-size: 1rem;">Dokumenti</h3>
            @if($preview['files'] === [])
                <p>Nema povezanih fajlova.</p>
            @else
                <ul>
                    @foreach($preview['files'] as $file)
                        <li>{{ $file['kind'] }} — {{ $file['path'] }} — {{ $file['action'] }}</li>
                    @endforeach
                </ul>
            @endif

            @if($preview['eligible'])
                <form method="POST" action="{{ route('admin.archive-test-competition.destroy') }}" style="margin-top: 16px;">
                    @csrf
                    <input type="hidden" name="competition_id" value="{{ $preview['competition']->id }}">
                    <p>
                        Za trajno uklanjanje samo ovog konkursa upišite
                        <strong>UKLONI {{ $preview['competition']->id }}</strong>
                    </p>
                    <label for="confirmation">Potvrda</label>
                    <input id="confirmation" name="confirmation" type="text" required autocomplete="off"
                           style="display: block; margin: 8px 0 12px; padding: 8px; width: 16rem;">
                    <button type="submit">Ukloni samo ovaj konkurs</button>
                </form>
            @endif
        </section>
    @endif
</div>
@endsection
