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
    }
    .page-header h1 {
        color: #fff;
        font-size: 28px;
        font-weight: 700;
        margin: 0;
    }
    .form-card {
        background: #fff;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .form-group {
        margin-bottom: 20px;
    }
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
    }
    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(11, 61, 145, 0.1);
    }
    .form-row {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    .btn-primary {
        background: var(--primary);
        color: #fff;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
    }
    .error-message {
        color: #ef4444;
        font-size: 12px;
        margin-top: 4px;
    }
</style>

<div class="admin-page">
    <div class="container mx-auto px-4">
        <div class="page-header">
            <h1>Kreiraj novu komisiju</h1>
        </div>

        <div class="form-card">
            <form method="POST" action="{{ route('admin.commissions.store') }}">
                @csrf
                
                <div class="form-group">
                    <label class="form-label">Naziv komisije *</label>
                    <input type="text" name="name" class="form-control @error('name') error @enderror" value="{{ old('name') }}" required>
                    @error('name')
                        <div class="error-message">{{ $message }}</div>
                    @enderror
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Godina *</label>
                        <input type="number" name="year" class="form-control" value="{{ old('year', date('Y')) }}" min="2020" max="2100" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="font-size: 12px; color: #6b7280;">
                            Mandat komisije traje 2 godine
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Datum početka mandata *</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date') }}" required onchange="calculateEndDate()">
                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                        Mandat komisije traje tačno 2 godine od datuma početka
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Datum završetka mandata</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="{{ old('end_date') }}" readonly style="background: #f3f4f6;">
                    <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                        Automatski izračunato (2 godine od datuma početka)
                    </div>
                </div>

                <script>
                    function calculateEndDate() {
                        const startDateInput = document.getElementById('start_date');
                        const endDateInput = document.getElementById('end_date');
                        
                        if (startDateInput.value) {
                            const startDate = new Date(startDateInput.value);
                            const endDate = new Date(startDate);
                            endDate.setFullYear(endDate.getFullYear() + 2);
                            
                            // Formatiraj datum kao YYYY-MM-DD
                            const year = endDate.getFullYear();
                            const month = String(endDate.getMonth() + 1).padStart(2, '0');
                            const day = String(endDate.getDate()).padStart(2, '0');
                            
                            endDateInput.value = `${year}-${month}-${day}`;
                        } else {
                            endDateInput.value = '';
                        }
                    }
                    
                    // Izračunaj na učitavanju stranice ako već postoji start_date
                    document.addEventListener('DOMContentLoaded', function() {
                        if (document.getElementById('start_date').value) {
                            calculateEndDate();
                        }
                    });
                </script>

                <!-- Informacije o sastavu komisije -->
                <div style="background: #f0f9ff; border-left: 4px solid var(--primary); padding: 16px; border-radius: 8px; margin: 24px 0;">
                    <h3 style="font-size: 16px; font-weight: 600; color: var(--primary); margin: 0 0 12px;">Sastav komisije</h3>
                    <p style="font-size: 14px; color: #374151; margin: 0 0 8px;"><strong>Komisija se sastoji od maksimalno 5 članova:</strong></p>
                    <ul style="margin: 0; padding-left: 20px; color: #374151; font-size: 14px;">
                        <li>1 predsjednik (iz reda potpredsjednika Opštine ili starješina organa lokalne uprave)</li>
                        <li>2 člana - predstavnici Opštine</li>
                        <li>1 član - predstavnica Udruženja preduzetnica Crne Gore ili strukovnih udruženja, ili biznisa, ili akademske zajednice</li>
                        <li>1 član - predstavnica Ženske političke mreže</li>
                    </ul>
                    <p style="font-size: 13px; color: #6b7280; margin: 12px 0 0; font-style: italic;">Možete kreirati komisiju sa bilo kojim brojem članova (1-5). Nema obaveznih članova - možete dodati bilo koji član. Ostale članove možete dodati kasnije.</p>
                </div>

                <!-- Članovi komisije -->
                <div style="margin-top: 32px; padding-top: 24px; border-top: 2px solid #e5e7eb;">
                    <h2 style="font-size: 20px; font-weight: 700; color: var(--primary); margin: 0 0 20px;">Članovi komisije</h2>
                    
                    <!-- Predsjednik -->
                    <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 16px;">1. Predsjednik</h3>
                        <div class="form-group">
                            <label class="form-label">Ime i prezime</label>
                            <input type="text" name="members[0][name]" class="form-control @error('members.0.name') error @enderror" value="{{ old('members.0.name') }}">
                            @error('members.0.name')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="members[0][email]" class="form-control @error('members.0.email') error @enderror" value="{{ old('members.0.email') }}">
                            @error('members.0.email')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="members[0][password]" class="form-control @error('members.0.password') error @enderror" minlength="8">
                            @error('members.0.password')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">Minimum 8 karaktera</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Organizacija</label>
                            <input type="text" name="members[0][organization]" class="form-control" value="{{ old('members.0.organization') }}" placeholder="Potpredsjednik Opštine / Starješina organa lokalne uprave">
                        </div>
                        <input type="hidden" name="members[0][position]" value="predsjednik">
                        <input type="hidden" name="members[0][member_type]" value="opstina">
                    </div>

                    <!-- 2 člana - Opština -->
                    <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 16px;">2. Član - Predstavnik Opštine</h3>
                        <div class="form-group">
                            <label class="form-label">Ime i prezime</label>
                            <input type="text" name="members[1][name]" class="form-control @error('members.1.name') error @enderror" value="{{ old('members.1.name') }}">
                            @error('members.1.name')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="members[1][email]" class="form-control @error('members.1.email') error @enderror" value="{{ old('members.1.email') }}">
                            @error('members.1.email')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="members[1][password]" class="form-control @error('members.1.password') error @enderror" minlength="8">
                            @error('members.1.password')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">Minimum 8 karaktera</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Organizacija</label>
                            <input type="text" name="members[1][organization]" class="form-control" value="{{ old('members.1.organization') }}" placeholder="Naziv organizacije">
                        </div>
                        <input type="hidden" name="members[1][position]" value="clan">
                        <input type="hidden" name="members[1][member_type]" value="opstina">
                    </div>

                    <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                        <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 16px;">3. Član - Predstavnik Opštine</h3>
                        <div class="form-group">
                            <label class="form-label">Ime i prezime</label>
                            <input type="text" name="members[2][name]" class="form-control @error('members.2.name') error @enderror" value="{{ old('members.2.name') }}">
                            @error('members.2.name')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="members[2][email]" class="form-control @error('members.2.email') error @enderror" value="{{ old('members.2.email') }}">
                            @error('members.2.email')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="members[2][password]" class="form-control @error('members.2.password') error @enderror" minlength="8">
                            @error('members.2.password')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">Minimum 8 karaktera</div>
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
                        <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 16px;">4. Član - Predstavnica Udruženja/Udruženja preduzetnica/Biznisa/Akademske zajednice</h3>
                        <div class="form-group">
                            <label class="form-label">Ime i prezime</label>
                            <input type="text" name="members[3][name]" class="form-control @error('members.3.name') error @enderror" value="{{ old('members.3.name') }}">
                            @error('members.3.name')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="members[3][email]" class="form-control @error('members.3.email') error @enderror" value="{{ old('members.3.email') }}">
                            @error('members.3.email')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="members[3][password]" class="form-control @error('members.3.password') error @enderror" minlength="8">
                            @error('members.3.password')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">Minimum 8 karaktera</div>
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
                            <input type="text" name="members[4][name]" class="form-control @error('members.4.name') error @enderror" value="{{ old('members.4.name') }}">
                            @error('members.4.name')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="members[4][email]" class="form-control @error('members.4.email') error @enderror" value="{{ old('members.4.email') }}">
                            @error('members.4.email')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="members[4][password]" class="form-control @error('members.4.password') error @enderror" minlength="8">
                            @error('members.4.password')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                            <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">Minimum 8 karaktera</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Organizacija</label>
                            <input type="text" name="members[4][organization]" class="form-control" value="{{ old('members.4.organization') }}" placeholder="Ženska politička mreža">
                        </div>
                        <input type="hidden" name="members[4][position]" value="clan">
                        <input type="hidden" name="members[4][member_type]" value="zene_mreza">
                    </div>
                </div>

                <!-- Dodjela konkursa komisiji -->
                @if($competitions->count() > 0)
                <div style="margin-top: 32px; padding-top: 24px; border-top: 2px solid #e5e7eb;">
                    <h2 style="font-size: 20px; font-weight: 700; color: var(--primary); margin: 0 0 20px;">Dodjela konkursa</h2>
                    <div class="form-group">
                        <label class="form-label">Izaberi konkurs za ovu komisiju</label>
                        <select name="competition_id" class="form-control">
                            <option value="">-- Izaberi konkurs --</option>
                            @foreach($competitions as $competition)
                                <option value="{{ $competition->id }}" {{ old('competition_id') == $competition->id ? 'selected' : '' }}>
                                    {{ $competition->title }} ({{ $competition->year }})
                                </option>
                            @endforeach
                        </select>
                        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                            Možete dodijeliti konkurs kasnije.
                        </div>
                    </div>
                </div>
                @endif

                <div style="margin-top: 24px;">
                    <button type="submit" class="btn-primary">Sačuvaj komisiju</button>
                    <a href="{{ route('admin.commissions.index') }}" style="margin-left: 12px; color: #6b7280;">Otkaži</a>
                </div>
                <div style="margin-top: 12px; padding: 12px; background: #f0f9ff; border-left: 4px solid var(--primary); border-radius: 4px;">
                    <p style="font-size: 13px; color: #374151; margin: 0;">
                        <strong>Napomena:</strong> Možete kreirati komisiju sa bilo kojim brojem članova (1-5). Nema obaveznih članova - možete dodati bilo koji član. Ako dodajete člana, sva polja za tog člana moraju biti popunjena. Ostale članove možete dodati kasnije na stranici za upravljanje komisijom.
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

