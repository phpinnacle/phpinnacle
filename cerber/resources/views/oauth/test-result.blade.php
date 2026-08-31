<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('phpinnacle-cerber::auth.test.title', ['provider' => $provider]) }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="flex items-center gap-4 mb-6">
                @if($success)
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                @else
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                @endif
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 mb-1">
                        {{ $provider }}
                    </h1>
                    <p class="text-lg {{ $success ? 'text-green-600' : 'text-red-600' }}">
                        {{ $message }}
                    </p>
                </div>
            </div>

            @if($userData)
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <h2 class="text-sm font-semibold text-gray-700 mb-3">{{ __('phpinnacle-cerber::auth.test.user') }}:</h2>
                <dl class="space-y-2">
                    @foreach($userData as $key => $value)
                        <div class="flex">
                            <dt class="text-sm font-medium text-gray-600 w-24">{{ ucfirst($key) }}:</dt>
                            <dd class="text-sm text-gray-900 flex-1">{{ $value ?? '-' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
            @endif

            @if($error)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                <h2 class="text-sm font-semibold text-red-800 mb-2">{{ __('phpinnacle-cerber::auth.test.error') }}:</h2>
                <pre class="text-xs text-red-700 whitespace-pre-wrap break-words">{{ $error }}</pre>
            </div>
            @endif

            <div class="flex justify-end">
                <button 
                    onclick="window.close()" 
                    class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors"
                >
                    {{ __('phpinnacle-cerber::auth.test.close') }}
                </button>
            </div>
        </div>
    </div>
</body>
</html>
