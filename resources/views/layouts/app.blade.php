<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'PharmAI') }}</title>

        <link rel="icon" type="image/x-icon" href="{{ asset('favicon.png') }}">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-200 bg-slate-950">
        <div class="min-h-screen relative">
        
            <div class="fixed inset-0 z-0 bg-cover bg-center scale-105 filter blur-[3px]" 
                style="background-image: url('{{ asset('images/medical-bg.jpg') }}');">
            </div>

            <div class="fixed inset-0 z-10 bg-slate-950/75 backdrop-blur-[1px]"></div>

            <div class="relative z-20 min-h-screen flex flex-col">
            
                <livewire:layout.navigation />

                @if (isset($header))
                    <header class="bg-slate-900/40 border-b border-slate-800/50 backdrop-blur-md shadow-sm">
                        <div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endif

                <main class="flex-grow">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
