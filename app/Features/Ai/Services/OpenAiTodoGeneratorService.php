<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedTodoItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiTodoGeneratorService implements AiClientInterface
{
    protected string $endpoint;
    protected string $apiKey;
    protected string $model;

    public function __construct(
        ?string $endpoint = null,
        ?string $apiKey = null,
        ?string $model = null,
    ) {
        $resolvedEndpoint = (string) ($endpoint ?? config('openai.endpoint', 'https://api.openai.com/v1'));
        $resolvedKey = (string) ($apiKey ?? config('openai.key', ''));

        // Toleransi jika nilai OPENAI_ENDPOINT dan OPENAI_KEY tertukar di .env
        if ((str_starts_with($resolvedKey, 'http://') || str_starts_with($resolvedKey, 'https://')) &&
            (!str_starts_with($resolvedEndpoint, 'http://') && !str_starts_with($resolvedEndpoint, 'https://'))) {
            $temp = $resolvedEndpoint;
            $resolvedEndpoint = $resolvedKey;
            $resolvedKey = $temp;
        }

        // Pastikan scheme http:// atau https:// selalu ada
        if (!empty($resolvedEndpoint) && !str_starts_with($resolvedEndpoint, 'http://') && !str_starts_with($resolvedEndpoint, 'https://')) {
            $resolvedEndpoint = 'https://' . $resolvedEndpoint;
        }

        $this->endpoint = rtrim($resolvedEndpoint, '/');
        $this->apiKey = $resolvedKey;
        $this->model = $model ?? (string) config('openai.model', 'gpt-4o-mini');
    }

    /**
     * Generate multiple structured todo items based on a user prompt.
     *
     * @return array<int, GeneratedTodoItem>
     *
     * @throws RuntimeException
     */
    public function generateTodos(string $prompt, int $count = 3): array
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            throw new RuntimeException('Prompt tidak boleh kosong.');
        }

        $count = max(1, $count);
        $today = Carbon::today()->format('Y-m-d');

        $systemPrompt = <<<SYS
You are an expert productivity assistant. Your task is to generate exactly {$count} realistic, actionable, and structured todo items based on the user's request.
Today's date is {$today}.

You MUST respond strictly with a valid JSON object matching this schema:
{
  "todos": [
    {
      "title": "Clear concise task title (max 100 chars)",
      "description": "Brief helpful actionable details about how to complete it",
      "due_date": "YYYY-MM-DD or null"
    }
  ]
}

Do not include any conversational text, introductory words, or explanations outside the JSON.
SYS;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout(30)->post("{$this->endpoint}/chat/completions", [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
        ]);

        if (!$response->successful()) {
            $errorMessage = $response->json('error.message') ?? "Request failed with status code {$response->status()}";
            throw new RuntimeException("OpenAI API Error: {$errorMessage}");
        }

        $rawContent = (string) $response->json('choices.0.message.content');
        return $this->parseContent($rawContent);
    }

    /**
     * Parse raw string content into an array of GeneratedTodoItem.
     *
     * @return array<int, GeneratedTodoItem>
     */
    public function parseContent(string $rawContent): array
    {
        $cleanContent = trim($rawContent);

        // Remove Markdown code fences if present
        if (str_starts_with($cleanContent, '```')) {
            $cleanContent = preg_replace('/^```(?:json)?\s*/i', '', $cleanContent);
            $cleanContent = preg_replace('/\s*```$/', '', $cleanContent);
            $cleanContent = trim((string) $cleanContent);
        }

        $decoded = json_decode($cleanContent, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('AI mengembalikan format respon yang tidak valid.');
        }

        // Support both {"todos": [...]} and [...]
        $items = isset($decoded['todos']) && is_array($decoded['todos'])
            ? $decoded['todos']
            : (array_is_list($decoded) ? $decoded : []);

        if (empty($items)) {
            throw new RuntimeException('Tidak ada todo yang berhasil di-generate dari AI.');
        }

        $results = [];
        foreach ($items as $item) {
            if (!is_array($item) || empty($item['title'])) {
                continue;
            }

            $title = trim((string) $item['title']);
            $description = isset($item['description']) && trim((string) $item['description']) !== ''
                ? trim((string) $item['description'])
                : null;

            $dueDate = null;
            if (!empty($item['due_date'])) {
                try {
                    $dueDate = Carbon::parse((string) $item['due_date'])->format('Y-m-d');
                } catch (\Throwable) {
                    $dueDate = null;
                }
            }

            $results[] = new GeneratedTodoItem(
                title: $title,
                description: $description,
                dueDate: $dueDate,
                status: 'pending',
            );
        }

        if (empty($results)) {
            throw new RuntimeException('Format item todo yang dikembalikan AI tidak sesuai spesifikasi.');
        }

        return $results;
    }
}

