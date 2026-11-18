@php
    $isCanonicalMemberForm = !empty($isCanonicalCommissionForm);
    $threeSeatHeading = $isCanonicalMemberForm
        ? 'Sastav komisije za podršku mikro, malim i srednjim preduzećima'
        : 'Sastav komisije mladih';
    $threeSeatNote = $isCanonicalMemberForm
        ? 'Komisija za podršku mikro, malim i srednjim preduzećima ima tri funkcije: predsjednika i sekretara kao predstavnike Opštine Kotor i člana iz poslovnog ili strukovnog udruženja, biznisa ili akademske zajednice. Funkcija se bira posebno i ne zavisi od redoslijeda unosa. Sva tri člana ostaju na postojećim mjestima 1, 2 i 3.'
        : 'Komisija mladih ima tri člana: predsjednika, sekretara i člana. Funkcija se bira posebno i ne zavisi od redoslijeda unosa. Sva tri člana boduju. Zamjenski član se ne može koristiti umjesto redovnog mjesta.';
    $threeSeatIntro = $isCanonicalMemberForm
        ? 'Pri kreiranju moraju biti popunjena sva 3 redovna mjesta. Funkcije su predsjednik Komisije, sekretar Komisije i član Komisije. Mjesta 4 i 5 nijesu dozvoljena. Zamjenski član se ne može koristiti umjesto redovnog mjesta.'
        : 'Pri kreiranju moraju biti popunjena sva 3 redovna mjesta. Predsjednik je jedan od ta tri člana. Mjesta 4 i 5 nijesu dozvoljena. Zamjenski član se ne može koristiti umjesto redovnog mjesta.';
@endphp
<div style="background: #f0f9ff; border-left: 4px solid var(--primary); padding: 16px; border-radius: 8px; margin: 24px 0;">
    <h3 style="font-size: 16px; font-weight: 600; color: var(--primary); margin: 0 0 12px;">{{ $threeSeatHeading }}</h3>
    <p style="font-size: 14px; color: #374151; margin: 0;">{{ $threeSeatIntro }}</p>
</div>
<div style="margin-top: 32px; padding-top: 24px; border-top: 2px solid #e5e7eb;">
    <h2 style="font-size: 20px; font-weight: 700; color: var(--primary); margin: 0 0 20px;">Članovi komisije</h2>
    @foreach([1,2,3] as $seat)
        @php $idx = $seat - 1; @endphp
        <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 16px;">Član Komisije</h3>
            <input type="hidden" name="members[{{ $idx }}][canonical_seat_no]" value="{{ $seat }}">
            <div class="form-group">
                <label class="form-label">Ime i prezime</label>
                <input type="text" name="members[{{ $idx }}][name]" class="form-control @error('members.'.$idx.'.name') error @enderror" value="{{ old('members.'.$idx.'.name') }}" required>
                @error('members.'.$idx.'.name')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label class="form-label">E-mail</label>
                <input type="email" name="members[{{ $idx }}][email]" class="form-control @error('members.'.$idx.'.email') error @enderror" value="{{ old('members.'.$idx.'.email') }}" required>
                @error('members.'.$idx.'.email')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="members[{{ $idx }}][password]" class="form-control @error('members.'.$idx.'.password') error @enderror" minlength="8">
                @error('members.'.$idx.'.password')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            @if($isCanonicalMemberForm)
            <div class="form-group">
                <label class="form-label">Funkcija</label>
                @php $selectedFunction = old('members.'.$idx.'.mmsp_function'); @endphp
                <select name="members[{{ $idx }}][mmsp_function]" class="form-control @error('members.'.$idx.'.mmsp_function') error @enderror" required>
                    <option value="">Izaberite funkciju</option>
                    <option value="predsjednik" @selected($selectedFunction === 'predsjednik')>Predsjednik Komisije</option>
                    <option value="sekretar" @selected($selectedFunction === 'sekretar')>Sekretar Komisije</option>
                    <option value="spoljasnji" @selected($selectedFunction === 'spoljasnji')>Član Komisije</option>
                </select>
                @error('members.'.$idx.'.mmsp_function')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Organizacija</label>
                <input type="text" name="members[{{ $idx }}][organization]" class="form-control @error('members.'.$idx.'.organization') error @enderror" value="{{ old('members.'.$idx.'.organization') }}" maxlength="255">
                @error('members.'.$idx.'.organization')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Kategorija člana</label>
                <select name="members[{{ $idx }}][external_category]" class="form-control @error('members.'.$idx.'.external_category') error @enderror">
                    <option value="">—</option>
                    <option value="udruzenje" @selected(old('members.'.$idx.'.external_category') === 'udruzenje')>Poslovno ili strukovno udruženje</option>
                    <option value="biznis" @selected(old('members.'.$idx.'.external_category') === 'biznis')>Biznis</option>
                    <option value="akademska_zajednica" @selected(old('members.'.$idx.'.external_category') === 'akademska_zajednica')>Akademska zajednica</option>
                </select>
                <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">Obavezno za člana Komisije. Predsjednik Komisije — Opština Kotor. Sekretar Komisije — Opština Kotor.</div>
                @error('members.'.$idx.'.external_category')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            @else
            <div class="form-group">
                <label class="form-label">Funkcija</label>
                @php $selectedFunction = old('members.'.$idx.'.pm_function'); @endphp
                <select name="members[{{ $idx }}][pm_function]" class="form-control @error('members.'.$idx.'.pm_function') error @enderror" required>
                    <option value="">Izaberite funkciju</option>
                    <option value="predsjednik" @selected($selectedFunction === 'predsjednik')>Predsjednik Komisije</option>
                    <option value="sekretar" @selected($selectedFunction === 'sekretar')>Sekretar Komisije</option>
                    <option value="clan" @selected($selectedFunction === 'clan')>Član Komisije</option>
                </select>
                @error('members.'.$idx.'.pm_function')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label class="form-label">Organizacija</label>
                <input type="text" name="members[{{ $idx }}][organization]" class="form-control" value="{{ old('members.'.$idx.'.organization') }}" maxlength="255">
            </div>
            @endif
        </div>
    @endforeach
</div>
<div style="margin-top: 12px; padding: 12px; background: #f0f9ff; border-left: 4px solid var(--primary); border-radius: 4px;">
    <p style="font-size: 13px; color: #374151; margin: 0;">
        <strong>Napomena:</strong> {{ $threeSeatNote }}
    </p>
</div>
