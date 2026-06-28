<?php

namespace App\Services;

class MedicineAnalyzeService
{
    /**
     * Вікно допустимого відхилення дозування: ±20% від оригіналу.
     */
    private const QUANTITY_WINDOW = 0.20;

    /**
     * Мінімальна довжина слова для порівняння симптомів.
     */
    private const MIN_SYMPTOM_WORD_LENGTH = 4;

    // -------------------------------------------------------------------------
    // Ваги для формули SmartScore (Weighted Scoring Model)
    // -------------------------------------------------------------------------

    /** Вага хімічного збігу (жорсткий критерій) — пріоритет */
    private const WEIGHT_CHEMICAL = 0.7;

    /** Вага симптоматичного збігу (м'який критерій) */
    private const WEIGHT_SYMPTOMS = 0.3;

    // -------------------------------------------------------------------------
    // Штрафи для коригування SmartScore (обмеження пацієнта)
    // -------------------------------------------------------------------------

    /** Вік пацієнта не відповідає мінімальному — сильний штраф */
    private const PENALTY_AGE_NOT_ALLOWED = 50;

    /** Препарат заборонений вагітним, а пацієнтка вагітна — сильний штраф */
    private const PENALTY_PREGNANCY_UNSAFE = 40;

    /** Кожен збіг з протипоказаннями пацієнта — штраф за один збіг */
    private const PENALTY_PER_CONTRAINDICATION = 15;

    /** Штраф за помірну лікарську взаємодію */
    private const PENALTY_INTERACTION_MODERATE = 25;

    /** Штраф за тяжку лікарську взаємодію (протипоказано) */
    private const PENALTY_INTERACTION_SEVERE = 60;

    /** ШТРАФ ЗА КРИТИЧНЕ ДУБЛЮВАННЯ ТЕРАПІЇ ТА ПЕРЕДОЗУВАННЯ ВЗАЄМОУКЛЮЧНИХ ЛЗ */
    private const PENALTY_THERAPY_DUPLICATION = 65;

    // =========================================================================
    // ПУБЛІЧНИЙ API
    // =========================================================================

