<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use RuntimeException;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model');
        $this->baseUrl = config('services.gemini.base_url');

        if (empty($this->apiKey)) {
            throw new RuntimeException('GEMINI_TOKEN is not set in .env');
        }
    }

    /**
     * Текстовий запит до Gemini.
     */
    public function ask(string $prompt, ?string $model = null): string
    {
        $body = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.1],
        ];

        $text = $this->extractText($this->sendRequest($model ?? $this->model, $body));

        return $text ?? throw new RuntimeException('Gemini не повернув текстову відповідь.');
    }

    /**
     * Запит до Gemini — повертає розпарсений JSON-масив.
     * responseMimeType примушує Gemini повертати чистий JSON без markdown.
     */
    public function askJson(
        string $prompt,
        ?string $systemInstruction = null,
        ?string $model = null,
    ): array {
        $body = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.1,
                'responseMimeType' => 'application/json',
            ],
        ];

        if ($systemInstruction) {
            $body['system_instruction'] = [
                'parts' => [['text' => $systemInstruction]],
            ];
        }

        $text = $this->extractText($this->sendRequest($model ?? $this->model, $body));

        if ($text === null) {
            throw new RuntimeException('Gemini не повернув відповідь.');
        }

        $decoded = json_decode($text, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Gemini повернув невалідний JSON: ' . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Відправляє запит до Gemini API з retry на 429/500/503.
     */
    private function sendRequest(string $model, array $body): array
    {
        $url = "{$this->baseUrl}/models/{$model}:generateContent?key={$this->apiKey}";

        $response = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout(120)
            ->retry(3, 2000, function (\Exception $e) {
                return $e instanceof ConnectionException
                    || (method_exists($e, 'response') && in_array($e->response?->status(), [429, 500, 503]));
            })
            ->post($url, $body);

        if ($response->failed()) {
            throw new RuntimeException(
                'Помилка запиту до Gemini API: ' . $response->status() . ' — ' . $response->body()
            );
        }

        return $response->json();
    }

    /**
     * Витягує текст із відповіді Gemini.
     */
    private function extractText(array $data): ?string
    {
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }
}
