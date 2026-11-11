@php
    $accountName = app(\App\Identity\Runtime\UserNameService::class)->isAccountOnly($user);
    $nameFirst = $accountName ? $user->first_name : $subjectIdentity->firstName;
    $nameLast = $accountName ? $user->last_name : $subjectIdentity->lastName;
    $authorizedName = \App\Support\UserType::isLegalEntity($subjectIdentity->userType);
@endphp
<div class="profile-card bg-white rounded-lg p-6 mb-6">
    <h2>{{ $authorizedName ? 'Ime i prezime postojećeg ovlašćenog lica' : 'Ime i prezime' }}</h2>
    @if($authorizedName)
        <p>Ispravka imena postojećeg ovlašćenog lica ne mijenja naziv firme niti zamjenjuje ovlašćeno lice.</p>
    @endif
    <form method="POST" action="{{ route('profile.name.update') }}">
        @csrf
        @method('PUT')
        <label for="account_first_name">{{ $authorizedName ? 'Ime ovlašćenog lica' : 'Ime' }}</label>
        <input class="form-control" id="account_first_name" name="first_name" value="{{ old('first_name', $nameFirst) }}" maxlength="255" required>
        <label for="account_last_name">{{ $authorizedName ? 'Prezime ovlašćenog lica' : 'Prezime' }}</label>
        <input class="form-control" id="account_last_name" name="last_name" value="{{ old('last_name', $nameLast) }}" maxlength="255" required>
        <button class="btn btn-primary" type="submit">Sačuvaj ime i prezime</button>
    </form>
</div>
