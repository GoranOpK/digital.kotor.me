<div style="background: #f0f9ff; border-left: 4px solid var(--primary); padding: 16px; border-radius: 8px; margin: 24px 0;">
    <h3 style="font-size: 16px; font-weight: 600; color: var(--primary); margin: 0 0 12px;">Sastav komisije mladih</h3>
    <p style="font-size: 14px; color: #374151; margin: 0;">Pri kreiranju moraju biti popunjena sva 3 redovna mjesta. Predsjednik je jedan od ta tri člana. Mjesta 4 i 5 nijesu dozvoljena. Zamjenski član se ne može koristiti umjesto redovnog mjesta.</p>
</div>
<div style="margin-top: 32px; padding-top: 24px; border-top: 2px solid #e5e7eb;">
    <h2 style="font-size: 20px; font-weight: 700; color: var(--primary); margin: 0 0 20px;">Članovi komisije</h2>
    @foreach([1,2,3] as $seat)
        @php $idx = $seat - 1; @endphp
        <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 16px;">Mjesto {{ $seat }}</h3>
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
            <div class="form-group">
                <label class="form-label">Pozicija</label>
                <select name="members[{{ $idx }}][position]" class="form-control @error('members.'.$idx.'.position') error @enderror" required>
                    <option value="predsjednik" {{ old('members.'.$idx.'.position', $seat === 1 ? 'predsjednik' : 'clan') === 'predsjednik' ? 'selected' : '' }}>Predsjednik</option>
                    <option value="clan" {{ old('members.'.$idx.'.position', $seat === 1 ? 'predsjednik' : 'clan') === 'clan' ? 'selected' : '' }}>Član</option>
                </select>
                @error('members.'.$idx.'.position')
                    <div class="error-message">{{ $message }}</div>
                @enderror
            </div>
        </div>
    @endforeach
</div>
<div style="margin-top: 12px; padding: 12px; background: #f0f9ff; border-left: 4px solid var(--primary); border-radius: 4px;">
    <p style="font-size: 13px; color: #374151; margin: 0;">
        <strong>Napomena:</strong> Komisija mladih ima tri mjesta i sva tri se popunjavaju pri kreiranju. Predsjednik može biti na mjestu 1, 2 ili 3, ali mora biti tačno jedan. Zamjenski član se ne može koristiti umjesto redovnog mjesta.
    </p>
</div>
