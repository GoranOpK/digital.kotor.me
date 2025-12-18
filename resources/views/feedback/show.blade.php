@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <div class="mb-6">
                    <a href="{{ route('admin.feedback.index') }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                        ← Nazad na sve povratne informacije
                    </a>
                </div>

                <h2 class="text-2xl font-bold mb-6">Detalji povratne informacije</h2>

                @if (session('success'))
                    <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Informacije o feedback-u -->
                <div class="space-y-4 mb-8">
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
                        <h3 class="text-lg font-semibold mb-2">{{ $feedback->subject }}</h3>
                        <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                            <span>
                                <strong>Od:</strong> 
                                {{ $feedback->getSenderName() }}
                                @if($feedback->user)
                                    ({{ $feedback->user->email }})
                                @elseif($feedback->email)
                                    ({{ $feedback->email }})
                                @endif
                            </span>
                            <span>
                                <strong>Datum:</strong> {{ $feedback->created_at->format('d.m.Y H:i') }}
                            </span>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-semibold mb-2">Poruka:</h4>
                        <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $feedback->message }}</p>
                    </div>

                    @if($feedback->admin_response)
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h4 class="font-semibold mb-2">Odgovor administratora:</h4>
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{{ $feedback->admin_response }}</p>
                            @if($feedback->responded_at)
                                <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                                    Odgovoreno: {{ $feedback->responded_at->format('d.m.Y H:i') }}
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Forma za ažuriranje -->
                <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 class="text-lg font-semibold mb-4">Ažuriraj status i odgovor</h3>
                    
                    <form method="POST" action="{{ route('admin.feedback.update', $feedback) }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Status
                            </label>
                            <select 
                                name="status" 
                                id="status"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                @foreach(App\Models\Feedback::getStatuses() as $value => $label)
                                    <option value="{{ $value }}" {{ $feedback->status === $value ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="admin_response" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Odgovor
                            </label>
                            <textarea 
                                name="admin_response" 
                                id="admin_response" 
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >{{ old('admin_response', $feedback->admin_response) }}</textarea>
                            @error('admin_response')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end">
                            <button 
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition"
                            >
                                Ažuriraj
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
