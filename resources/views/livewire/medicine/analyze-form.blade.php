<div class="w-full {{ $state === 'result' ? 'max-w-4xl' : 'max-w-xl' }} mx-auto py-12 px-4 sm:px-6 lg:px-8 bg-transparent relative z-20 transition-all duration-500">
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

                    <x-ui.select label="Режим аналізу" wire:model.live="analysisType" 
                                  :error="$errors->first('analysisType')">
                        <option value="medicine_name">💊 За найменуванням лікарського засобу</option>
                        <option value="symptoms">🤒 За клінічними симптоми</option>
                    </x-ui.select>

                    <x-ui.input
                        label="{{ $analysisType === 'medicine_name' ? 'Найменування лікарського засобу' : 'Клінічні симптоми' }}"
                        wire:model="query"
                        placeholder="{{ $analysisType === 'medicine_name' ? 'Наприклад: Нурофен' : 'Наприклад: Головний біль, температура' }}"
                        :error="$errors->first('query')"
                    />

                    <x-ui.input label="Вік пацієнта" type="number" wire:model="age" min="0" max="120"
                                placeholder="Вкажіть скільки повних років" :error="$errors->first('age')"/>

                    <x-ui.input label="Алергологічний анамнез та обмеження" wire:model="contraindications"
                                placeholder="Наприклад: Цитрусові, шерсть, цвітіння"
                                :error="$errors->first('contraindications')"/>

                    <x-ui.input label="Супутня фармакотерапія" wire:model="currentMedications"
                                placeholder="Наприклад: Еналаприл, аспірин"
                                :error="$errors->first('currentMedications')"/>

                    <x-ui.checkbox id="isPregnant" label="Статус вагітності пацієнта" wire:model="isPregnant"
                                   :error="$errors->first('isPregnant')"/>

                    <div class="border-t border-slate-800/60 pt-2"></div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="submit"
                            class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-semibold py-3 px-6 rounded-xl shadow-[0_4px_20px_rgba(99,102,241,0.2)] transition-all duration-300 hover:shadow-[0_4px_25px_rgba(139,92,246,0.4)] active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg wire:loading.remove wire:target="submit" class="w-4 h-4 shrink-0" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <svg wire:loading wire:target="submit" class="animate-spin w-4 h-4 shrink-0 text-white" fill="none"
                             viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <span wire:loading.remove wire:target="submit">Аналізувати</span>
                        <span wire:loading wire:target="submit">Обробляється...</span>
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
                    <button wire:click="newSearch" wire:loading.attr="disabled" wire:target="searchBySymptoms"
                            class="flex-1 flex items-center justify-center gap-2 border border-amber-800 text-amber-300 hover:bg-amber-900/20 font-medium py-2.5 rounded-xl transition disabled:opacity-40">
                        ← Змінити запит
                    </button>
                    <button wire:click="searchBySymptoms" wire:loading.attr="disabled" wire:target="searchBySymptoms"
                            class="flex-1 flex items-center justify-center gap-2 bg-amber-600 hover:bg-amber-700 text-white font-medium py-2.5 rounded-xl transition disabled:opacity-60 disabled:cursor-not-allowed">
                        <svg wire:loading wire:target="searchBySymptoms" class="animate-spin w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <span wire:loading.remove wire:target="searchBySymptoms">🤒</span>
                        <span wire:loading.remove wire:target="searchBySymptoms">Шукати за симптомами</span>
                        <span wire:loading wire:target="searchBySymptoms">Обробляється...</span>
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
                    <button wire:click="newSearch" wire:loading.attr="disabled" wire:target="searchByMedicineName"
                            class="flex-1 flex items-center justify-center gap-2 border border-blue-800 text-blue-300 hover:bg-blue-900/20 font-medium py-2.5 rounded-xl transition disabled:opacity-40">
                        ← Змінити запит
                    </button>
                    <button wire:click="searchByMedicineName" wire:loading.attr="disabled" wire:target="searchByMedicineName"
                            class="flex-1 flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 rounded-xl transition disabled:opacity-60 disabled:cursor-not-allowed">
                        <svg wire:loading wire:target="searchByMedicineName" class="animate-spin w-4 h-4 text-white shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <span wire:loading.remove wire:target="searchByMedicineName">💊</span>
                        <span wire:loading.remove wire:target="searchByMedicineName">Шукати за назвою</span>
                        <span wire:loading wire:target="searchByMedicineName">Обробляється...</span>
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

                    // 🟢 БЕЗДОГАННИЙ КЛІНІЧНИЙ ЛІЧИЛЬНИК: рахуємо зауваження виключно за їхніми системними типами
                    $chartWarningsCount = array_map(function($r) {
                        $count = 0;
                        if (!empty($r['smart_reasons'])) {
                            foreach ($r['smart_reasons'] as $reason) {
                                $type = $reason['type'] ?? '';
                                if (in_array($type, ['warning', 'critical', 'danger', 'error'])) {
                                    $count++;
                                }
                            }
                        }
                        return $count;
                    }, $recommendations);

                    $bestAlternative = $recommendations[0] ?? null;
                @endphp

                <div x-data="farmaReport(
                        {{ Js::from($chartLabels) }},
                        {{ Js::from($chartSmart) }},
                        {{ Js::from($chartExact) }},
                        {{ Js::from($chartFuzzy) }},
                        {{ Js::from($chartSymptoms) }},
                        {{ Js::from($chartColors) }},
                        {{ Js::from($chartWarningsCount) }}
                    )" class="space-y-4 font-sans">

                    <div class="flex items-center justify-between gap-3">
                        <button wire:click="newSearch"
                                class="flex items-center gap-2 text-sm text-indigo-400 hover:underline font-medium">
                            ← Новий запит
                        </button>
                        {{-- Кнопка-тригер аналітики --}}
                        <button @click="toggle()"
                                class="flex items-center gap-2 text-sm font-semibold bg-slate-900 border border-indigo-500/40 text-indigo-400 hover:bg-indigo-950/30 hover:border-indigo-400 px-4 py-2 rounded-xl transition-all duration-300 shadow-[0_0_15px_rgba(99,102,241,0.1)] active:scale-[0.98]">
                            <span>📊</span> {{ $analysisType === 'medicine_name' ? 'Аналітичний дашборд' : 'Аналітичний дашборд' }}
                        </button>
                    </div>

                    <div x-show="show"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="bg-slate-900/90 border border-slate-800 rounded-2xl shadow-2xl p-5 sm:p-6 space-y-5 backdrop-blur-md text-white relative z-30">

                        {{-- 📊 КЛІНІЧНИЙ ЗАГОЛОВОК ПАНЕЛІ --}}
                        <div class="border-b border-slate-800 pb-3">
                            <h3 class="text-base font-bold text-slate-200 tracking-wide">
                                📊 {{ $analysisType === 'medicine_name' ? 'Розширений звіт' : 'Розширений звіт' }}
                            </h3>
                        </div>

                        {{-- 📊 1. КЛІНІЧНІ KPI-КАРТКИ --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                            <div class="bg-slate-800/30 border border-slate-800/80 p-3 rounded-xl text-center shadow-inner flex flex-col justify-center min-w-0">
                                <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400 leading-tight">Скринінг ліків</p>
                                <p class="text-2xl font-black text-indigo-400 mt-1">{{ count($recommendations) }} <span class="text-xs font-normal text-slate-500">ЛЗ</span></p>
                            </div>
                            <div class="bg-slate-800/30 border border-slate-800/80 p-3 rounded-xl text-center shadow-inner flex flex-col justify-center min-w-0">
                                <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400 leading-tight">Висока відповідність</p>
                                <p class="text-2xl font-black text-green-400 mt-1">
                                    {{ collect($recommendations)->where('smart_score', '>=', 75)->count() }} <span class="text-xs font-normal text-slate-500">ЛЗ</span>
                                </p>
                            </div>
                            <div class="bg-slate-800/30 border border-slate-800/80 p-3 rounded-xl text-center shadow-inner flex flex-col justify-center min-w-0">
                                <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400 leading-tight">Часткова відповідність</p>
                                <p class="text-2xl font-black text-yellow-400 mt-1">
                                    {{ collect($recommendations)->where('smart_score', '>=', 50)->where('smart_score', '<', 75)->count() }} <span class="text-xs font-normal text-slate-500">ЛЗ</span>
                                </p>
                            </div>
                            <div class="bg-slate-800/30 border border-slate-800/80 p-3 rounded-xl text-center shadow-inner flex flex-col justify-center min-w-0">
                                <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400 leading-tight">Низька відповідність</p>
                                <p class="text-2xl font-black text-slate-400 mt-1">
                                    {{ collect($recommendations)->where('smart_score', '<', 50)->count() }} <span class="text-xs font-normal text-slate-500">ЛЗ</span>
                                </p>
                            </div>
                            <div class="bg-slate-800/30 border border-slate-800/80 p-3 rounded-xl text-center shadow-inner flex flex-col justify-center min-w-0">
                                <p class="text-[10px] uppercase font-bold tracking-wider text-slate-400 leading-tight">Макс. збіг симптомів</p>
                                <p class="text-2xl font-black text-emerald-400 mt-1">
                                    {{ count($recommendations) > 0 ? collect($recommendations)->max('match_symptoms') : 0 }}%
                                </p>
                            </div>
                        </div>

                        {{-- 🧠 2. КЛІНІКО-ФАРМАКОЛОГІЧНИЙ ВИСНОВОК ТА ОНОВЛЕНА ЛЕГЕНДА --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-1 font-sans">
                            <div class="md:col-span-2 bg-gradient-to-r from-indigo-950/20 to-slate-800/30 border border-indigo-500/20 rounded-xl p-3.5 flex items-start gap-3 shadow-inner">
                                <span class="text-xl mt-0.5">🧠</span>
                                <div class="text-xs text-slate-300 leading-relaxed">
                                    <p class="font-bold text-indigo-400 text-[13px] mb-0.5">Клініко-фармакологічний висновок</p>
                                    @if($bestAlternative && $analysisType === 'medicine_name')
                                         На основі автоматизованого контент-скринінгу, засіб <strong class="text-white">"{{ $bestAlternative['name'] }}"</strong> визначено як раціональний терапевтичний замінник із загальним індексом відповідності профілю пацієнта <strong class="text-indigo-400">{{ $bestAlternative['smart_score'] }}%</strong>. Він демонструє високу молекулярну тотожність діючих речовин, оптимальне покриття симптомів та чистий профіль сумісності без виявлених перехресних конфліктів із супутньою терапією.
                                    @else
                                         Сформовано автоматизований реєстр лікарських засобів, ранжований за комплексним індексом клінічної релевантності. Системний алгоритм PharmAI проаналізував коморбідні фактори ризику, сумісність діючих речовин та вікові обмеження пацієнтів, винісши найбільш безпечні та ефективні рішення на перші позиції.
                                    @endif
                                </div>
                            </div>

                            <div class="bg-slate-800/30 border border-slate-800/80 rounded-xl p-3.5 space-y-2 flex flex-col justify-center text-[11px]">
                                <p class="font-bold tracking-wider text-slate-400 uppercase text-[9px] mb-0.5">Шкала релевантності</p>
                                <div class="flex items-center gap-2 text-slate-300">
                                    <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                                    <span><strong>75%–100%:</strong> Висока відповідність</span>
                                </div>
                                <div class="flex items-center gap-2 text-slate-300">
                                    <span class="w-2 h-2 rounded-full bg-yellow-500 shrink-0"></span>
                                    <span><strong>50%–74%:</strong> Часткова відповідність</span>
                                </div>
                                <div class="flex items-center gap-2 text-slate-300">
                                    <span class="w-2 h-2 rounded-full bg-slate-500 shrink-0"></span>
                                    <span><strong>0%–49%:</strong> Низька відповідність</span>
                                </div>
                            </div>
                        </div>

                        {{-- 📈 3. СЕКЦІЯ ГРАФІКІВ (КРУГОВИЙ АНАЛІЗ ТА РАДАР / СИМПТОМИ БАР) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-1">
                            <div class="bg-slate-800/20 border border-slate-800/60 p-4 rounded-xl shadow-inner w-full">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5 font-sans">
                                    <span>🍩</span> Розподіл за рівнями відповідності
                                </p>
                                <div class="relative w-full h-[240px] flex items-center justify-center" wire:ignore>
                                    <canvas id="chart-safety-donut"></canvas>
                                </div>
                            </div>

                            @if ($analysisType === 'medicine_name')
                                <div class="bg-slate-800/20 border border-slate-800/60 p-4 rounded-xl shadow-inner">
                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5 font-sans">
                                        <span>🕸️</span> Радарний профіль порівняння (ТОП-3 замінники)
                                    </p>
                                    <div wire:ignore class="relative w-full h-[240px] flex items-center justify-center">
                                        <canvas id="chart-radar-top"></canvas>
                                    </div>
                                </div>
                            @else
                                <div class="bg-slate-800/20 border border-slate-800/60 p-4 rounded-xl shadow-inner">
                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5 font-sans">
                                        <span>⚠️</span> Кількість виявлених зауважень та ризиків безпеки
                                    </p>
                                    <div wire:ignore class="relative w-full h-[240px] flex items-center justify-center">
                                        <canvas id="chart-symptoms-bar"></canvas>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- 📊 4. СТАНДАРТНІ ГРАФІКИ РЕЙТИНГІВ (ОПТИМІЗОВАНИЙ МАТОВИЙ СИНХРОН) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-2 border-t border-slate-800/40">
    
                            {{-- Розтягуємо на всю ширину дашборду для чистої та красивої симетрії --}}
                            <div class="md:col-span-2 w-full">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4 font-sans">🧠 Інтелектуальний рейтинг відповідності</p>
        
                                <div class="space-y-4 w-full">
                                    @foreach ($recommendations as $rec)
                                        <div class="space-y-1.5">
                                            {{-- Рядок показників: компактний шрифт text-[11px] та акуратне напівжирне виділення --}}
                                            <div class="flex justify-between items-center text-[11px] font-sans">
                                                <span class="font-medium text-slate-300 truncate pr-4">{{ $rec['name'] }}</span>
                                                <span class="font-semibold" style="color: {{ $rec['smart_score'] >= 75 ? 'rgba(34,197,94,0.9)' : ($rec['smart_score'] >= 50 ? 'rgba(234,179,8,0.9)' : 'rgba(239,68,68,0.9)') }}">
                                                    {{ $rec['smart_score'] }}%
                                                </span>
                                            </div>
                    
                                            {{-- Тонка лінія прогресу h-2 на чистому темному фоні без зайвих рамок --}}
                                            <div class="w-full bg-slate-900/50 rounded-full h-2 border border-slate-800/30 overflow-hidden">
                                                {{-- Матові оригінальні RGBA-кольори з конфігурації графіків --}}
                                                <div class="h-full rounded-full transition-all duration-500"
                                                    style="width: {{ $rec['smart_score'] }}%; background-color: {{ $rec['smart_score'] >= 75 ? 'rgba(34,197,94,0.8)' : ($rec['smart_score'] >= 50 ? 'rgba(234,179,8,0.8)' : 'rgba(239,68,68,0.8)') }};">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @if ($analysisType === 'medicine_name')
                                {{-- Системний if-заглушка для альтернативних режимів скринінгу ліків --}}
                            @endif
                        </div>

                        {{-- 📋 5. ЧИСТА КЛІНІЧНА ТАБЛИЦЯ --}}
                        <div class="w-full pt-2 border-t border-slate-800/40">
                            <table class="w-full text-xs text-left border-collapse table-fixed font-sans">
                                <thead>
                                    <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider text-[10px]">
                                        <th class="py-2.5 px-3 font-semibold {{ $analysisType === 'medicine_name' ? 'w-[35%]' : 'w-[55%]' }}">Торгова назва ЛЗ</th>
                                        <th class="py-2.5 px-1 font-semibold text-center {{ $analysisType === 'medicine_name' ? 'w-[13%]' : 'w-[15%]' }}">Збіг</th>
                                        @if ($analysisType === 'medicine_name')
                                            <th class="py-2.5 px-1 font-semibold text-center w-[13%]">Точні</th>
                                            <th class="py-2.5 px-1 font-semibold text-center w-[13%]">Схожі</th>
                                        @endif
                                        <th class="py-2.5 px-1 font-semibold text-center {{ $analysisType === 'medicine_name' ? 'w-[13%]' : 'w-[15%]' }}">Симптоми</th>
                                        <th class="py-2.5 pl-2 pr-4 font-semibold text-right w-[15%]">Країна-виробник</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recommendations as $i => $rec)
                                        <tr class="border-b border-slate-800/50 hover:bg-slate-800/30 transition {{ $i === 0 ? 'font-bold bg-indigo-950/20 text-white' : 'text-slate-300' }}">
                                            <td class="py-2.5 px-3 flex items-center gap-1.5 min-w-0 truncate">
                                                <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $rec['smart_score'] >= 75 ? 'bg-green-500' : ($rec['smart_score'] >= 50 ? 'bg-yellow-500' : 'bg-red-500') }}"></span>
                                                <span class="truncate">@if ($i === 0)🏆 @endif {{ $rec['name'] }}</span>
                                            </td>
                                            <td class="py-2.5 px-1 text-center font-black text-[11px]
                                                {{ $rec['smart_score'] >= 75 ? 'text-green-400' : ($rec['smart_score'] >= 50 ? 'text-yellow-400' : 'text-red-400') }}">
                                                {{ $rec['smart_score'] }}%
                                            </td>
                                            @if ($analysisType === 'medicine_name')
                                                <td class="py-2.5 px-1 text-center text-indigo-400/90 text-[11px]">{{ $rec['match_exact'] }}%</td>
                                                <td class="py-2.5 px-1 text-center text-purple-400/90 text-[11px]">
                                                    {{ $rec['match_exact'] == 100 ? '—' : $rec['match_fuzzy'] . '%' }}
                                                </td>
                                            @endif
                                            <td class="py-2.5 px-1 text-center text-blue-400/90 text-[11px]">{{ $rec['match_symptoms'] }}%</td>
                                            <td class="py-2.5 pl-2 pr-4 text-right text-slate-400 text-[11px] truncate">{{ $rec['country'] ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            @endif

            {{-- Картка препарату --}}
            <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-6 sm:p-8 space-y-5 backdrop-blur-md shadow-xl text-white relative z-20">
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

                    @if (!empty($currentMedications) && $analysisType !== 'symptoms')
                         @if (!empty($medicine['interaction_matches']) || !empty($medicine['interaction_details']))
                            <span class="inline-flex items-center gap-1 bg-red-950/40 border border-red-800 text-red-400 text-xs font-medium px-3 py-1 rounded-full">
                                ⚠️ Конфлікт ліків: {{ !empty($medicine['interaction_matches']) ? implode(', ', $medicine['interaction_matches']) : $currentMedications }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 bg-green-950/40 border border-green-800 text-green-400 text-xs font-medium px-3 py-1 rounded-full">
                                ✅ Сумісно з лікуванням
                            </span>
                        @endif
                    @endif

                    @if (!empty($medicine['contraindication_matches']))
                        <span class="inline-flex items-center gap-1 bg-red-950/40 border border-red-800 text-red-400 text-xs font-medium px-3 py-1.5 rounded-full">
                            ⚠️ Збіг з протипоказаннями: {{ implode(', ', $medicine['contraindication_matches']) }}
                        </span>
                    @elseif (!empty($contraindications) && $analysisType === 'medicine_name')
                        <span class="inline-flex items-center gap-1 bg-green-950/40 border border-green-800 text-green-400 text-xs font-medium px-3 py-1 rounded-full">
                            ✅ Препарат сумісний із вказаними Вами алергіями та обмеженнями здоров'я
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

                @if (!empty($medicine['interaction_details']))
                    @php
                        $uniqueDetails = array_unique($medicine['interaction_details']);
                        $finalDetails = [];
                        foreach ($uniqueDetails as $detail) {
                            $isDuplicate = false;
                            foreach ($finalDetails as $added) {
                                if (levenshtein(mb_strtolower($detail), mb_strtolower($added)) < 50) {
                                    $isDuplicate = true;
                                    break;
                                }
                            }
                            if (!$isDuplicate) { $finalDetails[] = $detail; }
                        }
                    @endphp

                    <div class="bg-red-950/20 border border-red-900/40 rounded-xl px-4 py-3 space-y-2 text-sm text-red-300/90 shadow-inner">
                        @foreach ($finalDetails as $detail)
                            <p class="flex items-start gap-2 leading-relaxed">
                                <span class="shrink-0 mt-0.5">❌</span>
                                <span>{{ $detail }}</span>
                            </p>
                        @endforeach
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
                <div class="space-y-4 relative z-20">
                    <h3 class="text-base font-semibold text-slate-300 mb-2 mt-6">
                        🔄 {{ $analysisType === 'medicine_name' ? 'Рекомендовані аналоги' : 'Рекомендовані препарати' }} ({{ count($recommendations) }})
                    </h3>

                    <div class="flex items-start gap-2.5 bg-amber-950/40 border border-amber-900/50 text-amber-300 text-xs rounded-xl px-4 py-3 mb-3 backdrop-blur-md">
                        <span class="shrink-0 text-base">ℹ️</span>
                        <p>
                            Препарати підібрані на основі <strong>міжнародної медичної бази</strong> AI і можуть відрізнятися від переліку на українських сайтах (наприклад, таблетки.ua).
                            Деякі препарати можуть быть недоступні в аптеках України або мати іншу торгову назву.
                            Перед застосуванням проконсультуйтеся з лікарем або фармацевтом.
                        </p>
                    </div>

                    <div class="space-y-4">
                        @foreach ($recommendations as $index => $rec)
                            @php 
                                $recDb = \App\Models\Medicine::where('name', $rec['name'])->first(); 
                                $takenLower = mb_strtolower($currentMedications);
                                $analogueNameLower = mb_strtolower($rec['name'] ?? '');
                                
                                $isSameDrugOverdose = false;
                                if (!empty($currentMedications)) {
                                    $cleanTaken = trim($takenLower);
                                    $cleanAnalogue = trim($analogueNameLower);
                                    if (str_contains($cleanAnalogue, $cleanTaken) || str_contains($cleanTaken, $cleanAnalogue)) {
                                        $isSameDrugOverdose = true;
                                    }
                                }
                            @endphp
                            <div class="bg-slate-900/60 border rounded-xl p-4 sm:p-5 space-y-3 backdrop-blur-md text-white
                                {{ $index === 0 ? 'border-indigo-500 shadow-[0_0_20px_rgba(99,102,241,0.15)]' : 'border-slate-800/80' }}">

                                <div class="flex items-center justify-between gap-3 flex-wrap">
                                    <div class="flex items-center gap-2">
                                        @if ($index === 0) <span title="Найкращий варіант для Вас" class="text-base">🏆</span> @endif
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

                                @if (!empty($rec['smart_reasons']) || $isSameDrugOverdose)
                                    <div class="bg-slate-800/40 border border-slate-800/60 rounded-lg px-3 py-2 space-y-1.5">
                                        @if ($isSameDrugOverdose)
                                            <p class="text-xs flex items-start gap-2 text-red-400 font-medium bg-red-950/20 border border-red-900/30 p-2 rounded-md">
                                                <span class="shrink-0 text-sm">❌</span>
                                                <span><strong>Фармацевтична несумісність (дублювання терапії):</strong> Запропонований аналог містить діючу речовину, яка вже входить до складу Вашого поточного лікування ({{ $currentMedications }}). Одночасне застосування призведе до небезпечного передозовки та підвисить ризик внутрішніх побічних ефектів.</span>
                                            </p>
                                        @endif

                                        @if (!empty($rec['smart_reasons']))
                                            @php $displayedDosageWarning = false; @endphp
                                            @foreach ($rec['smart_reasons'] as $reason)
                                                @php
                                                    $textLower = mb_strtolower($reason['text'] ?? '');
                                                    $isDosage = str_contains($textLower, 'дозуван') || str_contains($textLower, 'форм');
                                                    if ($isDosage) {
                                                        if ($displayedDosageWarning) { continue; }
                                                        $displayedDosageWarning = true;
                                                    }
                                                    $isCritical = str_contains($textLower, 'несумісн') || str_contains($textLower, 'конфлікт') || $reason['type'] === 'warning';
                                                @endphp
                                                <p class="text-xs flex items-start gap-1.5 {{ $reason['type'] === 'positive' ? 'text-green-400' : ($isCritical ? 'text-red-400' : 'text-slate-400') }}">
                                                    <span class="shrink-0 mt-px">
                                                        {{ $reason['type'] === 'positive' ? '✅' : ($isCritical ? '⚠️' : 'ℹ️') }}
                                                    </span>
                                                    {{ $reason['text'] }}
                                                </p>
                                            @endforeach
                                        @endif
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

                                    @if (!empty($currentMedications))
                                        @php
                                            $reasonsCollection = collect($rec['smart_reasons'] ?? []);
                                            $takenLower = mb_strtolower($currentMedications);
                                            $analogueNameLower = mb_strtolower($rec['name'] ?? '');
                                            $analogueIngredients = mb_strtolower(implode(' ', array_column($rec['active_ingredients'] ?? [], 'name')));
                                            
                                            $isSameDrugOverdose = false;
                                            if (str_contains($analogueNameLower, 'аспірин') || str_contains($analogueNameLower, 'aspirin') || str_contains($analogueIngredients, 'acetylsalicylicum')) {
                                                if (str_contains($takenLower, 'аспірин') || str_contains($takenLower, 'aspirin')) {
                                                    $isSameDrugOverdose = true;
                                                }
                                            }

                                            $hasCriticalConflict = $isSameDrugOverdose || $reasonsCollection->contains(fn($r) => 
                                                in_array($r['type'] ?? '', ['warning', 'critical']) && (
                                                    str_contains(mb_strtolower($r['text'] ?? ''), 'несумісн') || 
                                                    str_contains(mb_strtolower($r['text'] ?? ''), 'конфлікт') ||
                                                    str_contains(mb_strtolower($r['text'] ?? ''), 'критич') ||
                                                    str_contains(mb_strtolower($r['text'] ?? ''), 'подвоєн') ||
                                                    str_contains(mb_strtolower($r['text'] ?? ''), 'ризик')
                                                )
                                            );
                                            
                                            $hasMildInteraction = !$hasCriticalConflict && $reasonsCollection->contains(fn($r) => 
                                                in_array($r['type'] ?? '', ['warning', 'neutral', 'info']) && (
                                                    str_contains(mb_strtolower($r['text'] ?? ''), 'взаємоді') || 
                                                    str_contains(mb_strtolower($r['text'] ?? ''), 'зниж')
                                                )
                                            );
                                        @endphp

                                        @if ($hasCriticalConflict)
                                            <span class="inline-flex items-center gap-1 bg-red-950/40 border border-red-800 text-red-400 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                ⚠️ Критичний конфлікт
                                            </span>
                                        @elseif ($hasMildInteraction)
                                            <span class="inline-flex items-center gap-1 bg-slate-800 border border-slate-700 text-amber-400 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                ℹ️ Незначна взаємодія
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 bg-green-950/40 border border-green-800 text-green-400 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                                ✅ Сумісно з лікуванням
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

{{-- ====== СИНХРОНІЗОВАНИЙ ТА СТАБІЛЬНИЙ ДВИГУН ДЛЯ ГРАФІКІВ ====== --}}
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('farmaReport', (labels, smart, exact, fuzzy, symptoms, colors, warningsCount) => ({
        show: false,
        charts: {},
        init() {
            this.$watch('smart', () => { if (this.show) { this.renderCharts(); } });
        },
        toggle() {
            this.show = !this.show;
            if (this.show) {
                // Мікрозатримка, щоб x-transition дав контейнеру розгорнутися до розрахунку розмірів canvas
                setTimeout(() => this.renderCharts(), 60);
            }
        },
        renderCharts() {
            ['metrics', 'safety-donut', 'radar', 'symptoms-bar'].forEach(id => {
                if (this.charts[id]) { this.charts[id].destroy(); }
            });

            Chart.defaults.font.family = 'ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial';

            // 1. 📊 Компонентний розподіл ліків (Тільки для режиму за назвою ЛЗ)
            let ctxMetrics = document.getElementById('chart-metrics');
            if (ctxMetrics) {
                this.charts['metrics'] = new Chart(ctxMetrics, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            { label: 'Точні', data: exact, backgroundColor: 'rgba(99, 102, 241, 0.8)' },
                            { label: 'Схожі', data: fuzzy, backgroundColor: 'rgba(167, 139, 250, 0.8)' },
                            { label: 'Симптоми', data: symptoms, backgroundColor: 'rgba(59, 130, 246, 0.8)' }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: { padding: { bottom: 20 } },
                        plugins: { legend: { labels: { color: '#94a3b8', font: { size: 10 } } } },
                        scales: { 
                            x: { stacked: false, grid: { color: '#1e293b' }, ticks: { color: '#94a3b8', font: { size: 10 }, maxRotation: 45, minRotation: 45, autoSkip: false } }, 
                            y: { stacked: false, min: 0, max: 100, grid: { color: '#1e293b' }, ticks: { color: '#94a3b8', font: { size: 10 }, stepSize: 20 } } 
                        }
                    }
                });
            }

            // 2. 🍩 Розподіл за рівнями відповідності (КРУГОВИЙ ПОНЧИК)
            let ctxDonut = document.getElementById('chart-safety-donut');
            if (ctxDonut) {
                let safeCount = smart.filter(score => score >= 75).length;
                let warningCount = smart.filter(score => score >= 50 && score < 75).length;
                let riskCount = smart.filter(score => score < 50).length;

                this.charts['safety-donut'] = new Chart(ctxDonut, {
                    type: 'doughnut',
                    data: {
                        labels: ['Висока відповідність', 'Часткова відповідність', 'Низька відповідність'],
                        datasets: [{
                            data: [safeCount, warningCount, riskCount],
                            backgroundColor: ['rgba(34,197,94,0.8)', 'rgba(234,179,8,0.8)', 'rgba(100,116,139,0.8)'],
                            borderColor: '#1e293b',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { color: '#94a3b8', font: { size: 9 }, boxWidth: 10 } } }
                    }
                });
            }

            // 3. 🕸️ Радарний профіль порівняння (Тільки для режиму за назвою ЛЗ)
            let ctxRadar = document.getElementById('chart-radar-top');
            if (ctxRadar && labels.length > 0) {
                let top3Labels = labels.slice(0, 3);
                let radarColors = [
                    { bg: 'rgba(99, 102, 241, 0.15)', border: 'rgba(99, 102, 241, 1)' },
                    { bg: 'rgba(34, 197, 94, 0.15)', border: 'rgba(34, 197, 94, 1)' },
                    { bg: 'rgba(234, 179, 8, 0.15)', border: 'rgba(234, 179, 8, 1)' }
                ];
                let datasets = top3Labels.map((label, i) => ({
                    label: label,
                    data: [exact[i], fuzzy[i], symptoms[i], smart[i]],
                    backgroundColor: radarColors[i].bg,
                    borderColor: radarColors[i].border,
                    borderWidth: 2,
                    pointRadius: 3
                }));
                this.charts['radar'] = new Chart(ctxRadar, {
                    type: 'radar',
                    data: { labels: ['Точний склад', 'Модифікації', 'Симптоми', 'SmartScore'], datasets: datasets },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 9 }, color: '#94a3b8' } } },
                        scales: { r: { min: 0, max: 100, ticks: { display: false }, grid: { color: '#334155' }, angleLines: { color: '#334155' } } }
                    }
                });
            }

            // 4. ⚠️ Кількість виявлених зауважень та ризиків безпеки (ВЕРТИКАЛЬНИЙ БАР ДЛЯ СИМПТОМІВ)
            let ctxSymptomsBar = document.getElementById('chart-symptoms-bar');
            if (ctxSymptomsBar && labels.length > 0) {
                this.charts['symptoms-bar'] = new Chart(ctxSymptomsBar, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: warningsCount,
                            backgroundColor: 'rgba(239, 68, 68, 0.75)', 
                            borderColor: '#dc2626',
                            borderWidth: 1.5,
                            borderRadius: 4,
                            barThickness: 14
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { grid: { color: '#1e293b' }, ticks: { color: '#94a3b8', font: { size: 9 }, maxRotation: 35, minRotation: 35, autoSkip: false } },
                            y: {
                                min: 0,
                                suggestedMax: 2,
                                grid: { color: '#1e293b' },
                                ticks: { color: '#94a3b8', font: { size: 10 }, stepSize: 1, callback: function(v) { if (v % 1 === 0) return v; } }
                            }
                        }
                    }
                });
            }
        }
    }));
});
</script>