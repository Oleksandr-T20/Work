@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full bg-slate-950/50 border-slate-800/80 text-slate-100 placeholder-slate-500 focus:border-indigo-500 focus:ring-indigo-500/20 rounded-xl shadow-inner transition-all duration-300 focus:shadow-[0_0_15px_rgba(99,102,241,0.1)]']) }}>