    /**
     * Аналізує рекомендації відносно вихідного препарату з урахуванням контексту пацієнта.
     *
     * Кожен аналог отримує:
     *   match_exact    — збіг за точними назвами речовин + дозування ±20%
     *   match_fuzzy    — збіг за схожими назвами речовин + дозування ±20%
     *   match_symptoms — збіг за симптомами/показаннями (Jaccard)
     *   match_percent  — максимум з трьох (хімічна/симптоматична схожість)
     *   age_allowed    — чи підходить вік пацієнта
     *   contraindication_matches — список збігів з протипоказаннями пацієнта
     *   smart_score    — фінальний бал з урахуванням ВСІХ обмежень (0–100)
     *   smart_reasons  — масив пояснень {'type': 'positive'|'warning', 'text': '...'}
     *
     * @param  array<int, array<string, mixed>> $recommendations
     * @param  array<string, mixed>             $initialMedicine
     * @param  array{age: ?int, is_pregnant: bool, contraindications: string} $patientContext
     * @return array<int, array<string, mixed>>
     */
    public function analyze(array $recommendations, array $initialMedicine, array $patientContext = [], string $analysisType = 'medicine_name'): array
    {
        $initialIngredients = $this->normalizeIngredients($initialMedicine['active_ingredients'] ?? []);
        $initialSymptoms    = $this->normalizeSymptoms($initialMedicine['symptoms'] ?? '');

        $result = [];

        foreach ($recommendations as $recommendation) {
            // Крос-мовна фільтрація брендів
            $searchedBrand = $this->normalizeBrandName($initialMedicine['name'] ?? '');
            $recommendedBrand = $this->normalizeBrandName($recommendation['name'] ?? '');

            // 2. Якщо це той самий бренд — миттєво пропускаємо його
            if ($searchedBrand === $recommendedBrand) {
            continue;
            }
            
            $candidateIngredients = $this->normalizeIngredients($recommendation['active_ingredients'] ?? []);
            $candidateSymptoms    = $this->normalizeSymptoms($recommendation['symptoms'] ?? '');

            // Визначаємо, чи є асиметрія в дозах для точних речовин
            $dosageMismatch = $this->hasDosageMismatch($initialIngredients, $candidateIngredients);

            // --- Три метрики хімічно-симптоматичної схожості ---

            // 1. Точний збіг: назва речовини ідентична + дозування ±20%
            // Якщо немає вихідних речовин — немає що порівнювати
            if (empty($initialIngredients)) {
                $matchExact = 0.0;
            } else {
                $matchExact = $this->calculateExactMatchPercent($initialIngredients, $candidateIngredients);
            }

            // 2. Нечіткий збіг: одна назва містить іншу ("ibuprofen" ↔ "ibuprofenum") + дозування ±20%
            // Якщо немає вихідних речовин — немає що порівнювати
            if (empty($initialIngredients)) {
                $matchFuzzy = 0.0;
            } else {
                $matchFuzzy = $this->calculateFuzzyMatchPercent($initialIngredients, $candidateIngredients);
            }

            // 3. Збіг симптомів/показань (Jaccard за ключовими словами)
            $matchSymptoms = $this->calculateSymptomMatchPercent($initialSymptoms, $candidateSymptoms);

            // --- Базовий бал за Weighted Scoring Model ---
            // Формула: SmartScore = 0.7 × max(exact, fuzzy) + 0.3 × symptoms
            // (Штрафи за обмеження пацієнта застосовуються пізніше)
            $chemicalMatch = $matchExact + $matchFuzzy;
            $matchPercent = (self::WEIGHT_CHEMICAL * $chemicalMatch) + (self::WEIGHT_SYMPTOMS * $matchSymptoms);

            // --- Обмеження на основі контексту пацієнта ---

            // Чи підходить вік пацієнта для цього препарату
            $ageAllowed = $this->checkAgeAllowed($recommendation['min_age'] ?? '', $patientContext['age'] ?? null);

            // Збіги з протипоказаннями пацієнта
            $contraindicationMatches = $this->checkContraindications(
                $recommendation['contraindications'] ?? [],
                $patientContext['contraindications'] ?? ''
            );

            // --- Лікарська взаємодія (Drug-Drug Interaction) ---
            $drugInteraction = $this->checkDrugInteraction(
                $recommendation['name'] ?? '',
                $patientContext['current_medications'] ?? ''
            );

            // 🔥 ВИЯВЛЕННЯ ДУБЛЮВАННЯ ТЕРАПІЇ НА РІВНІ БЕКЕНДУ
            $isSameDrugOverdose = false;
            $currentMedications = $patientContext['current_medications'] ?? '';
            if (!empty($currentMedications)) {
                $takenLower = mb_strtolower($currentMedications);
                $analogueNameLower = mb_strtolower($recommendation['name'] ?? '');
                if (str_contains($analogueNameLower, trim($takenLower)) || str_contains($takenLower, trim($analogueNameLower))) {
                    $isSameDrugOverdose = true;
                }
            }

            // --- Інтелектуальний бал з урахуванням усіх факторів ---
            [$smartScore, $smartReasons] = $this->calculateSmartScore(
                matchPercent:             $matchPercent,
                matchExact:               $matchExact,
                matchFuzzy:               $matchFuzzy,
                matchSymptoms:            $matchSymptoms,
                ageAllowed:               $ageAllowed,
                pregnancySafe:            $recommendation['pregnancy_safe'] ?? null,
                contraindicationMatches:  $contraindicationMatches,
                drugInteraction:          $drugInteraction,
                patientContext:           $patientContext,
                analysisType:             $analysisType,
                dosageMismatch:           $dosageMismatch,
                isSameDrugOverdose:       $isSameDrugOverdose
            );

            $result[] = array_merge($recommendation, [
                'match_exact'             => round($matchExact, 1),
                'match_fuzzy'             => round($matchFuzzy, 1),
                'match_symptoms'          => round($matchSymptoms, 1),
                'match_percent'           => round($matchPercent, 1),
                'age_allowed'             => $ageAllowed,
                'contraindication_matches' => $contraindicationMatches,
                'drug_interaction'        => $drugInteraction,
                'smart_score'             => round($smartScore, 1),
                'smart_reasons'           => $smartReasons,
            ]);
        }

        // =========================================================================
        // ІНТЕЛЕКТУАЛЬНА ДЕДУПЛІКАЦІЯ ДАНИХ (DATA CLEANING)
        // =========================================================================
        if (!empty($result)) {
            $uniqueRecommendations = [];

            foreach ($result as $item) {
                // 1. Очищаємо назву від тексту в дужках: "Барбовал (Barboval)" -> "Барбовал"
                $cleanName = preg_replace('/\s*\(.*?\)\s*/u', '', $item['name']);
                
                // 2. Нормалізуємо ключ для порівняння двійників
                $normalizedKey = mb_strtolower(trim($cleanName));

                // 3. Якщо такого препарату немає — додаємо, якщо є — залишаємо повноцінніший за Smart Score
                if (!isset($uniqueRecommendations[$normalizedKey])) {
                    $uniqueRecommendations[$normalizedKey] = $item;
                } else {
                    if ($item['smart_score'] > $uniqueRecommendations[$normalizedKey]['smart_score']) {
                        $uniqueRecommendations[$normalizedKey] = $item;
                    }
                }
            }

            // Переіндексовуємо масив
            $result = array_values($uniqueRecommendations);

            // 4. Фінальне сортування за спаданням балу
            usort($result, fn($a, $b) => $b['smart_score'] <=> $a['smart_score']);
        }
        // =========================================================================

        return $result;
    }

/**
     * Крос-мовна нормалізація комерційних брендів ліків
     */
    private function normalizeBrandName(?string $name): string
    {
        if (!$name) {
            return '';
        }

        // Беремо перше слово в нижньому регістрі
        $brand = explode(' ', trim(mb_strtolower($name)))[0];

        // Словник крос-мовної синонімії для популярних брендів
        $dictionary = [
            'стріпсілс'   => 'strepsils',
            'стрепсілс'   => 'strepsils',
            'нурофен'     => 'nurofen',
            'ібупрофен'   => 'ibuprofen',
            'парацетамол' => 'paracetamol',
            'німесил'     => 'nimesil',
            'декатилен'   => 'decatylen',
            'тантум'      => 'tantum',
        ];

        return $dictionary[$brand] ?? $brand;
    }

