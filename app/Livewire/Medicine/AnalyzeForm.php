<?php

namespace App\Livewire\Medicine;

use App\Services\MedicineAnalyzeService;
use App\Services\MedicineDetailsService;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Throwable;

class AnalyzeForm extends Component
{
    // --- Форма ---
    #[Validate('required|string|max:500')]
    public string $query = '';

    #[Validate('required|integer|min:0|max:120')]
    public ?int $age = null;

    #[Validate('boolean')]
    public bool $isPregnant = false;

    #[Validate('nullable|string|max:500')]
    public string $contraindications = '';

    #[Validate('required|in:medicine_name,symptoms')]
    public string $analysisType = 'medicine_name';

    // --- Стан ---
    public string $state = 'idle'; // idle | loading | result | error

    public ?array $result = null;
    public ?string $errorMessage = null;

    public function submit(): void
    {
        $this->validate();

        set_time_limit(180); // Gemini може відповідати довго

        $this->state = 'loading';
        $this->result = null;
        $this->errorMessage = null;

        try {
            $details = app(MedicineDetailsService::class);
            $analyzer = app(MedicineAnalyzeService::class);

            if ($this->analysisType === 'medicine_name') {
                // ЗАПИТ 1 — Деталі препарату (назва, симптоми, діючі речовини)
                $medicine = $details->getMedicineDetails($this->query);

                // ЗАПИТ 2 — Пошук препаратів за симптомами (семантичний)
                $recommendations = $details->getRecommendationsBySymptoms(
                    $medicine['symptoms'],
                    $medicine['name']
                );

                // Зберігаємо інструкції в БД з отриманих даних AI
                $details->findOrSaveInstructions($medicine['name'], $medicine['instructions_html'] ?? '');
                foreach ($recommendations as $rec) {
                    $details->findOrSaveInstructions($rec['name'], $rec['instructions_html'] ?? '');
                }

                // Порівняння діючих речовин локально (без AI)
                $filtered = $analyzer->analyze($recommendations, $medicine);

                $this->result = [
                    'medicine' => $medicine,
                    'recommendations' => $filtered,
                ];
            } else {
                // Симптоми — поки заглушка (наступний крок)
                throw new \RuntimeException('Аналіз по симптомах ще в розробці.');
            }

            $this->state = 'result';
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
            $this->state = 'error';
        }
    }

    public function newSearch(): void
    {
        $this->state = 'idle';
        $this->result = null;
        $this->errorMessage = null;
    }

    public function render()
    {
        return view('livewire.medicine.analyze-form');
    }
}
