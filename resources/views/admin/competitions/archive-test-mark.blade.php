@extends('layouts.app')

@section('content')
<div class="container" style="padding: 24px 0 48px;">
    <h1 style="font-size: 1.4rem; margin-bottom: 8px;">Označavanje ranijeg konkursa kao testnog</h1>
    <p style="max-width: 46rem; margin-bottom: 20px;">
        Postupak samo upisuje oznaku na jedan već objavljen konkurs podrške preduzetništvu mladih
        ili ženskom preduzetništvu čiji je ID na odobrenom spisku. Ne briše konkurs i ne prepoznaje
        test po nazivu, datumu ili ID-u.
    </p>

    @if(session('success'))
        <p style="padding: 12px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px;">{{ session('success') }}</p>
    @endif
    @if($errors->any())
        <p style="padding: 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px;">{{ $errors->first() }}</p>
    @endif

    <form method="POST" action="{{ route('admin.archive-test-mark.preview') }}" style="margin-bottom: 24px;">
        @csrf
        <label for="competition_id">ID konkursa</label>
        <input id="competition_id" name="competition_id" type="number" min="1" step="1" required
               value="{{ old('competition_id', $preview['competition']->id ?? '') }}"
               style="display: block; margin: 8px 0 12px; padding: 8px; width: 12rem;">
        <button type="submit">Prikaži konkurs</button>
    </form>

    @if($preview)
        <section style="border: 1px solid #d1d5db; border-radius: 8px; padding: 16px; max-width: 52rem;">
            <h2 style="font-size: 1.1rem;">Konkurs {{ $preview['competition']->id }}</h2>
            <p><strong>Naziv:</strong> {{ $preview['competition']->title }}</p>
            <p><strong>Tip:</strong> {{ $preview['competition']->type }}</p>
            <p><strong>Status:</strong> {{ $preview['competition']->status }}</p>
            <p><strong>Trenutna oznaka:</strong> {{ $preview['competition']->is_test ? 'testni' : 'nije testni' }}</p>
            @if(! $preview['eligible'])
                <p style="color: #991b1b;"><strong>Ne može se označiti:</strong> {{ $preview['reason'] }}</p>
            @else
                <form method="POST" action="{{ route('admin.archive-test-mark.store') }}" style="margin-top: 16px;">
                    @csrf
                    <input type="hidden" name="competition_id" value="{{ $preview['competition']->id }}">
                    <p>
                        Za označavanje samo ovog konkursa upišite
                        <strong>OZNACI {{ $preview['competition']->id }}</strong>
                    </p>
                    <label for="confirmation">Potvrda</label>
                    <input id="confirmation" name="confirmation" type="text" required autocomplete="off"
                           style="display: block; margin: 8px 0 12px; padding: 8px; width: 16rem;">
                    <button type="submit">Označi samo ovaj konkurs</button>
                </form>
            @endif
        </section>
    @endif
</div>
@endsection
