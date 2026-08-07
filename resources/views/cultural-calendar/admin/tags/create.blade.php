@extends('layouts.app')

@section('content')
<div class="kk-shell mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Nova oznaka</h1>
        <a href="{{ route('cultural-tags.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
            Nazad na listu
        </a>
    </div>

    <form method="POST" action="{{ route('cultural-tags.store') }}" class="bg-white rounded-lg border border-gray-200 p-6">
        @csrf
        @include('cultural-calendar.admin.tags.partials.form', ['tag' => null])
        <div style="margin-top:24px; padding-top:12px; border-top:1px solid #e5e7eb;">
            <button type="submit" style="display:inline-block; background:#b91c1c; color:#fff; border:none; border-radius:8px; padding:10px 16px; font-weight:600; cursor:pointer;">
                Sačuvaj oznaku
            </button>
        </div>
    </form>
</div>
@endsection
