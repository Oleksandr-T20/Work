@props([
    'label'  => null,
    'error'  => null,
    'type'   => 'text',
])

<div class="flex flex-col gap-1">
    @if ($label)
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
        </label>
    @endif

    <input
        type="{{ $type }}"
        {{ $attributes->whereStartsWith(['wire:', 'x-', 'id', 'name', 'placeholder', 'min', 'max', 'step', 'autocomplete']) }}
        class="w-full rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-4 py-2.5 text-sm shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition {{ $attributes->get('class') }}"
    />

    @if ($error)
        <p class="text-xs text-red-500 dark:text-red-400">{{ $error }}</p>
    @endif
</div>
