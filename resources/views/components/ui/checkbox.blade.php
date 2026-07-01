@props([
    'label' => null,
    'error' => null,
    'id'    => null,
])

<div class="flex flex-col gap-1">
    <label for="{{ $id }}" class="inline-flex items-center gap-3 cursor-pointer select-none">
        
         @if ($label)
            <span class="text-sm font-medium text-slate-300 transition-colors duration-200 hover:text-slate-200">
                {{ $label }}
            </span>
        @endif

        <input
            type="checkbox"
            id="{{ $id }}"
            {{ $attributes->whereStartsWith(['wire:', 'x-', 'name']) }}
            class="rounded-md bg-slate-950/60 border-slate-800/80 text-indigo-600 focus:ring-indigo-500/30 focus:ring-offset-slate-950 h-4 w-4 transition-all duration-200 cursor-pointer focus:outline-none {{ $attributes->get('class') }}"
        />
        
    </label>

    @if ($error)
        <p class="text-xs text-rose-400 font-medium mt-0.5">{{ $error }}</p>
    @endif
</div>