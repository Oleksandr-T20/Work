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
    // Штрафи для інтелектуального скорингу (smart_score)
    // -------------------------------------------------------------------------

    /** Вік пацієнта не відповідає мінімальному — сильний штраф */
    private const PENALTY_AGE_NOT_ALLOWED = 50;

    /** Препарат заборонений вагітним, а пацієнтка вагітна — сильний штраф */
    private const PENALTY_PREGNANCY_UNSAFE = 40;

    /** Кожен збіг з протипоказаннями пацієнта — штраф за один збіг */
    private const PENALTY_PER_CONTRAINDICATION = 15;

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
    public function analyze(array $recommendations, array $initialMedicine, array $patientContext = []): array
    {
        $initialIngredients = $this->normalizeIngredients($initialMedicine['active_ingredients'] ?? []);
        $initialSymptoms    = $this->normalizeSymptoms($initialMedicine['symptoms'] ?? '');

        if (empty($initialIngredients)) {
            return array_map(fn($r) => array_merge($r, [
                'match_exact'             => 100.0,
                'match_fuzzy'             => 100.0,
                'match_symptoms'          => 100.0,
                'match_percent'           => 100.0,
                'age_allowed'             => true,
                'contraindication_matches' => [],
                'smart_score'             => 100.0,
                'smart_reasons'           => [],
            ]), $recommendations);
        }

        $result = [];

        foreach ($recommendations as $recommendation) {
            $candidateIngredients = $this->normalizeIngredients($recommendation['active_ingredients'] ?? []);
            $candidateSymptoms    = $this->normalizeSymptoms($recommendation['symptoms'] ?? '');

            // --- Три метрики хімічно-симптоматичної схожості ---

            // 1. Точний збіг: назва речовини ідентична + дозування ±20%
            $matchExact = $this->calculateExactMatchPercent($initialIngredients, $candidateIngredients);

            // 2. Нечіткий збіг: одна назва містить іншу ("ibuprofen" ↔ "ibuprofenum") + дозування ±20%
            $matchFuzzy = $this->calculateFuzzyMatchPercent($initialIngredients, $candidateIngredients);

            // 3. Збіг симптомів/показань (Jaccard за ключовими словами)
            $matchSymptoms = $this->calculateSymptomMatchPercent($initialSymptoms, $candidateSymptoms);

            // Базовий відсоток схожості — максимум з трьох
            $matchPercent = max($matchExact, $matchFuzzy, $matchSymptoms);

            // --- Обмеження на основі контексту пацієнта ---

            // Чи підходить вік пацієнта для цього препарату
            $ageAllowed = $this->checkAgeAllowed($recommendation['min_age'] ?? '', $patientContext['age'] ?? null);

            // Збіги з протипоказаннями пацієнта
            $contraindicationMatches = $this->checkContraindications(
                $recommendation['contraindications'] ?? [],
                $patientContext['contraindications'] ?? ''
            );

            // --- Інтелектуальний бал з урахуванням усіх факторів ---
            [$smartScore, $smartReasons] = $this->calculateSmartScore(
                matchPercent:             $matchPercent,
                matchExact:               $matchExact,
                matchFuzzy:               $matchFuzzy,
                matchSymptoms:            $matchSymptoms,
                ageAllowed:               $ageAllowed,
                pregnancySafe:            $recommendation['pregnancy_safe'] ?? null,
                contraindicationMatches:  $contraindicationMatches,
                patientContext:           $patientContext,
            );

            $result[] = array_merge($recommendation, [
                'match_exact'             => round($matchExact, 1),
                'match_fuzzy'             => round($matchFuzzy, 1),
                'match_symptoms'          => round($matchSymptoms, 1),
                'match_percent'           => round($matchPercent, 1),
                'age_allowed'             => $ageAllowed,
                'contraindication_matches' => $contraindicationMatches,
                'smart_score'             => round($smartScore, 1),
                'smart_reasons'           => $smartReasons,
            ]);
        }

        // Сортуємо за інтелектуальним балом (найкращий варіант — першим)
        usort($result, fn($a, $b) => $b['smart_score'] <=> $a['smart_score']);

        return $result;
    }

    // =========================================================================
    // ІНТЕЛЕКТУАЛЬНИЙ СКОРИНГ
    // =========================================================================

    /**
     * Розраховує фінальний інтелектуальний бал (0–100) та масив пояснень.
     *
     * Алгоритм:
     *   1. Стартуємо з базового балу (max хімічних/симптоматичних метрик)
     *   2. Нараховуємо штрафи за обмеження пацієнта (вік, вагітність, протипоказання)
     *   3. Фіксуємо причини у вигляді зрозумілих повідомлень
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
        array  $patientContext,
    ): array {
        $score   = $matchPercent;
        $reasons = [];

        // --- Позитивні пояснення схожості ---

        if ($matchExact >= 80) {
            $reasons[] = ['type' => 'positive', 'text' => "Висока хімічна схожість — точні речовини: {$matchExact}%"];
        } elseif ($matchFuzzy >= 80) {
            $reasons[] = ['type' => 'positive', 'text' => "Схожі діючі речовини: {$matchFuzzy}%"];
        } elseif ($matchSymptoms >= 60) {
            $reasons[] = ['type' => 'positive', 'text' => "Збіг за показаннями та симптомами: {$matchSymptoms}%"];
        } else {
            $reasons[] = ['type' => 'neutral', 'text' => "Загальна схожість: {$matchPercent}%"];
        }

        // --- Вік ---

        $age = $patientContext['age'] ?? null;

        if ($age !== null) {
            if (!$ageAllowed) {
                // Штраф за невідповідний вік
                $score -= self::PENALTY_AGE_NOT_ALLOWED;
                $reasons[] = ['type' => 'warning', 'text' => "Вік пацієнта ({$age} р.) не відповідає умовам застосування"];
            } else {
                $reasons[] = ['type' => 'positive', 'text' => "Вік пацієнта ({$age} р.) відповідає умовам застосування"];
            }
        }

        // --- Вагітність ---

        $isPregnant = $patientContext['is_pregnant'] ?? false;

        if ($isPregnant && $pregnancySafe !== null) {
            if (!$pregnancySafe) {
                $score -= self::PENALTY_PREGNANCY_UNSAFE;
                $reasons[] = ['type' => 'warning', 'text' => 'Препарат не рекомендований під час вагітності'];
            } else {
                $reasons[] = ['type' => 'positive', 'text' => 'Безпечно для вагітних'];
            }
        }

        // --- Протипоказання ---

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
                $reasons[] = ['type' => 'positive', 'text' => 'Протипоказань за Вашим профілем не виявлено'];
            }
        }

        // Обмежуємо бал діапазоном [0, 100]
        $score = max(0.0, min(100.0, $score));

        return [$score, $reasons];
    }

    // =========================================================================
    // ПЕРЕВІРКИ ОБМЕЖЕНЬ
    // =========================================================================

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
            $normalized[$name][] = $this->parseQuantity($ingredient['quantity'] ?? '0');
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
        $words = preg_split('/[\s,;.()\-]+/u', mb_strtolower($symptoms));

        return array_values(array_unique(array_filter(
            $words,
            fn($word) => mb_strlen($word) >= self::MIN_SYMPTOM_WORD_LENGTH
        )));
    }

    /**
     * Витягує числове значення з рядка дозування.
     * Приклади: "500 mg" → 500.0, "1,5 г" → 1.5, "N/A" → 0.0
     */
    private function parseQuantity(string $quantity): float
    {
        preg_match('/[\d.,]+/', $quantity, $matches);
        return isset($matches[0]) ? (float) str_replace(',', '.', $matches[0]) : 0.0;
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
        $total = count($initial);
        $matched = 0;

        foreach ($initial as $name => $initialDoses) {
            if (!array_key_exists($name, $candidate)) {
                continue;
            }
            if ($this->dosesMatch($initialDoses, $candidate[$name])) {
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
        $total = count($initial);
        $matched = 0;

        foreach ($initial as $initName => $initialDoses) {
            $foundKey = $this->findFuzzyKey($initName, array_keys($candidate));
            if ($foundKey === null) {
                continue;
            }
            if ($this->dosesMatch($initialDoses, $candidate[$foundKey])) {
                $matched++;
            }
        }

        return ($matched / $total) * 100;
    }

    /**
     * Знаходить ключ зі списку, який частково збігається з назвою (одна містить іншу).
     *
     * @param string[] $candidateKeys
     */
    private function findFuzzyKey(string $initName, array $candidateKeys): ?string
    {
        foreach ($candidateKeys as $candName) {
            if (str_contains($candName, $initName) || str_contains($initName, $candName)) {
                return $candName;
            }
        }
        return null;
    }

    // =========================================================================
    // МЕТОД 3 — Збіг симптомів / показань
    // =========================================================================

    /**
     * Jaccard-схожість між наборами ключових слів симптомів:
     *   |перетин| / |об'єднання| × 100
     *
     * @param string[] $initial
     * @param string[] $candidate
     */
    private function calculateSymptomMatchPercent(array $initial, array $candidate): float
    {
        if (empty($initial) || empty($candidate)) {
            return 0.0;
        }

        $intersection = array_intersect($initial, $candidate);
        $union        = array_unique(array_merge($initial, $candidate));

        return (count($intersection) / count($union)) * 100;
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
