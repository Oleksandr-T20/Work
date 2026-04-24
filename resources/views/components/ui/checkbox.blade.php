@props([
    'label' => null,
    'error' => null,
    'id'    => null,
])

<div class="flex flex-col gap-1">
    <div class="flex items-center gap-3">
        <input
            type="checkbox"
            id="{{ $id }}"
            {{ $attributes->whereStartsWith(['wire:', 'x-', 'name']) }}
            class="w-4 h-4 rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700 transition"
        />
        @if ($label)
            <label for="{{ $id }}"
                   class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer select-none">
                {{ $label }}
            </label>
        @endif
    </div>

    @if ($error)
        <p class="text-xs text-red-500 dark:text-red-400">{{ $error }}</p>
    @endif
</div>