    // =========================================================================
    // ІНТЕЛЕКТУАЛЬНИЙ СКОРИНГ (Weighted Scoring Model)
    // =========================================================================

    /**
     * Розраховує фінальний інтелектуальний бал (0–100) та масив пояснень.
     *
     * Формула (Weighted Scoring Model):
     *   SmartScore = w₁ × max(Match_exact, Match_fuzzy) + w₂ × Match_symptoms
     *   де w₁ = 0.7 (хімічний збіг — жорсткий критерій)
     *       w₂ = 0.3 (симптоматичний збіг — м'який критерій)
     *
     * Після розрахунку базового балу застосовуються штрафи за обмеження пацієнта.
     *
     * @return array{float, array<int, array{type: string, text: string}>}
     */
    private function calculateSmartScore(
        float  $matchPercent,
        float  $matchExact,
        float  $matchFuzzy,
        float  $matchSymptoms,
        bool   $ageAllowed,
        mixed  $pregnancySafe,
        array  $contraindicationMatches,
        array  $drugInteraction,
        array  $patientContext,
        string $analysisType = 'medicine_name',
        bool   $dosageMismatch = false,
        bool   $isSameDrugOverdose = false
    ): array {

        // АДАПТИВНІ ВАГИ ДЛЯ РІЗНИХ ТИПІВ ПОШУКУ
        if ($analysisType === 'symptoms') {
            $w1 = 0.0; // При пошуку за симптомами хімічний склад ігноруємо
            $w2 = 1.0; // Оцінка повністю будується на симптомах (100%)
        } else {
            $w1 = 0.7; // Стандартна модель для аналогів (70% хімія / 30% симптоми)
            $w2 = 0.3;
        }
           
        $reasons = [];

        // --- 1. Базовий бал за формулою Weighted Scoring Model з адаптивними вагами ---
               
        $chemicalMatch = $matchExact + $matchFuzzy;
        $score = ($w1 * $chemicalMatch) + ($w2 * $matchSymptoms);

        // --- 2. Пояснення базового балу ---

        if ($analysisType !== 'symptoms') {
            // Створюємо округлену копію для хімічного збігу (1 знак після коми)
            $chemicalPercentage = round($chemicalMatch, 1);

            if ($chemicalMatch >= 80) {
                $reasons[] = ['type' => 'positive', 'text' => "Висока хімічна схожість: {$chemicalPercentage}%"];
            } elseif ($chemicalMatch >= 50) {
                $reasons[] = ['type' => 'positive', 'text' => "Помірна хімічна схожість: {$chemicalPercentage}%"];
            } elseif ($chemicalMatch > 0) {
                $reasons[] = ['type' => 'neutral', 'text' => "Низька хімічна схожість: {$chemicalPercentage}%"];
            }
            
            if ($dosageMismatch) {
                $reasons[] = [
                    'type' => 'warning',
                    'text' => 'Дозування або форма випуску діючої речовини відрізняється від оригіналу'
                ];
            }
        }

        // ДОДАЄМО ПЕРЕВІРКУ: Якщо речовина точна, але дози різні — виводимо попередження
        if ($dosageMismatch) {
            $reasons[] = [
                'type' => 'warning',
                'text' => 'Невідповідність дозування або форми випуску діючої речовини'
            ];
        }

        // Створюємо округлену копію для виведення на екран (1 знак після коми)
        $symptomsPercentage = round($matchSymptoms, 1);

        if ($matchSymptoms >= 60) {
            $reasons[] = ['type' => 'positive', 'text' => "Збіг симптомів: {$symptomsPercentage}%"];
        } elseif ($matchSymptoms >= 30) {
            $reasons[] = ['type' => 'neutral', 'text' => "Частковий збіг симптомів: {$symptomsPercentage}%"];
        }

        // --- 3. Штрафи за обмеження пацієнта ---

        // Вік
        $age = $patientContext['age'] ?? null;

        if ($age !== null) {
            if (!$ageAllowed) {
                $score -= self::PENALTY_AGE_NOT_ALLOWED;
                $reasons[] = ['type' => 'warning', 'text' => "Вік пацієнта ({$age} р.) не відповідає умовам застосування"];
            } else {
                $reasons[] = ['type' => 'positive', 'text' => "Вік пацієнта ({$age} р.) відповідає умовам застосування"];
            }
        }

        // Вагітність
        $isPregnant = $patientContext['is_pregnant'] ?? false;

        if ($isPregnant && $pregnancySafe !== null) {
            if (!$pregnancySafe) {
                $score -= self::PENALTY_PREGNANCY_UNSAFE;
                $reasons[] = ['type' => 'warning', 'text' => 'Препарат не рекомендований під час вагітності'];
            } else {
                $reasons[] = ['type' => 'positive', 'text' => 'Безпечно для вагітних'];
            }
        }

        // Протипоказання
        if (!empty($patientContext['contraindications'])) {
            if (!empty($contraindicationMatches)) {
                $penalty = min(
                    count($contraindicationMatches) * self::PENALTY_PER_CONTRAINDICATION,
                    45  // максимальний штраф за протипоказання
                );
                $score -= $penalty;
                $reasons[] = [
                    'type' => 'warning',
                    'text' => 'Збіг з вказаними протипоказаннями: ' . implode(', ', $contraindicationMatches),
                ];
            } else {
                // 🟢 Зрозумілий людейноорієнтований текст для обмежень
                $reasons[] = ['type' => 'positive', 'text' => 'Препарат сумісний із вказаними Вами алергіями та обмеженнями здоров\'я'];
            }
        }

        // --- Лікарська взаємодія (Drug-Drug Interaction) ---
        if (!empty($patientContext['current_medications'])) {
            if ($drugInteraction['has_interaction'] ?? false) {
                $severity = $drugInteraction['severity'] ?? 'mild';
                $interactingDrugs = implode(', ', $drugInteraction['interacting_drugs'] ?? []);

                if ($severity === 'severe') {
                    $score -= self::PENALTY_INTERACTION_SEVERE;
                    $reasons[] = [
                        'type' => 'critical',
                        'text' => "Фармацевтична несумісність {$interactingDrugs}: " . ($drugInteraction['description'] ?? ''),
                    ];
                } elseif ($severity === 'moderate') {
                    $score -= self::PENALTY_INTERACTION_MODERATE;
                    $reasons[] = [
                        'type' => 'warning',
                        'text' => "Можлива взаємодія {$interactingDrugs}: " . ($drugInteraction['description'] ?? ''),
                    ];
                } elseif ($severity === 'mild') {
                    $reasons[] = [
                        'type' => 'neutral',
                        'text' => "Незначна взаємодія {$interactingDrugs}",
                    ];
                }
            } else {
                // 🟢 Якщо поточне лікування вказано, але конфліктів немає — виводимо зелене підтвердження
                $reasons[] = [
                    'type' => 'positive', 
                    'text' => 'Конфліктів чи негативних взаємодій із Вашим поточним лікуванням не виявлено'
                ];
            }
        }

        // ШТРАФ ЗА ПРЯМЕ ДУБЛЮВАННЯ ТОРГОВОЇ НАЗВИ (БРЕНДУ) З ПОТОЧНИМ ЛІКУВАННЯМ
        if ($isSameDrugOverdose) {
            $score -= self::PENALTY_THERAPY_DUPLICATION;
        }

        // Обмежуємо бал діапазоном [0, 100]
        $score = max(0.0, min(100.0, $score));

        return [$score, $reasons];
    }

