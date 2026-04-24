<?php

use App\Http\Controllers\MedicineController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::view('medicine/analyze', 'medicine.analyze')
    ->middleware(['auth', 'verified'])
    ->name('medicine.analyze');

Route::get('medicine/{id}/instructions', [MedicineController::class, 'instructions'])
    ->name('medicine.instructions');

require __DIR__ . '/auth.php';

/**
 * TODO
 * Основні принципи. Всі дані діючих речовин тільки на латині
 * Виміри в міліграмах, або мілілітрах
 *
 *
 * [✅ DONE] - Створити форму для отримання вхідних даних (Livewire: App\Livewire\Medicine\AnalyzeForm)
 *      [✅] - input (text) для введення назви препарату або симптомів
 *      [✅] - input (number) для введення віку пацієнта
 *      [✅] - input (checkbox) для вибору флажка вагітності
 *      [✅] - input (text) для введення алергій, або протипоказань
 *      [✅] - input (select) для вибору типу аналізу enum ['назва препарату', 'симптоми']
 *      [✅] - submit -> dd() даних (тимчасово)
 *
 * [✅ DONE] - Якщо введено тип препарату (назва)
 *      [✅] - ЗАПИТ 1: MedicineDetailsService::getMedicineDetails() → деталі + симптоми препарату
 *      [✅] - ЗАПИТ 2: MedicineDetailsService::getRecommendationsBySymptoms() → семантичний пошук по симптомах
 *      [✅] - MedicineAnalyzeService::analyze() → порівняння діючих речовин (локально, без AI)
 *      [✅] - Генерація результату на сторінці з посиланнями на інструкції
 *
 * [✅ DONE] - Створення таблиці medicines (id, name unique+index, instructions_html)
 * [✅ DONE] - Публічний маршрут /medicine/{id}/instructions → 404 якщо немає
 *
 * [✅ DONE] - MedicineDetailsService
 *      [✅] - getSymptoms() через GeminiService
 *      [✅] - findOrSaveInstructions() — перевірка БД + збереження
 *
 * [✅ DONE] - AI/GeminiService
 *      [✅] - ask(prompt, systemInstruction) → string
 *      [✅] - askJson(prompt, systemInstruction) → array
 *
 * [✅ DONE] - MedicineAnalyzeService
 *      [✅] - analyze(recommendations[], initialMedicine) → відфільтровані за % збігом
 */
