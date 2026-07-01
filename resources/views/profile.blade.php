<x-app-layout>
    <div class="py-12 relative z-20 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- 🎯 ОНОВЛЕНИЙ БРЕНДОВАНИЙ ЗАГОЛОВОК (ВЕЛИКИЙ, ФІРМОВИЙ ГРАДІЄНТ) --}}
        <div class="mb-10 flex flex-col gap-1">
            <h2 class="text-3xl font-black tracking-wider bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">
                {{ __('Налаштування профілю') }}
            </h2> 
             {{--<p class="text-sm text-slate-400 tracking-wide">Керування обліковим записом PharmAI</p>--}}
        </div>

        {{-- Контейнер з секціями, розділеними тонкими глянцевими лініями --}}
        <div class="space-y-12">

            {{-- Секція 1: Інформація профілю --}}
            <div class="pb-12 border-b border-slate-800/40">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            {{-- Секція 2: Оновлення пароля --}}
            <div class="pb-12 border-b border-slate-800/40">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            {{-- Секція 3: Видалення акаунту --}}
            <div class="max-w-xl">
                <livewire:profile.delete-user-form />
            </div>

        </div>
    </div>
</x-app-layout>