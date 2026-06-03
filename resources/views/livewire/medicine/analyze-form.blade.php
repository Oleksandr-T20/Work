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


            {{-- ====== МАТЕМАТИЧНИЙ ЗВІТ (Alpine + Chart.js) ====== --}}
            @if (!empty($recommendations))
            @php
                $chartLabels   = array_map(fn($r) => $r['name'], $recommendations);
                $chartSmart    = array_map(fn($r) => $r['smart_score'], $recommendations);
                $chartExact    = array_map(fn($r) => $r['match_exact'], $recommendations);
                $chartFuzzy    = array_map(fn($r) => $r['match_fuzzy'], $recommendations);
                $chartSymptoms = array_map(fn($r) => $r['match_symptoms'], $recommendations);
                $chartColors   = array_map(fn($r) =>
                    $r['smart_score'] >= 75 ? 'rgba(34,197,94,0.8)' :
                    ($r['smart_score'] >= 50 ? 'rgba(234,179,8,0.8)' : 'rgba(239,68,68,0.8)'),
                    $recommendations
                );
            @endphp

            {{-- farmaReport() визначено у app.js — завжди доступна, дані передаються аргументами --}}
            <div x-data="farmaReport(
                    {{ Js::from($chartLabels) }},
                    {{ Js::from($chartSmart) }},
                    {{ Js::from($chartExact) }},
                    {{ Js::from($chartFuzzy) }},
                    {{ Js::from($chartSymptoms) }},
                    {{ Js::from($chartColors) }}
                )">

                {{-- Кнопки управління — всередині Alpine-компонента для прямого доступу до toggle() --}}
                <div class="flex items-center justify-between gap-3">
                    <button wire:click="newSearch"
                            class="flex items-center gap-2 text-sm text-indigo-600 dark:text-indigo-400 hover:underline font-medium">
                        ← Новий запит
                    </button>
                    <button @click="toggle()"
                            class="flex items-center gap-2 text-sm font-medium bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 px-3 py-1.5 rounded-lg transition">
                        📊 {{ $analysisType === 'medicine_name' ? 'Звіт щодо аналогів' : 'Звіт щодо рекомендованих препаратів' }}
                    </button>
                </div>

                <div x-show="show"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-1"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl ring-1 ring-gray-200 dark:ring-gray-700 p-6 space-y-8">

                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-gray-800 dark:text-white">📊 {{ $analysisType === 'medicine_name' ? 'Звіт щодо аналогів' : 'Звіт щодо рекомендованих препаратів' }}</h3>
                        <span class="text-xs text-gray-400">Без урахування AI-інтерпретації</span>
                    </div>

                    {{-- Числова таблиця --}}
                    <div class="overflow-x-auto">
                        <table class="w-full text-xs text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <th class="py-2 pr-4 font-semibold text-gray-500 dark:text-gray-400">Препарат</th>
                                    <th class="py-2 px-3 font-semibold text-gray-500 dark:text-gray-400 text-center">🧠 Збіг</th>
                                    @if ($analysisType === 'medicine_name')
                                        <th class="py-2 px-3 font-semibold text-gray-500 dark:text-gray-400 text-center">🧪 Точні</th>
                                        <th class="py-2 px-3 font-semibold text-gray-500 dark:text-gray-400 text-center">🔬 Схожі</th>
                                    @endif
                                    <th class="py-2 px-3 font-semibold text-gray-500 dark:text-gray-400 text-center">🩺 Симптоми</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recommendations as $i => $rec)
                                    <tr class="border-b border-gray-50 dark:border-gray-700/50 {{ $i === 0 ? 'font-semibold' : '' }}">
                                        <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">
                                            @if ($i === 0)🏆 @endif {{ $rec['name'] }}
                                        </td>
                                        <td class="py-2 px-3 text-center font-bold
                                            {{ $rec['smart_score'] >= 75 ? 'text-green-600' : ($rec['smart_score'] >= 50 ? 'text-yellow-600' : 'text-red-500') }}">
                                            {{ $rec['smart_score'] }}%
                                        </td>
                                        @if ($analysisType === 'medicine_name')
                                            <td class="py-2 px-3 text-center text-indigo-600 dark:text-indigo-400">{{ $rec['match_exact'] }}%</td>
                                            <td class="py-2 px-3 text-center text-purple-600 dark:text-purple-400">{{ $rec['match_fuzzy'] }}%</td>
                                        @endif
                                        <td class="py-2 px-3 text-center text-blue-600 dark:text-blue-400">{{ $rec['match_symptoms'] }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Діаграма 1 --}}
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Інтелектуальний збіг аналогів</p>
                        <div wire:ignore>
                            <canvas id="chart-smart" style="max-height:{{ count($recommendations) * 38 + 20 }}px"></canvas>
                        </div>
                    </div>

                    {{-- Діаграма 2 --}}
                    @if ($analysisType === 'medicine_name')
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-3">Метрики збігу</p>
                            <div wire:ignore>
                                <canvas id="chart-metrics" style="max-height:320px"></canvas>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Картка препарату --}}
            <div
                class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl ring-1 ring-gray-200 dark:ring-gray-700 p-6 sm:p-8 space-y-5">
                <div class="flex items-center gap-3">
                    <span class="text-3xl">
            {{ $analysisType === 'medicine_name' ? '💊' : '🤒' }}
        </span>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $medicine['name'] }}</h2>
                </div>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">
                        {{ $analysisType === 'medicine_name' ? 'Показання' : 'Симптоми' }}
                    </p>
                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $medicine['symptoms'] }}</p>
                </div>

                {{-- Вік, вагітність, протипоказання --}}
                <div class="flex flex-wrap gap-2">
                    @if (!empty($medicine['min_age']))
                        @if ($age === null)
                            {{-- Вік не вказано — нейтральний синій бейдж --}}
                            <span class="inline-flex items-center gap-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-xs font-medium px-3 py-1 rounded-full">
                                👶 {{ $medicine['min_age'] }}
                            </span>
                        @elseif ($medicine['age_allowed'])
                            {{-- Вік вказано і підходить — зелений --}}
                            <span class="inline-flex items-center gap-1 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-xs font-medium px-3 py-1 rounded-full">
                                ✅ {{ $medicine['min_age'] }}
                            </span>
                        @else
                            {{-- Вік вказано але не підходить — червоний --}}
                            <span class="inline-flex items-center gap-1 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-xs font-medium px-3 py-1 rounded-full">
                                ⛔ {{ $medicine['min_age'] }} (вік не відповідає)
                            </span>
                        @endif
                    @endif

                    {{-- Вагітність — показуємо тільки якщо обрано чекбокс --}}
                    @if ($isPregnant && isset($medicine['pregnancy_safe']))
                        @if ($medicine['pregnancy_safe'])
                            <span class="inline-flex items-center gap-1 bg-pink-50 dark:bg-pink-900/20 text-pink-700 dark:text-pink-300 text-xs font-medium px-3 py-1 rounded-full">
                                🤰 Дозволено вагітним
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-xs font-medium px-3 py-1 rounded-full">
                                🚫 Не рекомендовано вагітним
                            </span>
                        @endif
                    @endif

                    @if (!empty($medicine['contraindication_matches']))
                        <span class="inline-flex items-center gap-1 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-xs font-medium px-3 py-1.5 rounded-full">
                            ⚠️ Збіг з протипоказаннями: {{ implode(', ', $medicine['contraindication_matches']) }}
                        </span>
                    @elseif (!empty($contraindications))
                        <span class="inline-flex items-center gap-1 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-xs font-medium px-3 py-1 rounded-full">
                            ✅ Збігу з Вашими протипоказаннями немає
                        </span>
                    @endif

                    {{-- Країна виробника --}}
                    @if (!empty($medicine['country']))
                        <span class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-medium px-3 py-1 rounded-full">
                            🏭 {{ $medicine['country'] }}
                        </span>
                    @endif

                    {{-- Доступність в Україні --}}
                    @if (isset($medicine['available_in_ukraine']))
                        @if ($medicine['available_in_ukraine'])
                            <span class="inline-flex items-center gap-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-xs font-medium px-3 py-1 rounded-full">
                                🇺🇦 Доступний в Україні
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300 text-xs font-medium px-3 py-1 rounded-full">
                                🌍 Тільки за кордоном
                            </span>
                        @endif
                    @endif
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
                                <span class="inline-flex items-center gap-1 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-medium px-3 py-1 rounded-full">
                                    {{ $ing['name'] }} <span class="opacity-60">{{ $ing['quantity'] }}</span>
                                </span>
                            @endforeach
                        </div>
                    @endif

                    {{-- Спосіб застосування та дози --}}
                    @if (!empty($medicine['dosage']))
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Спосіб застосування та дози</p>
                            <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">{{ $medicine['dosage'] }}</p>
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
                    <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-2">
                        🔄 {{ $analysisType === 'medicine_name' ? 'Рекомендовані аналоги' : 'Рекомендовані препарати' }} ({{ count($recommendations) }})
                    </h3>

                    {{-- Попередження: аналоги підібрані AI на основі міжнародної медичної бази --}}
                    <div class="flex items-start gap-2.5 bg-amber-50 dark:bg-amber-900/20 text-amber-800 dark:text-amber-300 text-xs rounded-xl px-4 py-3 mb-3 ring-1 ring-amber-200 dark:ring-amber-700">
                        <span class="shrink-0 text-base">ℹ️</span>
                        <p>
                            Препарати підібрані на основі <strong>міжнародної медичної бази</strong> AI і можуть відрізнятися від переліку на українських сайтах (наприклад, таблетки.ua).
                            Деякі препарати можуть бути недоступні в аптеках України або мати іншу торгову назву.
                            Перед застосуванням проконсультуйтеся з лікарем або фармацевтом.
                        </p>
                    </div>
                    <div class="space-y-3">
                        @foreach ($recommendations as $index => $rec)
                            @php $recDb = \App\Models\Medicine::where('name', $rec['name'])->first(); @endphp
                            <div class="bg-white dark:bg-gray-800 rounded-xl ring-1 ring-gray-200 dark:ring-gray-700 p-4 sm:p-5 space-y-3
                                {{ $index === 0 ? 'ring-2 ring-indigo-400 dark:ring-indigo-500' : '' }}">

                                {{-- Шапка: назва + smart_score --}}
                                <div class="flex items-center justify-between gap-3 flex-wrap">
                                    <div class="flex items-center gap-2">
                                        @if ($index === 0)
                                            <span title="Найкращий варіант для Вас" class="text-base">🏆</span>
                                        @endif
                                        <span class="font-semibold text-gray-900 dark:text-white text-sm">{{ $rec['name'] }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        {{-- Інтелектуальний бал (з урахуванням усіх обмежень) --}}
                                        <span class="text-xs font-bold px-2.5 py-1 rounded-full
                                            {{ $rec['smart_score'] >= 75 ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' :
                                              ($rec['smart_score'] >= 50 ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300' :
                                               'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300') }}">
                                            🧠 {{ $rec['smart_score'] }}%
                                        </span>
                                    </div>
                                </div>

                                {{-- Пояснення інтелектуального аналізу --}}
                                @if (!empty($rec['smart_reasons']))
                                    <div class="bg-gray-50 dark:bg-gray-700/40 rounded-lg px-3 py-2 space-y-1">
                                        @foreach ($rec['smart_reasons'] as $reason)
                                            <p class="text-xs flex items-start gap-1.5
                                                {{ $reason['type'] === 'positive' ? 'text-green-700 dark:text-green-400' :
                                                  ($reason['type'] === 'warning'  ? 'text-red-600 dark:text-red-400' :
                                                   'text-gray-500 dark:text-gray-400') }}">
                                                <span class="shrink-0 mt-px">
                                                    {{ $reason['type'] === 'positive' ? '✅' : ($reason['type'] === 'warning' ? '⚠️' : 'ℹ️') }}
                                                </span>
                                                {{ $reason['text'] }}
                                            </p>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Три деталізовані показники схожості --}}
                                <div class="grid grid-cols-{{ $analysisType === 'medicine_name' ? '3' : '1' }} gap-2 text-center">
                                    @if ($analysisType === 'medicine_name')
                                        {{-- 1. Точний збіг діючих речовин + дозування ±20% --}}
                                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg px-2 py-1.5">
                                            <p class="text-[10px] text-gray-400 dark:text-gray-500 leading-tight mb-0.5">🧪 Точні речовини</p>
                                            <p class="text-xs font-bold
                                                {{ $rec['match_exact'] >= 80 ? 'text-green-600 dark:text-green-400' :
                                                  ($rec['match_exact'] >= 40 ? 'text-yellow-600 dark:text-yellow-400' :
                                                   'text-red-500 dark:text-red-400') }}">
                                                {{ $rec['match_exact'] }}%
                                            </p>
                                        </div>

                                        {{-- 2. Нечіткий збіг — "ibuprofen" ↔ "ibuprofenum" --}}
                                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg px-2 py-1.5">
                                            <p class="text-[10px] text-gray-400 dark:text-gray-500 leading-tight mb-0.5">🔬 Схожі речовини</p>
                                            <p class="text-xs font-bold
                                                {{ $rec['match_fuzzy'] >= 80 ? 'text-green-600 dark:text-green-400' :
                                                  ($rec['match_fuzzy'] >= 40 ? 'text-yellow-600 dark:text-yellow-400' :
                                                   'text-red-500 dark:text-red-400') }}">
                                                {{ $rec['match_fuzzy'] }}%
                                            </p>
                                        </div>
                                    @endif

                                    {{-- 3. Збіг симптомів/показань (Jaccard) --}}
                                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg px-2 py-1.5">
                                        <p class="text-[10px] text-gray-400 dark:text-gray-500 leading-tight mb-0.5">🩺 Симптоми</p>
                                        <p class="text-xs font-bold
                                            {{ $rec['match_symptoms'] >= 80 ? 'text-green-600 dark:text-green-400' :
                                              ($rec['match_symptoms'] >= 40 ? 'text-yellow-600 dark:text-yellow-400' :
                                               'text-red-500 dark:text-red-400') }}">
                                            {{ $rec['match_symptoms'] }}%
                                        </p>
                                    </div>
                                </div>

                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $rec['symptoms'] }}</p>

                                {{-- Вік, вагітність, протипоказання аналогу --}}
                                <div class="flex flex-wrap gap-1.5">
                                    @if (!empty($rec['min_age']))
                                        @if ($age === null)
                                            <span class="inline-flex items-center gap-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                👶 {{ $rec['min_age'] }}
                                            </span>
                                        @elseif ($rec['age_allowed'])
                                            <span class="inline-flex items-center gap-1 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                ✅ {{ $rec['min_age'] }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                ⛔ {{ $rec['min_age'] }} (вік не відповідає)
                                            </span>
                                        @endif
                                    @endif

                                    @if ($isPregnant && isset($rec['pregnancy_safe']))
                                        @if ($rec['pregnancy_safe'])
                                            <span class="inline-flex items-center gap-1 bg-pink-50 dark:bg-pink-900/20 text-pink-700 dark:text-pink-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                🤰 Дозволено вагітним
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                🚫 Не рекомендовано вагітним
                                            </span>
                                        @endif
                                    @endif

                                    {{-- Країна виробника --}}
                                    @if (!empty($rec['country']))
                                        <span class="inline-flex items-center gap-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                            🏭 {{ $rec['country'] }}
                                        </span>
                                    @endif

                                    {{-- Доступність в Україні --}}
                                    @if (isset($rec['available_in_ukraine']))
                                        @if ($rec['available_in_ukraine'])
                                            <span class="inline-flex items-center gap-1 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                🇺🇦 Доступний в Україні
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                🌍 Тільки за кордоном
                                            </span>
                                        @endif
                                    @endif
                                </div>

                                @if (!empty($rec['average_price_uah']))
                                    <p class="text-xs font-medium text-green-600 dark:text-green-400">
                                        💰 {{ $rec['average_price_uah'] }}
                                    </p>
                                @endif

                                @if (!empty($rec['active_ingredients']))
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($rec['active_ingredients'] as $ing)
                                            <span class="inline-flex items-center gap-1 bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 text-xs px-2.5 py-0.5 rounded-full">
                                                {{ $ing['name'] }} <span class="opacity-50">{{ $ing['quantity'] }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Спосіб застосування та дози --}}
                                @if (!empty($rec['dosage']))
                                    <div class="bg-gray-50 dark:bg-gray-700/40 rounded-lg px-3 py-2">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400 mb-1">Спосіб застосування та дози</p>
                                        <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed">{{ $rec['dosage'] }}</p>
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
