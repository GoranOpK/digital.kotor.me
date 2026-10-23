<!-- Informacije o sastavu komisije -->
<div style="background: #f0f9ff; border-left: 4px solid var(--primary); padding: 16px; border-radius: 8px; margin: 24px 0;">
    <h3 style="font-size: 16px; font-weight: 600; color: var(--primary); margin: 0 0 12px;">Sastav komisije</h3>
    <p style="font-size: 14px; color: #374151; margin: 0 0 8px;"><strong>Članovi Komisije su:</strong></p>
    <ul style="margin: 0; padding-left: 20px; color: #374151; font-size: 14px;">
        <li>3 člana - predstavnici Opštine Kotor (1 član - predsjednik Komisije, 2 člana - iz redova zaposlenih u Sekretarijatu od kojih je jedan sekretar Komisije)</li>
        <li>1 član - predstavnica Udruženja preduzetnica Crne Gore ili strukovnih udruženja, ili biznisa, ili akademske zajednice</li>
        <li>1 član - predstavnica Ženske političke mreže</li>
    </ul>
    <p style="font-size: 13px; color: #6b7280; margin: 12px 0 0; font-style: italic;">Pri kreiranju moraju biti popunjena sva 5 redovna mjesta. Zamjenski član se ne može koristiti umjesto redovnog mjesta.</p>
</div>

