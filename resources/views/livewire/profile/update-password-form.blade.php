<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => ['required', 'string', 'current_password'],
                'password' => ['required', 'string', Password::defaults(), 'confirmed'],
            ], [
                
                'current_password.required' => 'Поле поточного пароля є обовʼязковим.',
                'current_password.current_password' => 'Вказано неправильний поточний пароль.',
                
                'password.required' => 'Поле нового пароля є обовʼязковим.',
                'password.confirmed' => 'Підтвердження нового пароля не збігається.',
                'password.min' => 'Пароль має містити щонайменше :min символів.',
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}; ?>

<section>
    <header>
        <h2 class="text-lg font-medium text-slate-200">
            {{ __('Зміна пароля') }}
        </h2>

        <p class="mt-1 text-sm text-slate-400">
            {{ __('Переконайтеся, що ваш пароль відповідає вимогам безпеки.') }}
        </p>
    </header>

    <form wire:submit="updatePassword" class="mt-6 space-y-6">
        <div>
            {{-- 🟢 ПЕРЕКЛАД: Current Password -> Поточний пароль --}}
            <x-input-label for="update_password_current_password" :value="__('Поточний пароль')" class="text-slate-300" />
            <x-text-input wire:model="current_password" id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->get('current_password')" class="mt-2" />
        </div>

        <div>
            {{-- 🟢 ПЕРЕКЛАД: New Password -> Новий пароль --}}
            <x-input-label for="update_password_password" :value="__('Новий пароль')" class="text-slate-300" />
            <x-text-input wire:model="password" id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            {{-- 🟢 ПЕРЕКЛАД: Confirm Password -> Підтвердження пароля --}}
            <x-input-label for="update_password_password_confirmation" :value="__('Підтвердження пароля')" class="text-slate-300" />
            <x-text-input wire:model="password_confirmation" id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Зберегти') }}</x-primary-button>

            <x-action-message class="me-3 text-emerald-400 font-medium text-sm" on="password-updated">
                {{ __('Збережено.') }}
            </x-action-message>
        </div>
    </form>
</section>