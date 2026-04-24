@props([
    'label'   => null,
    'error'   => null,
    'options' => [],
])

<div class="flex flex-col gap-1">
    @if ($label)
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
        </label>
    @endif

    <select
        {{ $attributes->whereStartsWith(['wire:', 'x-', 'id', 'name']) }}
        class="w-full rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-4 py-2.5 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
    >
        {{ $slot }}
    </select>

    @if ($error)
        <p class="text-xs text-red-500 dark:text-red-400">{{ $error }}</p>
    @endif
</div>
