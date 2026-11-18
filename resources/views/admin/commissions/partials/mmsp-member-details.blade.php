@php
    $editingThisMember = (string) old('_member_id') === (string) $member->id;
@endphp
<form method="POST" action="{{ route('admin.commissions.members.update-details', $member) }}" style="margin-top: 12px;">
    @csrf
    <input type="hidden" name="_member_id" value="{{ $member->id }}">
    <div class="form-group">
        <label class="form-label">Ime i prezime</label>
        <input type="text" name="name" class="form-control" value="{{ $editingThisMember ? old('name', $member->name) : $member->name }}" required maxlength="255">
        @if($editingThisMember)
            @error('name')
                <div class="error-message">{{ $message }}</div>
            @enderror
        @endif
    </div>
    <div class="form-group">
        <label class="form-label">Organizacija</label>
        <input type="text" name="organization" class="form-control" value="{{ $editingThisMember ? old('organization', $member->organization) : $member->organization }}" required maxlength="255">
        @if($editingThisMember)
            @error('organization')
                <div class="error-message">{{ $message }}</div>
            @enderror
        @endif
    </div>
    <div class="form-group">
        <label class="form-label">E-mail</label>
        <input type="email" name="email" class="form-control" value="{{ $editingThisMember ? old('email', $member->user?->email) : $member->user?->email }}" required maxlength="255">
        @if($editingThisMember)
            @error('email')
                <div class="error-message">{{ $message }}</div>
            @enderror
        @endif
    </div>
    <button type="submit" class="btn-sm btn-success">Sačuvaj podatke člana</button>
</form>
