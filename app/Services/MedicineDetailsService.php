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
  "average_price_uah": "від X до Y грн",
  "active_ingredients": [
    {"name": "латинська назва речовини", "quantity": "кількість мг або мл"}
  ],
  "instructions_html": "повна інструкція із секціями: склад, показання, дозування, побічні ефекти, протипоказання. Використовуй HTML теги h3, p, ul, li, strong"
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
    "average_price_uah": "від X до Y грн",
    "active_ingredients": [
      {"name": "латинська назва речовини", "quantity": "кількість мг або мл"}
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
     * Перевіряє наявність препарату у БД і зберігає якщо немає.
     */
    public function findOrSaveInstructions(string $medicineName, string $instructionsHtml): Medicine
    {
        return Medicine::firstOrCreate(
            ['name' => $medicineName],
            ['instructions_html' => $instructionsHtml]
        );
    }
}
