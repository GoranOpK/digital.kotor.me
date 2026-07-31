@extends('layouts.app')

@section('content')
<div class="admin-page" style="background:#f9fafb; min-height:100vh; padding:24px 0;">
    <div class="container mx-auto px-4">
        <div class="page-header no-print" style="background:linear-gradient(90deg,#0B3D91,#0A347B); color:#fff; padding:24px; border-radius:16px; margin-bottom:24px;">
            <h1 style="color:#fff; font-size:28px; font-weight:700; margin:0;">Odluka o dodjeli sredstava</h1>
        </div>

        @include('competitions.partials.decision-document')

        <div style="text-align: center; margin-top: 24px;" class="no-print">
            @if((isset($isSuperAdmin) && $isSuperAdmin) || (isset($isChairman) && $isChairman))
            <button onclick="window.print()" class="btn" style="background: #0B3D91; color: #fff; padding: 12px 24px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600;">Štampaj Odluku</button>
            @endif
            <a href="{{ route('admin.competitions.ranking', $competition) }}" class="btn" style="background: #6b7280; color: #fff; padding: 12px 24px; border-radius: 8px; text-decoration: none; margin-left: 8px; display: inline-block;">Nazad na rang listu</a>
            @if(((isset($isSuperAdmin) && $isSuperAdmin) || (isset($isChairman) && $isChairman)) && !in_array($competition->status, ['closed', 'completed']) && $competition->hasChairmanCompletedDecisions())
                <form method="POST" action="{{ route('admin.competitions.close', $competition) }}" style="display: inline; margin-left: 8px;">
                    @csrf
                    <button type="submit" class="btn" style="background: #dc2626; color: #fff; padding: 12px 24px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600;">Zatvori konkurs</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
