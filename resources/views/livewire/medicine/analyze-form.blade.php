<div class="w-full max-w-xl mx-auto py-12 px-4 sm:px-6 lg:px-8 bg-transparent relative z-20">
    <div class="space-y-6">

        {{-- ====== ФОРМА (idle) ====== --}}
        @if ($state === 'idle')

            <div class="mb-8 text-center flex flex-col items-center gap-2">
                <div class="inline-flex items-center justify-center p-3 bg-slate-900/50 rounded-full border border-slate-800 shadow-[0_0_20px_rgba(99,102,241,0.15)] mb-2">
                    <x-application-logo />
                </div>
                <h1 class="text-3xl font-extrabold tracking-wider bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400 bg-clip-text text-transparent">PharmAI</h1>
                <p class="mt-1 text-sm text-slate-400">Введіть дані для отримання рекомендацій</p>
            </div>

            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 sm:p-8 shadow-[0_20px_50px_rgba(0,0,0,0.4)] backdrop-blur-md">
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

                    <div class="border-t border-slate-800/60 pt-2"></div>

                    <button type="submit"
                            class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-semibold py-3 px-6 rounded-xl shadow-[0_4px_20px_rgba(99,102,241,0.2)] transition-all duration-300 hover:shadow-[0_4px_25px_rgba(139,92,246,0.4)] active:scale-[0.98]">
                        <svg wire:loading.remove wire:target="submit" class="w-4 h-4 shrink-0" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <svg wire:loading wire:target="submit" class="animate-spin w-4 h-4 shrink-0 text-white" fill="none"
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
            <div class="flex flex-col items-center justify-center py-24 gap-6 bg-slate-900/60 border border-slate-800/80 rounded-2xl p-8 shadow-xl backdrop-blur-md">
                <div class="relative w-20 h-20">
                    <svg class="animate-spin w-20 h-20 text-indigo-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                        <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <span class="absolute inset-0 flex items-center justify-center text-2xl">💊</span>
                </div>
                <div class="text-center">
                    <p class="text-lg font-semibold text-white">Аналізуємо препарат...</p>
                    <p class="text-sm text-slate-400 mt-1">Запитуємо AI, це може зайняти до 30 секунд</p>
                </div>
            </div>

            {{-- ====== ПОМИЛКА (error) ====== --}}
        @elseif ($state === 'error')
            <div class="bg-red-950/40 border border-red-900/50 rounded-2xl p-6 sm:p-8 backdrop-blur-md shadow-xl">
                <div class="flex items-start gap-4">
                    <div class="shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-red-900/50 border border-red-700">
                        <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-red-200">Виникла помилка</h3>
                        <p class="mt-1 text-sm text-red-300/90">{{ $errorMessage }}</p>
                    </div>
                </div>
                <button wire:click="newSearch"
                        class="mt-5 w-full flex items-center justify-center gap-2 border border-red-800 text-red-300 hover:bg-red-900/30 font-medium py-2.5 rounded-xl transition">
                    ← Спробувати знову
                </button>
            </div>

            {{-- ====== ПОПЕРЕДЖЕННЯ ВАЛІДАЦІЇ (validation_error) ====== --}}
        @elseif ($state === 'validation_error')
            <div class="bg-amber-950/40 border border-amber-900/50 rounded-2xl p-6 sm:p-8 backdrop-blur-md shadow-xl">
                <div class="flex items-start gap-4">
                    <div class="shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-amber-900/50 border border-amber-700">
                        <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-amber-200">Невірно вказана назва препарату</h3>
                        <p class="mt-1 text-sm text-amber-300/90">{{ $validationWarning }}</p>
                    </div>
                </div>
                <div class="mt-5 flex flex-col sm:flex-row gap-3">
                    <button wire:click="newSearch"
                            class="flex-1 flex items-center justify-center gap-2 border border-amber-800 text-amber-300 hover:bg-amber-900/20 font-medium py-2.5 rounded-xl transition">
                        ← Змінити запит
                    </button>
                    <button wire:click="searchBySymptoms"
                            class="flex-1 flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white font-medium py-2.5 rounded-xl transition">
                        🤒 Шукати за симптомами
                    </button>
                </div>
            </div>

            {{-- ====== ПОПЕРЕДЖЕННЯ: ВВЕДЕНО НАЗВУ ПРЕПАРАТУ (validation_error_medicine) ====== --}}
        @elseif ($state === 'validation_error_medicine')
            <div class="bg-blue-950/40 border border-blue-900/50 rounded-2xl p-6 sm:p-8 backdrop-blur-md shadow-xl">
                <div class="flex items-start gap-4">
                    <div class="shrink-0 w-10 h-10 flex items-center justify-center rounded-full bg-blue-900/50 border border-blue-700">
                        <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-blue-200">Введено назву препарату</h3>
                        <p class="mt-1 text-sm text-blue-300/90">{{ $validationWarning }}</p>
                    </div>
                </div>
                <div class="mt-5 flex flex-col sm:flex-row gap-3">
                    <button wire:click="newSearch"
                            class="flex-1 flex items-center justify-center gap-2 border border-blue-800 text-blue-300 hover:bg-blue-900/20 font-medium py-2.5 rounded-xl transition">
                        ← Змінити запит
                    </button>
                    <button wire:click="searchByMedicineName"
                            class="flex-1 flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-xl transition">
                        💊 Шукати за назвою
                    </button>
                </div>
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

                <div x-data="farmaReport(
                        {{ Js::from($chartLabels) }},
                        {{ Js::from($chartSmart) }},
                        {{ Js::from($chartExact) }},
                        {{ Js::from($chartFuzzy) }},
                        {{ Js::from($chartSymptoms) }},
                        {{ Js::from($chartColors) }}
                    )" class="space-y-4">

                    <div class="flex items-center justify-between gap-3">
                        <button wire:click="newSearch"
                                class="flex items-center gap-2 text-sm text-indigo-400 hover:underline font-medium">
                            ← Новий запит
                        </button>
                        <button @click="toggle()"
                                class="flex items-center gap-2 text-sm font-medium bg-slate-900/80 text-indigo-400 border border-slate-800 hover:bg-slate-800 px-3 py-1.5 rounded-lg transition">
                            📊 {{ $analysisType === 'medicine_name' ? 'Звіт щодо аналогів' : 'Звіт щодо рекомендованих препаратів' }}
                        </button>
                    </div>

                    <div x-show="show"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="bg-slate-900/80 border border-slate-800 rounded-2xl shadow-xl p-6 space-y-8 backdrop-blur-md text-white">

                        <div class="flex items-center justify-between">
                            <h3 class="text-base font-bold text-white">📊 {{ $analysisType === 'medicine_name' ? 'Звіт щодо аналогів' : 'Звіт щодо рекомендованих препаратів' }}</h3>
                            <span class="text-xs text-slate-500">Bez AI-interpretaciyi</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-800 text-slate-400">
                                        <th class="py-2 pr-4 font-semibold">Препарат</th>
                                        <th class="py-2 px-3 font-semibold text-center">🧠 Збіг</th>
                                        @if ($analysisType === 'medicine_name')
                                            <th class="py-2 px-3 font-semibold text-center">🧪 Точні</th>
                                            <th class="py-2 px-3 font-semibold text-center">🔬 Схожі</th>
                                        @endif
                                        <th class="py-2 px-3 font-semibold text-center">% Симптоми</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recommendations as $i => $rec)
                                        <tr class="border-b border-slate-800/50 {{ $i === 0 ? 'font-semibold text-white' : 'text-slate-300' }}">
                                            <td class="py-2 pr-4">
                                                @if ($i === 0)🏆 @endif {{ $rec['name'] }}
                                            </td>
                                            <td class="py-2 px-3 text-center font-bold
                                                {{ $rec['smart_score'] >= 75 ? 'text-green-400' : ($rec['smart_score'] >= 50 ? 'text-yellow-400' : 'text-red-400') }}">
                                                {{ $rec['smart_score'] }}%
                                            </td>
                                            @if ($analysisType === 'medicine_name')
                                                <td class="py-2 px-3 text-center text-indigo-400">{{ $rec['match_exact'] }}%</td>
                                                <td class="py-2 px-3 text-center text-purple-400">
                                                    {{-- Якщо точний збіг 100%, замість дублювання 100% виводимо прочерк --}}
                                                    {{ $rec['match_exact'] == 100 ? '—' : $rec['match_fuzzy'] . '%' }}
                                                </td>
                                            @endif
                                            <td class="py-2 px-3 text-center text-blue-400">{{ $rec['match_symptoms'] }}%</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Інтелектуальний збіг аналогів</p>
                            <div wire:ignore>
                                <canvas id="chart-smart" style="max-height:{{ count($recommendations) * 38 + 20 }}px"></canvas>
                            </div>
                        </div>

                        @if ($analysisType === 'medicine_name')
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-3">Метрики збігу</p>
                                <div wire:ignore>
                                    <canvas id="chart-metrics" style="max-height:320px"></canvas>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Картка препарату --}}
            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 sm:p-8 space-y-5 backdrop-blur-md shadow-xl text-white">
                <div class="flex items-center gap-3">
                    <span class="text-3xl">
                        {{ $analysisType === 'medicine_name' ? '💊' : '🤒' }}
                    </span>
                    <h2 class="text-xl font-bold text-white">{{ $medicine['name'] }}</h2>
                </div>

                @if ($analysisType === 'medicine_name')
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Показання</p>
                        <p class="text-sm text-slate-300">{{ $medicine['symptoms'] }}</p>
                    </div>
                @endif

                <div class="flex flex-wrap gap-2">
                    @if (!empty($medicine['min_age']))
                        @if ($age === null)
                            <span class="inline-flex items-center gap-1 bg-slate-800 border border-slate-700 text-blue-400 text-xs font-medium px-3 py-1 rounded-full">
                                👶 {{ $medicine['min_age'] }}
                            </span>
                        @elseif ($medicine['age_allowed'])
                            <span class="inline-flex items-center gap-1 bg-green-950/40 border border-green-800 text-green-400 text-xs font-medium px-3 py-1 rounded-full">
                                ✅ {{ $medicine['min_age'] }}
                            </span>
                        
                        @else
                            <span class="inline-flex items-center gap-1 bg-red-950/40 border border-red-800 text-red-400 text-xs font-medium px-3 py-1 rounded-full">
                                ⛔ {{ $medicine['min_age'] }} (вік не відповідає)
                            </span>
                        @endif
                    @endif

                    @if ($isPregnant && isset($medicine['pregnancy_safe']))
                        @if ($medicine['pregnancy_safe'])
                            <span class="inline-flex items-center gap-1 bg-pink-950/40 border border-pink-800 text-pink-400 text-xs font-medium px-3 py-1 rounded-full">
                                🤰 Дозволено вагітним
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 bg-red-950/40 border border-red-800 text-red-400 text-xs font-medium px-3 py-1 rounded-full">
                                🚫 Не рекомендовано вагітним
                            </span>
                        @endif
                    @endif

                    @if (!empty($medicine['contraindication_matches']))
                        <span class="inline-flex items-center gap-1 bg-red-950/40 border border-red-800 text-red-400 text-xs font-medium px-3 py-1.5 rounded-full">
                            ⚠️ Збіг з протипоказаннями: {{ implode(', ', $medicine['contraindication_matches']) }}
                        </span>
                    @elseif (!empty($contraindications) && $analysisType === 'medicine_name')
                        <span class="inline-flex items-center gap-1 bg-green-950/40 border border-green-800 text-green-400 text-xs font-medium px-3 py-1 rounded-full">
                            ✅ Збігу з Вашими протипоказаннями немає
                        </span>
                    @endif

                    @if (!empty($medicine['country']))
                        <span class="inline-flex items-center gap-1 bg-slate-800 border border-slate-700 text-slate-300 text-xs font-medium px-3 py-1 rounded-full">
                            🏭 {{ $medicine['country'] }}
                        </span>
                    @endif

                    @if (isset($medicine['available_in_ukraine']))
                        @if ($medicine['available_in_ukraine'])
                            <span class="inline-flex items-center gap-1 bg-blue-950/40 border border-blue-800 text-blue-400 text-xs font-medium px-3 py-1 rounded-full">
                                🇺🇦 Доступний в Україні
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 bg-orange-950/40 border border-orange-800 text-orange-400 text-xs font-medium px-3 py-1 rounded-full">
                                🌍 Тільки за кордоном
                            </span>
                        @endif
                    @endif
                </div>

                @if (!empty($medicine['average_price_uah']))
                    <div class="inline-flex items-center gap-2 bg-green-950/40 border border-green-800 text-green-400 text-sm font-medium px-4 py-2 rounded-xl">
                        💰 {{ $medicine['average_price_uah'] }}
                    </div>
                @endif

                @if (!empty($medicine['active_ingredients']))
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-2">Діючі речовини</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($medicine['active_ingredients'] as $ing)
                                <span class="inline-flex items-center gap-1 bg-slate-800 border border-slate-700 text-indigo-400 text-xs font-medium px-3 py-1 rounded-full">
                                    {{ $ing['name'] }} <span class="opacity-60">{{ $ing['quantity'] }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (!empty($medicine['dosage']))
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Спосіб застосування та дози</p>
                        <p class="text-sm text-slate-300 leading-relaxed">{{ $medicine['dosage'] }}</p>
                    </div>
                @endif

                @php
                    $dbMedicine = \App\Models\Medicine::where('name', $medicine['name'])->first();
                @endphp
                @if ($dbMedicine)
                    <a href="{{ route('medicine.instructions', $dbMedicine->id) }}" target="_blank"
                       class="inline-flex items-center gap-2 text-sm text-indigo-400 hover:underline font-medium pt-2">
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
                <div class="space-y-4">
                    <h3 class="text-base font-semibold text-slate-300 mb-2 mt-6">
                        🔄 {{ $analysisType === 'medicine_name' ? 'Рекомендовані аналоги' : 'Рекомендовані препарати' }} ({{ count($recommendations) }})
                    </h3>

                    <div class="flex items-start gap-2.5 bg-amber-950/40 border border-amber-900/50 text-amber-300 text-xs rounded-xl px-4 py-3 mb-3 backdrop-blur-md">
                        <span class="shrink-0 text-base">ℹ️</span>
                        <p>
                            Препарати підібрані на основі <strong>міжнародної медичної бази</strong> AI і можуть відрізнятися від переліку на українських сайтах (наприклад, таблетки.ua).
                            Деякі препарати можуть бути недоступні в аптеках України або мати іншу торгову назву.
                            Перед застосуванням проконсультуйтеся з лікарем або фармацевтом.
                        </p>
                    </div>

                    <div class="space-y-4">
                        @foreach ($recommendations as $index => $rec)
                            @php $recDb = \App\Models\Medicine::where('name', $rec['name'])->first(); @endphp
                            <div class="bg-slate-900/60 border rounded-xl p-4 sm:p-5 space-y-3 backdrop-blur-md text-white
                                {{ $index === 0 ? 'border-indigo-500 shadow-[0_0_20px_rgba(99,102,241,0.15)]' : 'border-slate-800/80' }}">

                                <div class="flex items-center justify-between gap-3 flex-wrap">
                                    <div class="flex items-center gap-2">
                                        @if ($index === 0)
                                            <span title="Найкращий варіант для Вас" class="text-base">🏆</span>
                                        @endif
                                        <span class="font-semibold text-white text-sm">{{ $rec['name'] }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold px-2.5 py-1 rounded-full
                                            {{ $rec['smart_score'] >= 75 ? 'bg-green-950/60 text-green-400 border border-green-800' :
                                              ($rec['smart_score'] >= 50 ? 'bg-yellow-950/60 text-yellow-400 border border-yellow-800' :
                                               'bg-red-950/60 text-red-400 border border-red-800') }}">
                                            🧠 {{ $rec['smart_score'] }}%
                                        </span>
                                    </div>
                                </div>

                                @if (!empty($rec['smart_reasons']))
                                    <div class="bg-slate-800/40 border border-slate-800/60 rounded-lg px-3 py-2 space-y-1">
                                        @foreach ($rec['smart_reasons'] as $reason)
                                            <p class="text-xs flex items-start gap-1.5
                                                {{ $reason['type'] === 'positive' ? 'text-green-400' :
                                                  ($reason['type'] === 'warning'  ? 'text-red-400' : 'text-slate-400') }}">
                                                <span class="shrink-0 mt-px">
                                                    {{ $reason['type'] === 'positive' ? '✅' : ($reason['type'] === 'warning' ? '⚠️' : 'ℹ️') }}
                                                </span>
                                                {{ $reason['text'] }}
                                            </p>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="grid grid-cols-{{ $analysisType === 'medicine_name' ? '3' : '1' }} gap-2 text-center">
                                    @if ($analysisType === 'medicine_name')
                                        <div class="bg-slate-800/50 border border-slate-700/30 rounded-lg px-2 py-1.5">
                                            <p class="text-[10px] text-slate-500 leading-tight mb-0.5">🧪 Точні речовини</p>
                                            <p class="text-xs font-bold {{ $rec['match_exact'] >= 80 ? 'text-green-400' : ($rec['match_exact'] >= 40 ? 'text-yellow-400' : 'text-red-400') }}">
                                                {{ $rec['match_exact'] }}%
                                            </p>
                                        </div>

                                        <div class="bg-slate-800/50 border border-slate-700/30 rounded-lg px-2 py-1.5">
                                            <p class="text-[10px] text-slate-500 leading-tight mb-0.5">🔬 Схожі речовини</p>
                                            <p class="text-xs font-bold {{ $rec['match_fuzzy'] >= 80 ? 'text-green-400' : ($rec['match_fuzzy'] >= 40 ? 'text-yellow-400' : 'text-red-400') }}">
                                                
                                                {{-- Якщо точний збіг 100%, замість дублювання виводимо прочерк --}}
                                                {{ $rec['match_exact'] == 100 ? '—' : $rec['match_fuzzy'] . '%' }}
                                            
                                            </p>
                                        </div>
                                    @endif

                                    <div class="bg-slate-800/50 border border-slate-700/30 rounded-lg px-2 py-1.5">
                                        <p class="text-[10px] text-slate-500 leading-tight mb-0.5">🩺 Симптоми</p>
                                        <p class="text-xs font-bold {{ $rec['match_symptoms'] >= 80 ? 'text-green-400' : ($rec['match_symptoms'] >= 40 ? 'text-yellow-400' : 'text-red-400') }}">
                                            {{ $rec['match_symptoms'] }}%
                                        </p>
                                    </div>
                                </div>

                                <p class="text-xs text-slate-400">{{ $rec['symptoms'] }}</p>

                                <div class="flex flex-wrap gap-1.5">
                                    @if (!empty($rec['min_age']))
                                        @if ($age === null)
                                            <span class="inline-flex items-center gap-1 bg-slate-800 border border-slate-700 text-blue-400 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                👶 {{ $rec['min_age'] }}
                                            </span>
                                        @elseif ($rec['age_allowed'])
                                            <span class="inline-flex items-center gap-1 bg-green-950/40 border border-green-800 text-green-400 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                ✅ {{ $rec['min_age'] }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 bg-red-950/40 border border-red-800 text-red-400 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                ⛔ {{ $rec['min_age'] }} (вік не відповідає)
                                            </span>
                                        @endif
                                    @endif

                                    @if ($isPregnant && isset($rec['pregnancy_safe']))
                                        @if ($rec['pregnancy_safe'])
                                            <span class="inline-flex items-center gap-1 bg-pink-950/40 border border-pink-800 text-pink-400 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                🤰 Дозволено вагітним
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 bg-red-950/40 border border-red-800 text-red-400 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                🚫 Не рекомендовано вагітним
                                            </span>
                                        @endif
                                    @endif

                                    @if (!empty($rec['country']))
                                        <span class="inline-flex items-center gap-1 bg-slate-800 border border-slate-700 text-slate-300 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                            🏭 {{ $rec['country'] }}
                                        </span>
                                    @endif

                                    @if (isset($rec['available_in_ukraine']))
                                        @if ($rec['available_in_ukraine'])
                                            <span class="inline-flex items-center gap-1 bg-blue-950/40 border border-blue-800 text-blue-400 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                🇺🇦 Доступний в Україні
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 bg-orange-950/40 border border-orange-800 text-orange-400 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                🌍 Тільки за кордоном
                                            </span>
                                        @endif
                                    @endif
                                </div>

                                @if (!empty($rec['average_price_uah']))
                                    <p class="text-xs font-medium text-green-400">
                                        💰 {{ $rec['average_price_uah'] }}
                                    </p>
                                @endif

                                @if (!empty($rec['active_ingredients']))
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach ($rec['active_ingredients'] as $ing)
                                            <span class="inline-flex items-center gap-1 bg-slate-800 border border-slate-700 text-slate-300 text-xs px-2.5 py-0.5 rounded-full">
                                                {{ $ing['name'] }} <span class="opacity-50">{{ $ing['quantity'] }}</span>
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                @if (!empty($rec['dosage']))
                                    <div class="bg-slate-800/40 border border-slate-800/60 rounded-lg px-3 py-2">
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Спосіб застосування та дози</p>
                                        <p class="text-xs text-slate-300 leading-relaxed">{{ $rec['dosage'] }}</p>
                                    </div>
                                @endif

                                @if ($recDb)
                                    <a href="{{ route('medicine.instructions', $recDb->id) }}" target="_blank"
                                       class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:underline pt-1">
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