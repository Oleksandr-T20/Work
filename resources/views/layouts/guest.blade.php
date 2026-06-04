<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased relative min-h-screen overflow-x-hidden bg-slate-950">
    
     <!-- ЛЕГКЕ РОЗМИТТЯ: змінено з blur-md на контроль товщини у 3 пікселі -->
     <div class="fixed inset-0 z-0 bg-cover bg-center scale-105 filter blur-[3px]" 
         style="background-image: url('{{ asset('images/medical-bg.jpg') }}');">
     </div>

     <!-- Напівпрозорий темний шар став трохи світлішим (65%), щоб малюнок було краще видно -->
     <div class="fixed inset-0 z-10 bg-slate-950/65 backdrop-blur-[1px]"></div>

     <!-- Основна зона контенту -->
     <div class="relative z-20 min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        <div class="flex flex-col items-center gap-2">
            <a href="/" wire:navigate class="transition-transform duration-300 hover:scale-105">
                <x-application-logo />
            </a>
            <!-- Назва вашого проєкту під логотипом -->
            <h1 class="text-3xl font-extrabold tracking-wider bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent drop-shadow">
                PharmAI
            </h1>
            <p class="text-xs text-slate-400/80 tracking-widest uppercase">Master's Research Project</p>
        </div>

        <!-- Картка форми -->
        <div class="w-full sm:max-w-md mt-6 px-6 py-5 bg-slate-900/80 border border-slate-800/80 shadow-[0_20px_50px_rgba(0,0,0,0.5)] backdrop-blur-md overflow-hidden sm:rounded-xl">
            {{ $slot }}
        </div>
     </div>
    </body>
</html>