    // =========================================================================
    // ПЕРЕВІРКИ ОБМЕЖЕНЬ
    // =========================================================================

    /**
     * Перевіряє лікарську взаємодію між новим препаратом та поточними ліками.
     *
     * @return array{has_interaction: bool, severity: string, interacting_drugs: string[], description: string, recommendation: string}
     */
    private function checkDrugInteraction(string $newMedicine, string $currentMedications): array
    {
        if (empty(trim($currentMedications)) || empty(trim($newMedicine))) {
            return [
                'has_interaction' => false,
                'severity' => 'none',
                'interacting_drugs' => [],
                'description' => '',
                'recommendation' => '',
            ];
        }

        try {
            $interactionService = app(\App\Services\DrugInteractionService::class);
            return $interactionService->checkInteraction($newMedicine, $currentMedications);
        } catch (\Throwable $e) {
            // У разі помилки повертаємо порожній результат (не блокуємо користувача)
            return [
                'has_interaction' => false,
                'severity' => 'none',
                'interacting_drugs' => [],
                'description' => '',
                'recommendation' => '',
            ];
        }
    }

    /**
     * Перевіряє, чи відповідає вік пацієнта мінімальному віку препарату.
     *
     * Підтримувані формати рядка min_age:
     *   "з народження" / "від народження" / "from birth"  → мін. вік = 0
     *   "з 6 місяців" / "з 3 місяці"                      → конвертується в роки (6 міс = 0.5 р.)
     *   "з 2 тижнів" / "з 14 днів"                        → < 1 місяця → вік пацієнта >= 1 р. завжди підходить
     *   "з 6 років" / "від 12 років"                      → порівнюється напряму в роках
     *
     * Якщо вік пацієнта не вказано — повертає true (не перевіряємо).
     */
    public function checkAgeAllowed(string $minAge, ?int $patientAge): bool
    {
        if ($patientAge === null || empty($minAge)) {
            return true;
        }

        $lower = mb_strtolower($minAge);

        // "з народження", "від народження", "from birth" → мін. вік = 0
        if (str_contains($lower, 'народ') || str_contains($lower, 'birth')) {
            return true;
        }

        // Витягуємо перше число з рядка
        preg_match('/\d+/', $minAge, $matches);
        $number = isset($matches[0]) ? (int) $matches[0] : 0;

        // Якщо вказано місяці — конвертуємо в роки (дробові)
        if (str_contains($lower, 'міс') || str_contains($lower, 'month')) {
            $minAgeYears = $number / 12.0;
            return $patientAge >= $minAgeYears;
        }

        // Якщо тижні або дні — мінімальний вік менший за місяць, будь-який пацієнт >= 1 р. підходить
        if (str_contains($lower, 'тиж') || str_contains($lower, 'week')
            || str_contains($lower, 'день') || str_contains($lower, 'дні') || str_contains($lower, 'day')) {
            return true; // 5 років >> кількох тижнів/днів
        }

        // За замовчуванням — порівнюємо як роки
        return $patientAge >= $number;
    }