<!-- Članovi komisije -->
<div style="margin-top: 32px; padding-top: 24px; border-top: 2px solid #e5e7eb;">
    <h2 style="font-size: 20px; font-weight: 700; color: var(--primary); margin: 0 0 20px;">Članovi komisije</h2>

    <!-- Predsjednik -->
    <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 16px;">1. Predsjednik</h3>
        <div class="form-group">
            <label class="form-label">Ime i prezime</label>
            <input type="text" name="members[0][name]" class="form-control @error('members.0.name') error @enderror" value="{{ old('members.0.name') }}" required>
            @error('members.0.name')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label class="form-label">E-mail</label>
            <input type="email" name="members[0][email]" class="form-control @error('members.0.email') error @enderror" value="{{ old('members.0.email') }}" autocomplete="section-commission-member-0 username" autocapitalize="off" spellcheck="false" required>
            @error('members.0.email')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="members[0][password]" class="form-control @error('members.0.password') error @enderror" minlength="8" autocomplete="section-commission-member-0 current-password">
            @error('members.0.password')
                <div class="error-message">{{ $message }}</div>
            @enderror
            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">Minimum 8 karaktera (obavezno samo za novi e-mail)</div>
        </div>
        <div class="form-group">
            <label class="form-label">Organizacija</label>
            <input type="text" name="members[0][organization]" class="form-control" value="{{ old('members.0.organization') }}" placeholder="Opština Kotor">
        </div>
        <input type="hidden" name="members[0][position]" value="predsjednik">
        <input type="hidden" name="members[0][member_type]" value="opstina">
    </div>

    <!-- 2 člana - Opština -->
    <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 16px;">2. Član - Predstavnik Opštine Kotor (Sekretarijat)</h3>
        <div class="form-group">
            <label class="form-label">Ime i prezime</label>
            <input type="text" name="members[1][name]" class="form-control @error('members.1.name') error @enderror" value="{{ old('members.1.name') }}" required>
            @error('members.1.name')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label class="form-label">E-mail</label>
            <input type="email" name="members[1][email]" class="form-control @error('members.1.email') error @enderror" value="{{ old('members.1.email') }}" autocomplete="section-commission-member-1 username" autocapitalize="off" spellcheck="false" required>
            @error('members.1.email')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="members[1][password]" class="form-control @error('members.1.password') error @enderror" minlength="8" autocomplete="section-commission-member-1 current-password">
            @error('members.1.password')
                <div class="error-message">{{ $message }}</div>
            @enderror
            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">Minimum 8 karaktera (obavezno samo za novi e-mail)</div>
        </div>
        <div class="form-group">
            <label class="form-label">Organizacija</label>
            <input type="text" name="members[1][organization]" class="form-control" value="{{ old('members.1.organization') }}" placeholder="Naziv organizacije">
        </div>
        <input type="hidden" name="members[1][position]" value="clan">
        <input type="hidden" name="members[1][member_type]" value="opstina">
    </div>

    <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 16px;">3. Član - Predstavnik Opštine Kotor (Sekretarijat)</h3>
        <div class="form-group">
            <label class="form-label">Ime i prezime</label>
            <input type="text" name="members[2][name]" class="form-control @error('members.2.name') error @enderror" value="{{ old('members.2.name') }}" required>
            @error('members.2.name')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label class="form-label">E-mail</label>
            <input type="email" name="members[2][email]" class="form-control @error('members.2.email') error @enderror" value="{{ old('members.2.email') }}" autocomplete="section-commission-member-2 username" autocapitalize="off" spellcheck="false" required>
            @error('members.2.email')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="members[2][password]" class="form-control @error('members.2.password') error @enderror" minlength="8" autocomplete="section-commission-member-2 current-password">
            @error('members.2.password')
                <div class="error-message">{{ $message }}</div>
            @enderror
            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">Minimum 8 karaktera (obavezno samo za novi e-mail)</div>
        </div>
        <div class="form-group">
            <label class="form-label">Organizacija</label>
            <input type="text" name="members[2][organization]" class="form-control" value="{{ old('members.2.organization') }}" placeholder="Naziv organizacije">
        </div>
        <input type="hidden" name="members[2][position]" value="clan">
        <input type="hidden" name="members[2][member_type]" value="opstina">
    </div>

    <!-- 1 član - Udruženje -->
    <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 16px;">4. Član - Predstavnica Udruženja preduzetnica Crne Gore ili strukovnih udruženja, ili biznisa, ili akademske zajednice</h3>
        <div class="form-group">
            <label class="form-label">Ime i prezime</label>
            <input type="text" name="members[3][name]" class="form-control @error('members.3.name') error @enderror" value="{{ old('members.3.name') }}" required>
            @error('members.3.name')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label class="form-label">E-mail</label>
            <input type="email" name="members[3][email]" class="form-control @error('members.3.email') error @enderror" value="{{ old('members.3.email') }}" autocomplete="section-commission-member-3 username" autocapitalize="off" spellcheck="false" required>
            @error('members.3.email')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="members[3][password]" class="form-control @error('members.3.password') error @enderror" minlength="8" autocomplete="section-commission-member-3 current-password">
            @error('members.3.password')
                <div class="error-message">{{ $message }}</div>
            @enderror
            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">Minimum 8 karaktera (obavezno samo za novi e-mail)</div>
        </div>
        <div class="form-group">
            <label class="form-label">Organizacija</label>
            <input type="text" name="members[3][organization]" class="form-control @error('members.3.organization') error @enderror" value="{{ old('members.3.organization') }}" placeholder="Naziv udruženja/organizacije">
            @error('members.3.organization')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>
        <input type="hidden" name="members[3][position]" value="clan">
        <input type="hidden" name="members[3][member_type]" value="udruzenje">
    </div>

    <!-- 1 član - Ženske mreže -->
    <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 16px;">5. Član - Predstavnica Ženske političke mreže</h3>
        <div class="form-group">
            <label class="form-label">Ime i prezime</label>
            <input type="text" name="members[4][name]" class="form-control @error('members.4.name') error @enderror" value="{{ old('members.4.name') }}" required>
            @error('members.4.name')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label class="form-label">E-mail</label>
            <input type="email" name="members[4][email]" class="form-control @error('members.4.email') error @enderror" value="{{ old('members.4.email') }}" autocomplete="section-commission-member-4 username" autocapitalize="off" spellcheck="false" required>
            @error('members.4.email')
                <div class="error-message">{{ $message }}</div>
            @enderror
        </div>
        <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="members[4][password]" class="form-control @error('members.4.password') error @enderror" minlength="8" autocomplete="section-commission-member-4 current-password">
            @error('members.4.password')
                <div class="error-message">{{ $message }}</div>
            @enderror
            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">Minimum 8 karaktera (obavezno samo za novi e-mail)</div>
        </div>
        <div class="form-group">
            <label class="form-label">Organizacija</label>
            <input type="text" name="members[4][organization]" class="form-control" value="{{ old('members.4.organization') }}" placeholder="Ženska politička mreža">
        </div>
        <input type="hidden" name="members[4][position]" value="clan">
        <input type="hidden" name="members[4][member_type]" value="zene_mreza">
    </div>

</div>
<div style="margin-top: 12px; padding: 12px; background: #f0f9ff; border-left: 4px solid var(--primary); border-radius: 4px;">
    <p style="font-size: 13px; color: #374151; margin: 0;">
        <strong>Napomena:</strong> Pri kreiranju moraju biti popunjena sva 5 redovna mjesta. Zamjenski član se ne može koristiti umjesto redovnog mjesta.
    </p>
</div>
