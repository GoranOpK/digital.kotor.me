@php
    use App\Services\CulturalActivity\CulturalActivityAdminDisplay;
    use App\Services\CulturalActivity\CulturalActivityCatalog;
@endphp
@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Evidencija aktivnosti</h1>
    </div>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vrijeme radnje</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Evidentirano</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Radnja</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Izvršilac</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Objekat</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Organizator kontekst</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Modul / identitet</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Context</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($records as $record)
                    <tr>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                            {{ $record->occurred_at?->format('d.m.Y H:i:s') }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">
                            {{ $record->created_at?->format('d.m.Y H:i:s') }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            <div>{{ CulturalActivityCatalog::labelForEventType($record->event_type) }}</div>
                            <div class="text-xs text-gray-500 font-mono">{{ $record->event_type }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ CulturalActivityAdminDisplay::actor($record) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 font-mono">
                            {{ CulturalActivityAdminDisplay::target($record) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ CulturalActivityAdminDisplay::organizerContext($record) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            <div>{{ $record->source_module }}</div>
                            <div class="text-xs text-gray-500 font-mono break-all">{{ $record->event_id }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 font-mono break-all">
                            {{ CulturalActivityAdminDisplay::context($record) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-500">Nema zapisa u Evidenciji aktivnosti.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $records->links() }}
    </div>
</div>
@endsection