    /**
     * Знаходить збіги між протипоказаннями пацієнта та переліком протипоказань препарату.
     * Порівняння без урахування регістру, часткові збіги враховуються.
     *
     * @param  string[] $medicineContraindications
     * @return string[]
     */
    public function checkContraindications(array $medicineContraindications, string $patientContraindications): array
    {
        if (empty($patientContraindications) || empty($medicineContraindications)) {
            return [];
        }

        // Розбиваємо введення пацієнта на окремі елементи
        $userItems = array_filter(
            array_map('trim', preg_split('/[,;]+/', mb_strtolower($patientContraindications))),
            fn($s) => $s !== ''
        );

        $matches = [];

        foreach ($medicineContraindications as $contraindication) {
            $lower = mb_strtolower($contraindication);
            foreach ($userItems as $userItem) {
                if (str_contains($lower, $userItem) || str_contains($userItem, $lower)) {
                    $matches[] = $contraindication;
                    break;
                }
            }
        }

        return array_values(array_unique($matches));
    }

    // =========================================================================
    // НОРМАЛІЗАЦІЯ
    // =========================================================================

    /**
     * Нормалізує масив діючих речовин → ['назва_lowercase' => [дозування...]].
     *
     * @return array<string, float[]>
     */
    private function normalizeIngredients(array $ingredients): array
    {
        $normalized = [];

        foreach ($ingredients as $ingredient) {
            $name = strtolower(trim($ingredient['name'] ?? ''));
            if (!$name) {
                continue;
            }
            $doses = $this->parseQuantities($ingredient['quantity'] ?? '0');
            foreach ($doses as $dose) {
                $normalized[$name][] = $dose;
            }
        }

        return $normalized;
    }

