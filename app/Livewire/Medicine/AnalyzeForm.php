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

    #[Validate('nullable|string|max:500')]
    public string $currentMedications = '';

    #[Validate('required|in:medicine_name,symptoms')]
    public string $analysisType = 'medicine_name';

    // --- Стан ---
    public string $state = 'idle'; // idle | loading | result | error | validation_error | validation_error_medicine

    public ?array $result = null;
    public ?string $errorMessage = null;
    public ?string $validationWarning = null;

    protected function rules(): array
    {
        return [
            'query'             => ['required', 'string', 'max:500',
                function ($attribute, $value, $fail) {
                    // Дозволяємо літери, цифри, пробіли, апострофи та розділові знаки
                    if ($this->analysisType === 'medicine_name' && !preg_match('/^[\p{L}\p{N}\s.\',\-\/]+$/u', $value)) {
                        $fail('Назва препарату містить неприпустимі символи.');
                    } elseif ($this->analysisType === 'symptoms' && !preg_match('/^[\p{L}\p{N}\s.\',\-\/]+$/u', $value)) {
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
    /**
     * Клінічні повідомлення про помилки валідації українською мовою
     */
    protected function messages(): array
    {
        return [
            'query.required' => 'Поле введення є обов\'язковим для заповнення.',
            'age.required'   => 'Поле введення є обов\'язковим для заповнення.',
            'age.integer'    => 'Вік пацієнта має бути вказаний цілим числом.',
            'age.min'        => 'Вік пацієнта не може бути меншим за 0 років.',
            'age.max'        => 'Вік пацієнта не може перевищувати 120 років.',
        ];
    }

    public function submit(): void
    {
        $this->validate(); //
        set_time_limit(180); // Gemini може відповідати довго

        $this->query = trim($this->query);
        if (!empty($this->query)) {
            $this->query = mb_strtoupper(mb_substr($this->query, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($this->query, 1, null, 'UTF-8');
        }

        $this->state = 'loading'; // 
        $this->result = null; // 
        $this->errorMessage = null; // 
        $this->validationWarning = null; // 

        try {
            // Нормалізуємо запит для точного регістронезалежного аналізу
            $cleanQuery = trim($this->query);
            $lowerQuery = mb_strtolower($cleanQuery);

            $gemini = app(\App\Services\AI\GeminiService::class); 
            
            if ($this->analysisType === 'medicine_name') { 
                
                            $drugExistsInDb = \App\Models\Medicine::whereRaw('LOWER(name) LIKE ?', ['%' . $lowerQuery . '%'])->exists();

                // Якщо препарату немає в БД, тільки тоді верифікуємо його назву через Gemini
                if (!$drugExistsInDb && !$gemini->isMedicineName($cleanQuery)) { 
                    $this->validationWarning = 'Ви вказали симптоми або опис замість назви препарату. Якщо хочете знайти препарат за симптомами, оберіть відповідний тип аналізу у випадаючому списку.';
                    $this->state = 'validation_error'; // [cite: 19]
                    return; // [cite: 19]
                }
                
            } elseif ($this->analysisType === 'symptoms') { // 
                // Перевіряємо, що введено симптоми, а не назву препарату
                if ($gemini->isMedicineName($cleanQuery)) { // 
                    $this->validationWarning = 'Ви вказали назву препарату замість опису симптомів. Якщо хочете знайти препарат за назвою, оберіть відповідний тип аналізу у випадаючому списку.'; 
                    $this->state = 'validation_error_medicine'; // 
                    return; 
                }
            }

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
                    'age'               => $this->age, 
                    'is_pregnant'       => $this->isPregnant,  
                    'contraindications' => $this->contraindications, 
                    'current_medications' => $this->currentMedications, 
                ]; 
                // Шукаємо додаткові аналоги в локальній БД за діючими речовинами
                // (вирішує проблему асиметрії: Піколакс ↔ Слабілакс і навпаки)
                $aiFoundNames = array_column($recommendations, 'name'); 
                $localAnalogues = $details->findLocalAnalogues( 
                    $medicine['active_ingredients'] ?? [], 
                    $medicine['name'], 
                    $aiFoundNames 
                ); 
                // Об'єднуємо AI-аналоги і локальні (локальні додаються в кінець перед анаізом)
                $allRecommendations = array_merge($recommendations, $localAnalogues); 
                // Повний аналіз: хімічна/симптоматична схожість + smart_score з урахуванням обмежень пацієнта
                $filtered = $analyzer->analyze($allRecommendations, $medicine, $patientContext, $this->analysisType); 
                // Для основного препарату — перевіряємо обмеження через той самий сервіс
                $medicine['age_allowed']             = $analyzer->checkAgeAllowed($medicine['min_age'] ?? '', $this->age); 
                $medicine['contraindication_matches'] = $analyzer->checkContraindications($medicine['contraindications'] ?? [], $this->contraindications); 

                // =========================================================================
                // 🧠 ІНТЕЛЕКТУАЛЬНА ФІЛЬТРАЦІЯ ОДДРУКІВ ТА ВНУТРІШНІХ ДУБЛІВ ЛІКІВ
                // =========================================================================
                $normalizeWord = function ($text) { 
                    if (!$text) return ''; 
                    $firstWord = explode(' ', trim(mb_strtolower($text)))[0]; 
                    $firstWord = preg_replace('/[^\p{L}\p{N}]/u', '', $firstWord); 
                    
                    $cyr = ['а','б','в','г','д','е','є','ж','з','и','і','ї','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч','ш','щ','ь','ю','я']; 
                    $lat = ['a','b','v','h','d','e','ye','zh','z','y','i','yi','y','k','l','m','n','o','p','r','s','t','u','f','kh','ts','ch','sh','shch','','yu','ya']; 
                    $result = str_replace($cyr, $lat, $firstWord); 
                    
                    // Уніфікуємо схожі латинські фонеми (c/k, g/h) для склеювання крос-мовних дублів
                    return str_replace(['k', 'h', 'y', 'c', 'x'], ['c', 'g', 'i', 's', 'kh'], $result); 
                }; 

                $mainNormalized = $normalizeWord($medicine['name'] ?? ''); 

                // Етап А: Фільтруємо аналоги щодо головного препарату
                $filtered = array_filter($filtered, function($rec) use ($mainNormalized, $normalizeWord) { 
                    $recNormalized = $normalizeWord($rec['name'] ?? ''); 
                    if (empty($recNormalized) || empty($mainNormalized)) return true; 
                    return levenshtein($mainNormalized, $recNormalized) > 2; 
                }); 
                // Етап Б: Видаляємо дублікати МІЖ самими аналогами (напр. Кардіомагніл та Cardiomagnyl)
                $seenAnalogs = []; 
                $filtered = array_filter($filtered, function($rec) use ($normalizeWord, &$seenAnalogs) { 
                    $recNormalized = $normalizeWord($rec['name'] ?? ''); 
                    foreach ($seenAnalogs as $seen) { 
                        if (levenshtein($seen, $recNormalized) <= 2) { 
                            return false;  
                        }
                    } 
                    $seenAnalogs[] = $recNormalized; 
                    return true; 
                }); 
                $filtered = array_values($filtered); 

                // =========================================================================
                // 🧠 АВТОМАТИЧНЕ ВИЯВЛЕННЯ КОНФЛІКТІВ ДЛЯ ГОЛОВНОГО ПРЕПАРАТУ
                // =========================================================================
                $medicine['interaction_matches'] = []; 
                $medicine['interaction_details'] = []; 

                if (!empty($this->currentMedications)) {
                    foreach ($filtered as $rec) { 
                        if (($rec['match_exact'] ?? 0) == 100) { 
                            foreach ($rec['smart_reasons'] ?? [] as $reason) {
                                $textLower = mb_strtolower($reason['text'] ?? '');
                                if (str_contains($textLower, 'взаємоді') || str_contains($textLower, 'несумісн') || str_contains($textLower, 'конфлікт') || str_contains($textLower, 'подвоєн')) {
                            
                                    // 🛡️ ЗАПОБІЖНИК: Якщо фраза містить заперечення конфлікту, ігноруємо її
                                    if (str_contains($textLower, 'не виявлено') || str_contains($textLower, 'відсутн') || str_contains($textLower, 'не знайдено') || str_contains($textLower, 'немає')) {
                                        continue;
                                    }

                                    $medicine['interaction_matches'][] = $this->currentMedications;

                                    // Очищаємо назву аналога та його латинські/кириличні синоніми всередині речення
                                    $analogFirstWord = explode(' ', trim($rec['name']))[0]; 
                                    $cleanText = str_ireplace($analogFirstWord, $medicine['name'], $reason['text']); 
                                    
                                    $synonymsMap = [ 
                                        'cardiomagnyl' => 'Кардіомагніл', 'кардіомагніл' => 'Cardiomagnyl', 
                                        'enap' => 'Енап', 'енап' => 'Enap', 'nimesil' => 'Німесил', 'німесил' => 'Nimesil'
                                    ]; 
                                    $analogFirstWordLower = mb_strtolower($analogFirstWord); 
                                    if (isset($synonymsMap[$analogFirstWordLower])) { 
                                        $cleanText = str_ireplace($synonymsMap[$analogFirstWordLower], $medicine['name'], $cleanText); 
                                    } 
                                    
                                    $medicine['interaction_details'][] = $cleanText; 
                                } 
                            }
                        }
                    } 
                    $medicine['interaction_matches'] = array_values(array_unique($medicine['interaction_matches'])); 
                    $medicine['interaction_details'] = array_values(array_unique($medicine['interaction_details'])); 
                }
                // =========================================================================

                $this->result = [ 
                    'medicine'        => $medicine, 
                    'recommendations' => $filtered, 
                ]; 
            } elseif ($this->analysisType === 'symptoms') { 
                // Пошук аналогів за симптомами
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
                    'age'               => $this->age, 
                    'is_pregnant'       => $this->isPregnant, 
                    'contraindications' => $this->contraindications, 
                    'current_medications' => $this->currentMedications, 
                ]; 
                // Шукаємо додаткові аналоги в локальній БД за діючими речовинами
                $aiFoundNames = array_column($recommendations, 'name'); 
                $localAnalogues = $details->findLocalAnalogues( 
                    [], // Немає діючих речовин для пошуку локальних аналогів 
                    '', // Немає основного препарату для виключення 
                    $aiFoundNames 
                ); 
                // Об'єднуємо AI-аналоги і локальні (локальні додаються в кінець перед аналізом)
                $allRecommendations = array_merge($recommendations, $localAnalogues); 
                // Повний аналіз: хімічна/симптоматична схожість + smart_score з урахуванням обмежень пацієнта
                $filtered = $analyzer->analyze($allRecommendations, $medicine, $patientContext, $this->analysisType); 
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
        $this->validationWarning = null;
    }

    /**
     * Перемикає на пошук за симптомами і запускає аналіз.
     */
    public function searchBySymptoms(): void
    {
        $this->analysisType = 'symptoms';
        $this->validationWarning = null;
        $this->submit();
    }

    /**
     * Перемикає на пошук за назвою препарату і запускає аналіз.
     */
    public function searchByMedicineName(): void
    {
        $this->analysisType = 'medicine_name';
        $this->validationWarning = null;
        $this->submit();
    }

    public function render()
    {
        return view('livewire.medicine.analyze-form');
    }
}
