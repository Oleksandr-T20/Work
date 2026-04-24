<?php

namespace App\Services;

class MedicineAnalyzeService
{
    /**
     * Вікно допустимого відхилення кількості: ±20% від оригіналу.
     */
    private const QUANTITY_WINDOW = 0.20;

    /**
     * Фільтрує масив рекомендацій за % збігом діючих речовин із вихідним препаратом.
     *
     * @param  array<int, array<string, mixed>> $recommendations
     * @param  array<string, mixed>             $initialMedicine
     * @return array<int, array<string, mixed>>
     */
    public function analyze(array $recommendations, array $initialMedicine): array
    {
        $initial = $this->normalizeIngredients($initialMedicine['active_ingredients'] ?? []);

        if (empty($initial)) {
            return array_map(fn($r) => array_merge($r, ['match_percent' => 100.0]), $recommendations);
        }

        $result = [];

        foreach ($recommendations as $recommendation) {
            $candidate    = $this->normalizeIngredients($recommendation['active_ingredients'] ?? []);
            $matchPercent = $this->calculateMatchPercent($initial, $candidate);

            $result[] = array_merge($recommendation, [
                'match_percent' => round($matchPercent, 1),
            ]);
        }

        usort($result, fn($a, $b) => $b['match_percent'] <=> $a['match_percent']);

        return $result;
    }

    /**
     * Нормалізує масив діючих речовин.
     * Ключ — lowercase назва, значення — МАСИВ всіх дозувань цієї речовини.
     *
     * Одна речовина може мати кілька дозувань (напр. Pantoprazolum 20mg + 40mg).
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
            $qty = $this->parseQuantity($ingredient['quantity'] ?? '0');

            // Додаємо дозування до масиву (не перезаписуємо!)
            $normalized[$name][] = $qty;
        }

        return $normalized;
    }

    /**
     * Витягує числове значення з рядка (напр. "500 mg" → 500.0).
     */
    private function parseQuantity(string $quantity): float
    {
        preg_match('/[\d.,]+/', $quantity, $matches);
        return isset($matches[0]) ? (float) str_replace(',', '.', $matches[0]) : 0.0;
    }

    /**
     * Розраховує % збігу:
     *   matched_substances / total_unique_initial_substances * 100
     *
     * Речовина вважається "збіглою" якщо:
     *   - назва є у кандидаті
     *   - І хоча б ONE з дозувань кандидата потрапляє у вікно ±20%
     *     відносно ХОЧА Б ОДНОГО дозування в оригіналі
     *   (якщо будь-яке ��озування = 0 — зараховуємо збіг по назві)
     *
     * @param array<string, float[]> $initial
     * @param array<string, float[]> $candidate
     */
    private function calculateMatchPercent(array $initial, array $candidate): float
    {
        $total   = count($initial);
        $matched = 0;

        foreach ($initial as $name => $initialDoses) {
            if (!array_key_exists($name, $candidate)) {
                continue;
            }

            $candidateDoses = $candidate[$name];

            // Якщо будь-яке дозування невідоме (0) — зараховуємо збіг по назві
            $hasZero = in_array(0.0, $initialDoses, true)
                    || in_array(0.0, $candidateDoses, true);

            if ($hasZero) {
                $matched++;
                continue;
            }

            // Перевіряємо: чи є хоча б одна пара (initialDose, candidateDose)
            // де candidateDose потрапляє у вікно ±20% від initialDose
            $found = false;
            foreach ($initialDoses as $initDose) {
                $lower = $initDose * (1 - self::QUANTITY_WINDOW);
                $upper = $initDose * (1 + self::QUANTITY_WINDOW);

                foreach ($candidateDoses as $candDose) {
                    if ($candDose >= $lower && $candDose <= $upper) {
                        $found = true;
                        break 2;
                    }
                }
            }

            if ($found) {
                $matched++;
            }
        }

        return ($matched / $total) * 100;
    }
}
