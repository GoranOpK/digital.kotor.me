@extends('layouts.app')
@section('content')
<div class="container mx-auto px-4 py-8">
    <h1>Izmjena imena i prezimena</h1>
    @if(session('success'))<p role="status">{{ session('success') }}</p>@endif
    @if($errors->any())
        <ul role="alert">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    @endif
    @include('profile.partials.update-name-form')
</div>
@endsection
