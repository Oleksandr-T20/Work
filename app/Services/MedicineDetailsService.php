<?php

namespace App\Services;

use App\Models\Medicine;
use App\Services\AI\GeminiService;

class MedicineDetailsService
{
    public function __construct(private GeminiService $gemini) {}

    /**
     * ЗАПИТ 1 — Деталі препарату.
     */
    public function getMedicineDetails(string $medicineName): array
    {
        $prompt = <<<PROMPT
Ти — медичний довідник. Надай інформацію про препарат "{$medicineName}".
Всі назви діючих речовин ТІЛЬКИ латиною. Кількість у міліграмах або мілілітрах.

Поверни JSON у такому форматі:
{
  "name": "точна назва препарату",
  "symptoms": "детальний рядок показань та симптомів через кому",
  "min_age": "з якого віку дозволений (наприклад: з 6 років, з 12 років, з народження)",
  "pregnancy_safe": true або false,
    "contraindications": ["протипоказання 1", "протипоказання 2"],
    "average_price_uah": "від X до Y грн",
    "country": "країна виробника українською (наприклад: Франція, Німеччина, Україна)",
    "available_in_ukraine": true або false,
    "dosage": "детальний опис способу застосування та дозування для різних вікових груп (1-3 речення)",
    "active_ingredients": [
      {"name": "латинська назва речовини", "quantity": "кількість мг або мл"}
    ],
    "instructions_html": "коротка інструкція із секціями: показання, дозування, протипоказання. Використовуй HTML теги h3, p, ul, li, strong"
}
PROMPT;

        $data = $this->gemini->askJson($prompt);

        if (!isset($data['name'], $data['symptoms'], $data['active_ingredients'])) {
            throw new \RuntimeException('AI повернув неочікувану структуру (getMedicineDetails).');
        }

        return $data;
    }

    /**
     * ЗАПИТ 2 — Пошук препаратів за симптомами + інструкції.
     */
    public function getRecommendationsBySymptoms(string $symptoms, string $excludeName): array
    {
        $prompt = <<<PROMPT
Ти — медичний довідник. Знайди до 10 препаратів для лікування таких симптомів:
"{$symptoms}"

НЕ включай препарат "{$excludeName}".
Всі назви діючих речовин ТІЛЬКИ латиною. Кількість у міліграмах або мілілітрах.

Поверни JSON-масив:
[
  {
    "name": "назва препарату",
    "symptoms": "показання через кому",
    "min_age": "з якого віку дозволений (наприклад: з 6 років, з 12 років, з народження)",
    "pregnancy_safe": true або false,
    "contraindications": ["протипоказання 1", "протипоказання 2"],
    "average_price_uah": "від X до Y грн",
    "country": "країна виробника українською (наприклад: Франція, Німеччина, Україна)",
    "available_in_ukraine": true або false,
    "active_ingredients": [
      {"name": "латинська назва речовини", "quantity": "кількість мг або мл", "route": "спосіб застосування українською (наприклад: перорально, внутрішньовенно, зовнішньо, інгаляційно, сублінгвально)"}
    ],
    "instructions_html": "коротка інструкція із секціями: показання, дозування, протипоказання. Використовуй HTML теги h3, p, ul, li, strong"
  }
]
PROMPT;

        $data = $this->gemini->askJson($prompt);

        if (!is_array($data)) {
            throw new \RuntimeException('AI повернув неочікувану структуру (getRecommendationsBySymptoms).');
        }

        return $data;
    }

    /**
     * Зберігає або оновлює препарат у БД з повними даними від AI.
     * Використовує updateOrCreate щоб при повторному запиті доповнити дані.
     */
    public function saveMedicine(array $data): Medicine
    {
        return Medicine::updateOrCreate(
            ['name' => $data['name']],
            [
                'instructions_html'   => $data['instructions_html'] ?? '',
                'symptoms'            => $data['symptoms'] ?? null,
                'active_ingredients'  => $data['active_ingredients'] ?? null,
                'min_age'             => $data['min_age'] ?? null,
                'pregnancy_safe'      => $data['pregnancy_safe'] ?? null,
                'country'             => $data['country'] ?? null,
                'available_in_ukraine' => $data['available_in_ukraine'] ?? null,
            ]
        );
    }

    /**
     * Шукає в локальній БД препарати, які мають спільні діючі речовини
     * з вихідним препаратом і ще не присутні у списку AI-рекомендацій.
     *
     * Повертає їх у форматі масиву (сумісному з AI-результатами),
     * з позначкою source = 'local_db'.
     *
     * @param  array<int, array{name: string, quantity: string}> $initialIngredients
     * @param  string   $excludeName   назва вихідного препарату (не включати)
     * @param  string[] $alreadyFound  назви препаратів що вже є в результатах
     * @return array<int, array<string, mixed>>
     */
    public function findLocalAnalogues(array $initialIngredients, string $excludeName, array $alreadyFound = []): array
    {
        if (empty($initialIngredients)) {
            return [];
        }

        // Нормалізуємо назви діючих речовин оригіналу для порівняння
        $initialNames = array_map(
            fn($i) => strtolower(trim($i['name'] ?? '')),
            $initialIngredients
        );
        $initialNames = array_filter($initialNames);

        // Назви виключень (оригінал + вже знайдені AI-аналоги) для порівняння без урахування регістру
        $excludeNames = array_map('mb_strtolower', array_merge([$excludeName], $alreadyFound));

        // Завантажуємо всі препарати з БД, що мають збережені діючі речовини
        $candidates = Medicine::whereNotNull('active_ingredients')
            ->whereNotNull('symptoms')
            ->get();

        $found = [];

        foreach ($candidates as $medicine) {
            // Пропускаємо оригінал і вже знайдені аналоги
            if (in_array(mb_strtolower($medicine->name), $excludeNames, true)) {
                continue;
            }

            $candidateIngredients = $medicine->active_ingredients ?? [];
            $candidateNames = array_map(
                fn($i) => strtolower(trim($i['name'] ?? '')),
                $candidateIngredients
            );
            $candidateNames = array_filter($candidateNames);

            // Перевіряємо нечіткий збіг: хоч одна речовина спільна (одна назва містить іншу)
            $hasMatch = false;
            foreach ($initialNames as $initName) {
                foreach ($candidateNames as $candName) {
                    if (
                        $initName &&
                        $candName &&
                        (str_contains($candName, $initName) || str_contains($initName, $candName))
                    ) {
                        $hasMatch = true;
                        break 2;
                    }
                }
            }

            if (!$hasMatch) {
                continue;
            }

            // Формуємо запис у форматі сумісному з AI-результатами
            $found[] = [
                'name'                => $medicine->name,
                'symptoms'            => $medicine->symptoms ?? '',
                'active_ingredients'  => $medicine->active_ingredients ?? [],
                'min_age'             => $medicine->min_age,
                'pregnancy_safe'      => $medicine->pregnancy_safe,
                'contraindications'   => [],
                'average_price_uah'   => null,
                'country'             => $medicine->country,
                'available_in_ukraine' => $medicine->available_in_ukraine,
                'instructions_html'   => $medicine->instructions_html ?? '',
                'source'              => 'local_db',
            ];
        }

        return $found;
    }

    /**
     * @deprecated Використовуй saveMedicine() для збереження повних даних.
     */
    public function findOrSaveInstructions(string $medicineName, string $instructionsHtml): Medicine
    {
        return Medicine::firstOrCreate(
            ['name' => $medicineName],
            ['instructions_html' => $instructionsHtml]
        );
    }
}
