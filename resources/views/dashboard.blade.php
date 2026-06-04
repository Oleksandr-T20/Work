<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-6 relative z-10">
        {{-- Пряме підключення нашого Livewire-компонента форми аналізу --}}
        <livewire:medicine.analyze-form />
    </div>

    <div class="w-full max-w-2xl mx-auto mt-8 px-4">
    
        <div class="flex flex-col items-center gap-2 mb-8">
            <div class="p-3 bg-slate-900/50 rounded-full border border-slate-800 shadow-[0_0_20px_rgba(99,102,241,0.15)]">
                <x-application-logo />
            </div>
        
             <h2 class="text-3xl font-extrabold tracking-wider bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
                PharmAI
             </h2>
             <p class="text-xs text-slate-400 tracking-widest uppercase">Інтелектуальний аналіз та пошук аналогів</p>
        </div>

        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 shadow-[0_20px_50px_rgba(0,0,0,0.4)] backdrop-blur-md">
        
            <div class="text-sm text-slate-400 mb-6 text-center border-b border-slate-800/60 pb-4">
                Введіть дані пацієнта для отримання миттєвих рекомендацій
            </div>

            <form wire:submit="analyze">
                <div class="mt-6">
                    <button type="submit" class="w-full flex items-center justify-center gap-2 py-3.5 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-semibold rounded-xl shadow-[0_4px_20px_rgba(99,102,241,0.3)] transition-all duration-300 hover:shadow-[0_4px_25px_rgba(139,92,246,0.5)] active:scale-[0.98]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        Аналізувати препарат
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
