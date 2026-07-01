@props([
    'label'  => null,
    'error'  => null,
    'type'   => 'text',
])

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label class="text-sm font-medium text-slate-300">
            {{ $label }}
        </label>
    @endif

    <input
        type="{{ $type }}"
        {{ $attributes->whereStartsWith(['wire:', 'x-', 'id', 'name', 'placeholder', 'min', 'max', 'step', 'autocomplete']) }}
        class="w-full bg-slate-950/50 border-slate-800/80 text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500/20 rounded-xl px-4 py-2.5 text-sm shadow-inner transition-all duration-300 focus:shadow-[0_0_15px_rgba(99,102,241,0.1)] focus:outline-none [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none [appearance:textfield] {{ $attributes->get('class') }}"
    />

    @if ($error)
        <p class="text-xs text-rose-400 font-medium mt-0.5">{{ $error }}</p>
    @endif
</div>