    /**
     * Нормалізує рядок симптомів → унікальні ключові слова (lowercase).
     * Ігнорує слова коротші за MIN_SYMPTOM_WORD_LENGTH.
     *
     * @return string[]
     */
    private function normalizeSymptoms(string $symptoms): array
    {
        // 1. Універсально збагачуємо пошуковий вектор медичними синонімами
        $symptoms = $this->expandColloquialTerms($symptoms);

        // 2. Стандартна токенізація (розбиття на окремі слова)
        $words = preg_split('/[\s,;.()\-]+/u', mb_strtolower($symptoms));

        return array_values(array_unique(array_filter(
            $words,
            fn($word) => mb_strlen($word) >= self::MIN_SYMPTOM_WORD_LENGTH
        )));
    }

    /**
     * 🧠 УНІВЕРСАЛЬНА БАЗА ЗНАНЬ СЕМАНТИЧНИХ СИНОНІМІВ
     * Транслює побутові скарги пацієнта (за морфологічним коренем) у наукову термінологію.
     */
    private function expandColloquialTerms(string $symptoms): string
    {
        $lower = mb_strtolower($symptoms);
        
        // Карта відповідностей: "корінь слова пацієнта" => ["офіційні медичні терміни з бази ЛЗ"]
        // Цей масив можна легко винести в окремий файл config/medical_terms.php або завантажувати з БД
        $semanticDictionary = [
            'тис'   => ['гіпертензія', 'артеріальна', 'гіпертонія', 'тиск'],
            'гіперт'=> ['гіпертензія', 'артеріальна', 'гіпертонія'],
            'серц'  => ['серцева', 'недостатність', 'кардіалгія', 'ішемічна'],
            'діаб'  => ['цукровий', 'діабет'],
            'голов' => ['цефалгія', 'головний', 'біль'],
            'шлун'  => ['гастралгія', 'шлунково-кишкові', 'спазми', 'шлунок'],
            'каш'   => ['кашель', 'бронхіт', 'респіраторні'],
            'темп'  => ['лихоманка', 'гіпертермія', 'підвищена', 'температура'],
            'оч'    => ['офтальмологічні', 'очні', 'кон\'юнктивіт'],
            'печін' => ['гепатопротектори', 'печінкова', 'холецистит'],
        ];

        $enrichedTokens = [];

        // Перевіряємо наявність коренів у запиті пацієнта
        foreach ($semanticDictionary as $root => $medicalTerms) {
            if (str_contains($lower, $root)) {
                $enrichedTokens = array_merge($enrichedTokens, $medicalTerms);
            }
        }

        // Якщо знайдено збіги, склеюємо їх з основним текстом запиту
        if (!empty($enrichedTokens)) {
            $symptoms .= ' ' . implode(' ', array_unique($enrichedTokens));
        }

        return $symptoms;
    }

