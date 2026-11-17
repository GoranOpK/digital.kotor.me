@extends('layouts.app')

@section('content')
<div class="container" style="max-width: 720px; padding: 24px 16px;">
    <h1 style="font-size: 24px; font-weight: 700; margin-bottom: 8px;">Objavi Obavještenje</h1>
    <p style="color: #4b5563; margin-bottom: 20px;">PDF će se pojaviti u rubrici Obavještenja na početnoj stranici.</p>

    @if(session('success'))
        <div style="background: #ecfdf5; border: 1px solid #10b981; padding: 12px; border-radius: 8px; margin-bottom: 16px;">{{ session('success') }}</div>
    @endif
    @if($errors->any())
        <div style="background: #fef2f2; border: 1px solid #ef4444; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.notices.store') }}" enctype="multipart/form-data" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 20px;">
        @csrf
        <div style="margin-bottom: 16px;">
            <label for="title" style="display: block; font-weight: 600; margin-bottom: 8px;">Naslov</label>
            <input type="text" id="title" name="title" value="{{ old('title') }}" maxlength="255" required
                   style="display: block; width: 100%; box-sizing: border-box; padding: 8px; border: 1px solid #d1d5db; border-radius: 8px;">
        </div>
        <div style="margin-bottom: 16px;">
            <label for="public_display_date" style="display: block; font-weight: 600; margin-bottom: 8px;">Datum objave</label>
            <input type="date" id="public_display_date" name="public_display_date" value="{{ old('public_display_date', now()->toDateString()) }}" required
                   style="display: block; width: 100%; max-width: 240px; box-sizing: border-box; padding: 8px; border: 1px solid #d1d5db; border-radius: 8px;">
        </div>
        <div style="margin-bottom: 16px;">
            <label for="pdf" style="display: block; font-weight: 600; margin-bottom: 8px;">PDF dokument</label>
            <input type="file" id="pdf" name="pdf" accept="application/pdf" required>
            <p style="margin: 8px 0 0; color: #6b7280; font-size: 13px;">PDF do 5 MB.</p>
        </div>
        <button type="submit" class="btn btn-success">Objavi Obavještenje</button>
    </form>
</div>
@endsection
