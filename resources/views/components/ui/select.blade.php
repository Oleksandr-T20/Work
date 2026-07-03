@props([
    'label'   => null,
    'error'   => null,
    'options' => [],
])

<div class="flex flex-col gap-1.5">
    @if ($label)
       <label class="text-sm font-medium text-slate-300">
            {{ $label }}
        </label>
    @endif

    <div class="relative w-full">
        <select
            {{ $attributes->whereStartsWith(['wire:', 'x-', 'id', 'name']) }}
            class="w-full bg-slate-950/50 bg-none border border-slate-800/80 text-slate-100 focus:border-indigo-500 focus:ring-indigo-500/20 rounded-xl px-4 py-2.5 text-sm shadow-inner transition-all duration-300 focus:outline-none cursor-pointer pr-10 appearance-none [&>option]:bg-slate-900 [&>option]:text-slate-100 {{ $attributes->get('class') }}"
        >
            {{ $slot }}
        </select>
        
        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
            </svg>
        </div>
    </div>

    @if ($error)
        <p class="text-xs text-rose-400 font-medium mt-0.5">{{ $error }}</p>
    @endif
</div>