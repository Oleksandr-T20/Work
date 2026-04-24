<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $medicine->name }} — Інструкція</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 antialiased">
<div class="max-w-3xl mx-auto px-4 py-12">

    <div class="mb-8">
        <a href="javascript:history.back()"
           class="inline-flex items-center gap-1.5 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
            ← Назад
        </a>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl ring-1 ring-gray-200 dark:ring-gray-700 p-6 sm:p-10">
        <div class="flex items-center gap-3 mb-8 pb-6 border-b border-gray-100 dark:border-gray-700">
            <span class="text-4xl">💊</span>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $medicine->name }}</h1>
                <p class="text-xs text-gray-400 mt-0.5">Інструкція до застосування</p>
            </div>
        </div>

        <div class="prose prose-gray dark:prose-invert max-w-none
                        prose-headings:font-semibold prose-headings:text-gray-800 dark:prose-headings:text-gray-100
                        prose-p:text-gray-600 dark:prose-p:text-gray-300
                        prose-li:text-gray-600 dark:prose-li:text-gray-300
                        prose-strong:text-gray-800 dark:prose-strong:text-gray-100">
            {!! $medicine->instructions_html !!}
        </div>
    </div>

    <p class="text-center text-xs text-gray-400 mt-8">
        Farma Helper · Інформація згенерована AI та має лише довідковий характер
    </p>
</div>
</body>
</html>
