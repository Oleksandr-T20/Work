<?php

namespace App\Services;

use App\Services\AI\GeminiService;

class DrugInteractionService
{
    public function __construct(private GeminiService $gemini) {}

    /**
     * Перевіряє взаємодію між новим препаратом та поточними ліками пацієнта.
     *
     * @param string $newMedicine Назва нового препарату
     * @param string $currentMedications Список поточних ліків (через кому)
     * @return array{
     *     has_interaction: bool,
     *     severity: 'none'|'mild'|'moderate'|'severe',
     *     interacting_drugs: string[],
     *     description: string,
     *     recommendation: string
     * }
     */
    public function checkInteraction(string $newMedicine, string $currentMedications): array
    {
        if (empty(trim($currentMedications))) {
            return [
                'has_interaction' => false,
                'severity' => 'none',
                'interacting_drugs' => [],
                'description' => '',
                'recommendation' => '',
            ];
        }

        $prompt = <<<PROMPT
Ти — клінічний фармацевт. Перевір взаємодію між новим препаратом "{$newMedicine}" та ліками, які пацієнт вже приймає:
{$currentMedications}

Поверни JSON:
{
  "has_interaction": true або false,
  "severity": "none" або "mild" або "moderate" або "severe",
  "interacting_drugs": ["назва ліків 1", "назва ліків 2"],
  "description": "опис взаємодії українською (1-2 речення)",
  "recommendation": "рекомендація для пацієнта українською (1 речення)"
}

Критерії severity:
- none: взаємодії немає
- mild: незначна взаємодія, можна приймати разом
- moderate: помірна взаємодія, потрібен контроль лікаря
- severe: небезпечна взаємодія, протипоказано поєднувати
PROMPT;

        $data = $this->gemini->askJson($prompt);

        return [
            'has_interaction'   => $data['has_interaction'] ?? false,
            'severity'          => $data['severity'] ?? 'none',
            'interacting_drugs' => $data['interacting_drugs'] ?? [],
            'description'       => $data['description'] ?? '',
            'recommendation'    => $data['recommendation'] ?? '',
        ];
    }

    /**
     * Масова перевірка взаємодій для списку препаратів.
     *
     * @param array $medicines Список назв препаратів для перевірки
     * @param string $currentMedications Поточні ліки пацієнта
     * @return array<string, array> Асоціативний масив [назва_препарату => результат_взаємодії]
     */
    public function checkInteractionsBatch(array $medicines, string $currentMedications): array
    {
        if (empty(trim($currentMedications)) || empty($medicines)) {
            return [];
        }

        $medicinesList = implode(', ', $medicines);

        $prompt = <<<PROMPT
Ти — клінічний фармацевт. Перевір взаємодію між кожним препаратом зі списку та поточними ліками пацієнта.

Препарати для перевірки: {$medicinesList}

Поточні ліки пацієнта: {$currentMedications}

Поверни JSON-об'єкт, де ключ — назва препарату, значення — інформація про взаємодію:
{
  "Назва препарату 1": {
    "has_interaction": true або false,
    "severity": "none" або "mild" або "moderate" або "severe",
    "interacting_drugs": ["назва ліків"],
    "description": "опис взаємодії",
    "recommendation": "рекомендація"
  },
  "Назва препарату 2": { ... }
}

Критерії severity:
- none: взаємодії немає
- mild: незначна взаємодія, можна приймати разом
- moderate: помірна взаємодія, потрібен контроль лікаря
- severe: небезпечна взаємодія, протипоказано поєднувати

Обов'язково перевір КОЖЕН препарат зі списку.
PROMPT;

        $data = $this->gemini->askJson($prompt);

        return $data ?? [];
    }
}
