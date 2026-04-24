<div
    class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-blue-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-xl mx-auto space-y-6">

        {{-- ====== ФОРМА (idle) ====== --}}
        @if ($state === 'idle')

            <div class="mb-8 text-center">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-600 shadow-lg mb-4">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Аналіз препаратів</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Введіть дані для отримання рекомендацій</p>
            </div>

            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl ring-1 ring-gray-200 dark:ring-gray-700 p-6 sm:p-8">
                <form wire:submit="submit" class="space-y-5">

                    <x-ui.select label="Тип аналізу" wire:model.live="analysisType"
                                 :error="$errors->first('analysisType')">
                        <option value="medicine_name">💊 Назва препарату</option>
                        <option value="symptoms">🤒 Симптоми</option>
                    </x-ui.select>

                    <x-ui.input
                        label="{{ $analysisType === 'medicine_name' ? 'Назва препарату' : 'Симптоми' }}"
                        wire:model="query"
                        placeholder="{{ $analysisType === 'medicine_name' ? 'Наприклад: Нурофен' : 'Наприклад: головний біль, температура' }}"
                        :error="$errors->first('query')"
                    />

                    <x-ui.input label="Вік пацієнта" type="number" wire:model="age" min="0" max="120"
                                placeholder="Вік у роках" :error="$errors->first('age')"/>

                    <x-ui.input label="Алергії або протипоказання" wire:model="contraindications"
                                placeholder="Наприклад: аспірин, пеніцилін, помідори, пилюка"
                                :error="$errors->first('contraindications')"/>

                    <x-ui.checkbox id="isPregnant" label="Пацієнт вагітний" wire:model="isPregnant"
                                   :error="$errors->first('isPregnant')"/>

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-2"></div>

                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-semibold py-3 px-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-150">
                        <svg wire:loading.remove wire:target="submit" class="w-4 h-4 shrink-0" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <svg wire:loading wire:target="submit" class="animate-spin w-4 h-4 shrink-0" fill="none"
                             viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <span wire:loading.remove wire:target="submit">Аналізувати</span>
                        <span wire:loading wire:target="submit">Обробляємо...</span>
                    </button>

                </form>
            </div>

            {{-- ====== LOADER (loading) ====== --}}
        @elseif ($state === 'loading')
            <div class="flex flex-col items-center justify-center py-24 gap-6">
                <div class="relative w-20 h-20">
                    <svg class="animate-spin w-20 h-20 text-indigo-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-2xl">💊</span>
                </div>
                <div class="text-center">
                    <p class="text-lg font-semibold text-gray-800 dark:text-white">Аналізуємо препарат...</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Запитуємо AI, це може зайняти до 30
                        секунд</p>
                </div>
            </div>

            {{-- ====== ПОМИЛКА (error) ====== --}}
        @elseif ($state === 'error')
            <div class="bg-red-50 dark:bg-red-900/20 rounded-2xl ring-1 ring-red-200 dark:ring-red-800 p-6 sm:p-8">
                <div class="flex items-start gap-4">
                    <div
                        class="shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-red-100 dark:bg-red-800">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-300" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-red-800 dark:text-red-200">Виникла помилка</h3>
                        <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $errorMessage }}</p>
                    </div>
                </div>
                <button wire:click="newSearch"
                        class="mt-5 w-full flex items-center justify-center gap-2 border border-red-300 dark:border-red-700 text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-800/30 font-medium py-2.5 rounded-xl transition">
                    ← Спробувати знову
                </button>
            </div>

            {{-- ====== РЕЗУЛЬТАТ (result) ====== --}}
        @elseif ($state === 'result' && $result)
            @php $medicine = $result['medicine']; $recommendations = $result['recommendations']; @endphp

            {{-- Кнопка назад --}}
            <button wire:click="newSearch"
                    class="flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                ← Новий запит
            </button>

            {{-- Картка препарату --}}
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl ring-1 ring-gray-200 dark:ring-gray-700 p-6 sm:p-8 space-y-5">
                <div class="flex items-center gap-3">
                    <span class="text-3xl">💊</span>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $medicine['name'] }}</h2>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Показання</p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $medicine['symptoms'] }}</p>
                </div>

                @if (!empty($medicine['average_price_uah']))
                    <div
                        class="inline-flex items-center gap-2 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-sm font-medium px-4 py-2 rounded-xl">
                        💰 {{ $medicine['average_price_uah'] }}
                    </div>
                @endif

                @if (!empty($medicine['active_ingredients']))
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Діючі речовини</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($medicine['active_ingredients'] as $ing)
                                <span
                                    class="inline-flex items-center gap-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-medium px-3 py-1 rounded-full">
                                {{ $ing['name'] }} <span class="opacity-60">{{ $ing['quantity'] }}</span>
                            </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @php
                    $dbMedicine = \App\Models\Medicine::where('name', $medicine['name'])->first();
                @endphp
                @if ($dbMedicine)
                    <a href="{{ route('medicine.instructions', $dbMedicine->id) }}" target="_blank"
                       class="inline-flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                        📄 Переглянути інструкцію
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>
                @endif
            </div>

            {{-- Рекомендовані аналоги --}}
            @if (!empty($recommendations))
                <div>
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-3">
                        🔄 Рекомендовані аналоги ({{ count($recommendations) }})
                    </h3>
                    <div class="space-y-3">
                        @foreach ($recommendations as $rec)
                            @php $recDb = \App\Models\Medicine::where('name', $rec['name'])->first(); @endphp
                            <div
                                class="bg-white dark:bg-gray-800 rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 p-4 sm:p-5 space-y-3">
                                <div class="flex items-center justify-between gap-3 flex-wrap">
                                    <span
                                        class="font-semibold text-gray-900 dark:text-white text-sm">{{ $rec['name'] }}</span>
                                    <span class="text-xs font-bold px-2.5 py-1 rounded-full
                                        {{ $rec['match_percent'] >= 80 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' :
                                          ($rec['match_percent'] >= 60 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300' :
                                           'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300') }}">
                                        {{ $rec['match_percent'] }}% збіг
                                    </span>
                                </div>

                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $rec['symptoms'] }}</p>

                                @if (!empty($rec['average_price_uah']))
                                    <p class="text-xs font-medium text-green-600 dark:text-green-400">
                                        💰 {{ $rec['average_price_uah'] }}
                                    </p>
                                @endif

                                @if (!empty($rec['active_ingredients']))
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($rec['active_ingredients'] as $ing)
                                            <span
                                                class="inline-flex items-center gap-1 bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs px-2.5 py-0.5 rounded-full">
                                                {{ $ing['name'] }} <span
                                                    class="opacity-50">{{ $ing['quantity'] }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($recDb)
                                    <a href="{{ route('medicine.instructions', $recDb->id) }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400 hover:underline">
                                        📄 Інструкція
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