    /**
     * Витягує числове значення з рядка дозування.
     * Приклади: "500 mg" → 500.0, "1,5 г" → 1.5, "N/A" → 0.0
     */
    private function parseQuantities(string $quantity): array
    {
        preg_match_all('/[\d.,]+/', $quantity, $matches);
        
        if (empty($matches[0])) {
            return [0.0];
        }

        return array_map(fn($q) => (float) str_replace(',', '.', $q), $matches[0]);
    }

    // =========================================================================
    // МЕТОД 1 — Точний збіг діючих речовин
    // =========================================================================

    /**
     * Відсоток речовин оригіналу, чия назва ТОЧНО збігається з кандидатом + дозування ±20%.
     *
     * @param array<string, float[]> $initial
     * @param array<string, float[]> $candidate
     */
    private function calculateExactMatchPercent(array $initial, array $candidate): float
    {
        if (empty($initial)) {
            return 0.0;
        }

        $total = count($initial);
        $matched = 0;

        foreach ($initial as $name => $initialDoses) {
            // Якщо назва речовини повністю збігається — це точна речовина
            if (array_key_exists($name, $candidate)) {
                $matched++;
            }
        }

        return ($matched / $total) * 100;
    }

    // =========================================================================
    // МЕТОД 2 — Нечіткий збіг (схожі назви речовин)
    // =========================================================================

    /**
     * Відсоток речовин оригіналу, чия назва ЧАСТКОВО збігається з кандидатом + дозування ±20%.
     * Вирішує проблему різних форм латинських назв:
     *   "ibuprofen" ↔ "ibuprofenum", "paracetamol" ↔ "paracetamol sodium"
     *
     * @param array<string, float[]> $initial
     * @param array<string, float[]> $candidate
     */
    private function calculateFuzzyMatchPercent(array $initial, array $candidate): float
    {
        if (empty($initial)) {
            return 0.0;
        }
        
        $total = count($initial);
        $matched = 0;

        foreach ($initial as $initName => $initialDoses) {
            // Якщо речовина вже має ТОЧНИЙ збіг, 
            // вона виключається з підрахунку нечітких (схожих) компонентів
            if (array_key_exists($initName, $candidate)) {
                continue;
            }
            
            // Шукаємо схожі форми тільки для решти речовин
            $foundKey = $this->findFuzzyKey($initName, array_keys($candidate));
            if ($foundKey === null) {
                continue;
            }
            $matched++;
        }
        
        return ($matched / $total) * 100;
    }

    /**
     * Перевіряє невідповідність дозування як для точних, так і для схожих речовин.
     */
    private function hasDosageMismatch(array $initial, array $candidate): bool
    {
        foreach ($initial as $name => $initialDoses) {
            // 1. Якщо є точний збіг назви речовини
            if (array_key_exists($name, $candidate)) {
                if (!$this->dosesMatch($initialDoses, $candidate[$name])) {
                    return true; // Дозування точної речовини не збіглося
                }
            } else {
                // 2. Якщо точного немає, шукаємо нечіткий збіг (схожу речовину)
                $foundKey = $this->findFuzzyKey($name, array_keys($candidate));
                if ($foundKey !== null) {
                    if (!$this->dosesMatch($initialDoses, $candidate[$foundKey])) {
                        return true; // Дозування схожої речовини не збіглося
                    }
                }
            }
        }
        return false;
    }

