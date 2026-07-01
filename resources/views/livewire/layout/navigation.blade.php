<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component {
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect(route('login'), navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-slate-900/50 border-b border-slate-800/60 backdrop-blur-md shadow-sm relative z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('medicine.analyze') }}" wire:navigate class="flex items-center gap-3 group">
                        <x-application-logo class="block h-9 w-auto transition-transform duration-300 group-hover:scale-105"/>
                        <span class="font-sans font-extrabold tracking-wider bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent text-xl select-none">
                            PharmAI
                        </span>
                    </a>
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                {{-- 🟢 ДОДАНО contentClasses: обнуляємо стандартну обводку та тіні Breeze --}}
                <x-dropdown align="right" width="48" contentClasses="py-0 bg-transparent ring-0 shadow-none">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 border border-slate-800/60 text-sm leading-4 font-medium rounded-xl text-slate-400 bg-slate-950/40 hover:text-slate-200 hover:border-slate-700/80 focus:outline-none transition ease-in-out duration-150 backdrop-blur-sm">
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name"
                                 x-on:profile-updated.window="name = $event.detail.name"></div>

                            <div class="ms-1.5 text-slate-500">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                     viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                          d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                          clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        {{-- 🟢 ФІКС: повністю видалено "border border-slate-800" --}}
                        <div class="bg-slate-900 rounded-xl overflow-hidden shadow-2xl">
                            <x-dropdown-link :href="route('profile')" wire:navigate class="hover:bg-slate-800/50 text-slate-300">
                                {{ __('Профіль') }}
                            </x-dropdown-link>

                            <button wire:click="logout" class="w-full text-start">
                                <x-dropdown-link class="hover:bg-red-950/30 text-red-400 hover:text-red-300">
                                    {{ __('Вийти') }}
                                </x-dropdown-link>
                            </button>
                        </div>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                        class="inline-flex items-center justify-center p-2 rounded-xl text-slate-400 hover:text-slate-200 hover:bg-slate-800/40 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16"/>
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                              stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-slate-900/90 border-b border-slate-800 backdrop-blur-lg">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('medicine.analyze')" :active="request()->routeIs('medicine.analyze')"
                                   wire:navigate class="text-slate-300">
                Аналіз препаратів
            </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-1 border-t border-slate-800">
            <div class="px-4">
                <div class="font-medium text-base text-slate-200"
                     x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name"
                     x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-slate-400">{{ auth()->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate class="text-slate-400 hover:text-slate-200">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <button wire:click="logout" class="w-full text-start">
                    <x-responsive-nav-link class="text-red-400 hover:text-red-300">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </button>
            </div>
        </div>
    </div>
</nav>