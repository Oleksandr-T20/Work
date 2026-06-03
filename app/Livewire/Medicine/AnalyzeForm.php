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

    protected function rules(): array
    {
        return [
            'query'             => ['required', 'string', 'max:500',
                function ($attribute, $value, $fail) {
                    if ($this->analysisType === 'medicine_name' && !preg_match('/^[\p{L}\p{N}\s.,\-\/]+$/u', $value)) {
                        $fail('Назва препарату містить неприпустимі символи.');
                    } elseif ($this->analysisType === 'symptoms' && !preg_match('/^[\p{L}\p{N}\s.,\-\/]+$/u', $value)) {
                        $fail('Симптоми містять неприпустимі символи.');
                    }
                },
            ],
            'age'               => 'required|integer|min:0|max:120',
            'isPregnant'        => 'boolean',
            'contraindications' => 'nullable|string|max:500',
            'analysisType'      => 'required|in:medicine_name,symptoms',
        ];
    }

    public function submit(): void
    {
        $this->validate();

        set_time_limit(180); // Gemini може відповідати довго

        $this->state = 'loading';
        $this->result = null;
        $this->errorMessage = null;

        try {
            $details  = app(MedicineDetailsService::class);
            $analyzer = app(MedicineAnalyzeService::class);

            if ($this->analysisType === 'medicine_name') {
                // ЗАПИТ 1 — Деталі препарату (назва, симптоми, діючі речовини)
                $medicine = $details->getMedicineDetails($this->query);

                // ЗАПИТ 2 — Пошук аналогів за симптомами
                $recommendations = $details->getRecommendationsBySymptoms(
                    $medicine['symptoms'],
                    $medicine['name']
                );

                // Зберігаємо повні дані в БД (для майбутнього локального пошуку)
                $details->saveMedicine($medicine);
                foreach ($recommendations as $rec) {
                    $details->saveMedicine($rec);
                }

                // Контекст пацієнта — передається в аналізатор для інтелектуального скорингу
                $patientContext = [
                    'age'              => $this->age,
                    'is_pregnant'      => $this->isPregnant,
                    'contraindications' => $this->contraindications,
                ];

                // Шукаємо додаткові аналоги в локальній БД за діючими речовинами
                // (вирішує проблему асиметрії: Піколакс ↔ Слабілакс і навпаки)
                $aiFoundNames = array_column($recommendations, 'name');
                $localAnalogues = $details->findLocalAnalogues(
                    $medicine['active_ingredients'] ?? [],
                    $medicine['name'],
                    $aiFoundNames
                );

                // Об'єднуємо AI-аналоги і локальні (локальні додаються в кінець перед аналізом)
                $allRecommendations = array_merge($recommendations, $localAnalogues);

                // Повний аналіз: хімічна/симптоматична схожість + smart_score з урахуванням обмежень пацієнта
                $filtered = $analyzer->analyze($allRecommendations, $medicine, $patientContext);

                // Для основного препарату — перевіряємо обмеження через той самий сервіс
                $medicine['age_allowed']             = $analyzer->checkAgeAllowed($medicine['min_age'] ?? '', $this->age);
                $medicine['contraindication_matches'] = $analyzer->checkContraindications($medicine['contraindications'] ?? [], $this->contraindications);

                $this->result = [
                    'medicine'        => $medicine,
                    'recommendations' => $filtered,
                ];
            } elseif ($this->analysisType === 'symptoms') {
                // ЗАПИТ 1 — Пошук аналогів за симптомами
                $recommendations = $details->getRecommendationsBySymptoms(
                    $this->query,
                    '' // Немає основного препарату для виключення
                );

                // Створюємо фіктивний препарат для аналізу
                $medicine = [
                    'name'               => $this->query,
                    'symptoms'           => $this->query,
                    'active_ingredients' => [],
                    'min_age'            => '',
                    'contraindications'  => [],
                ];

                // Зберігаємо повні дані в БД (для майбутнього локального пошуку)
                foreach ($recommendations as $rec) {
                    $details->saveMedicine($rec);
                }

                // Контекст пацієнта — передається в аналізатор для інтелектуального скорингу
                $patientContext = [
                    'age'              => $this->age,
                    'is_pregnant'      => $this->isPregnant,
                    'contraindications' => $this->contraindications,
                ];

                // Шукаємо додаткові аналоги в локальній БД за діючими речовинами
                // (вирішує проблему асиметрії: Піколакс ↔ Слабілакс і навпаки)
                $aiFoundNames = array_column($recommendations, 'name');
                $localAnalogues = $details->findLocalAnalogues(
                    [], // Немає діючих речовин для пошуку локальних аналогів
                    '', // Немає основного препарату для виключення
                    $aiFoundNames
                );

                // Об'єднуємо AI-аналоги і локальні (локальні додаються в кінець перед аналізом)
                $allRecommendations = array_merge($recommendations, $localAnalogues);

                // Повний аналіз: хімічна/симптоматична схожість + smart_score з урахуванням обмежень пацієнта
                $filtered = $analyzer->analyze($allRecommendations, $medicine, $patientContext);

                $this->result = [
                    'medicine'        => $medicine,
                    'recommendations' => $filtered,
                ];
            } else {
                throw new \RuntimeException('Невідомий тип аналізу.');
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