    /**
     * Знаходить ключ зі списку, який частково збігається з назвою (одна містить іншу).
     *
     * @param string[] $candidateKeys
     */
    private function findFuzzyKey(string $initName, array $candidateKeys): ?string
    {
        // Витягуємо перше (головне) слово діючої речовини (напр. "magnesii")
        $initFirstWord = explode(' ', trim($initName))[0];
        
        foreach ($candidateKeys as $candName) {
            // Базова перевірка на випадок однослівних назв речовин
            if (str_contains($candName, $initName) || str_contains($initName, $candName)) {
                return $candName;
            }

            // Нова логіка для багатослівних латинських солей:
            $candFirstWord = explode(' ', trim($candName))[0];
            
            // Якщо головні елементи збігаються (напр. magnesii ↔ magnesii), а солі різні
            if (!empty($initFirstWord) && $initFirstWord === $candFirstWord && strlen($initFirstWord) > 3) {
                return $candName;
            }
        }
        return null;
    }

    // =========================================================================
    // МЕТОД 3 — Збіг симптомів / показань
    // =========================================================================

    /**
     * Розраховує відсоток збігу симптомів пацієнта з показаннями препарату.
     *
     * Алгоритм:
     *   1. Визначаємо, скільки симптомів пацієнта покриває препарат (покриття)
     *   2. Штрафуємо, якщо препарат має занадто багато зайвих показань (нецільовий)
     *   3. Результат = покриття × коефіцієнт цільовості
     *
     * Це дає більш точну оцінку: препарат, який лікує "все підряд", отримає нижчий бал,
     * ніж препарат, який цільовий саме на симптоми пацієнта.
     *
     * @param string[] $initial   Симптоми пацієнта
     * @param string[] $candidate Показання препарату
     */
    private function calculateSymptomMatchPercent(array $initial, array $candidate): float
    {
        if (empty($initial) || empty($candidate)) {
            return 0.0;
        }

        // 1. Покриття: скільки слів пацієнта є в показаннях препарату
        $intersection = array_intersect($initial, $candidate);
        $coverage = count($intersection) / count($initial); // 0.0 – 1.0

        // 2. Коефіцієнт цільовості: наскільки препарат сфокусований на симптомах пацієнта
        // Якщо у препарата 100 показань, а пацієнт має 2 — це нецільовий препарат
        // Формула: |перетин| / |показання препарату| (але не більше 1.0)
        $focus = min(1.0, count($intersection) / max(1, count($candidate)));

        // 3. Фінальний бал: покриття × вага_покриття + цільовість × вага_цільовості
        // Пріоритет на покритті (пацієнт хоче знайти ліки для своїх симптомів)
        $score = ($coverage * 0.7) + ($focus * 0.3);

        return $score * 100;
    }

    // =========================================================================
    // ДОПОМІЖНЕ — Порівняння дозувань
    // =========================================================================

    /**
     * Повертає true якщо хоча б одна пара (initialDose, candidateDose) потрапляє в ±20%.
     * Якщо будь-яке дозування = 0 (невідоме) — зараховуємо збіг по назві.
     *
     * @param float[] $initialDoses
     * @param float[] $candidateDoses
     */
    private function dosesMatch(array $initialDoses, array $candidateDoses): bool
    {
        $hasZero = in_array(0.0, $initialDoses, true) || in_array(0.0, $candidateDoses, true);
        if ($hasZero) {
            return true;
        }

        foreach ($initialDoses as $initDose) {
            $lower = $initDose * (1 - self::QUANTITY_WINDOW);
            $upper = $initDose * (1 + self::QUANTITY_WINDOW);

            foreach ($candidateDoses as $candDose) {
                if ($candDose >= $lower && $candDose <= $upper) {
                    return true;
                }
            }
        }

        return false;
    }
}
