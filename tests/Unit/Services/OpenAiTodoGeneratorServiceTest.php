<?php

namespace Tests\Unit\Services;

use App\Features\Ai\Services\OpenAiTodoGeneratorService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use RuntimeException;

class OpenAiTodoGeneratorServiceTest extends TestCase
{
    public function test_empty_prompt_throws_exception(): void
    {
        $service = new OpenAiTodoGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prompt tidak boleh kosong.');
        $service->generateTodos('   ');
    }

    public function test_successful_generation(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'todos' => [
                                    [
                                        'title' => 'Tugas 1',
                                        'description' => 'Deskripsi 1',
                                        'due_date' => '2026-11-15',
                                    ],
                                    [
                                        'title' => 'Tugas 2',
                                        'description' => null,
                                        'due_date' => 'invalid-date',
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new OpenAiTodoGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');
        $results = $service->generateTodos('Buatkan 2 tugas', 2);

        $this->assertCount(2, $results);
        $this->assertEquals('Tugas 1', $results[0]->title);
        $this->assertEquals('Deskripsi 1', $results[0]->description);
        $this->assertEquals('2026-11-15', $results[0]->dueDate);

        $this->assertEquals('Tugas 2', $results[1]->title);
        $this->assertNull($results[1]->description);
        $this->assertNull($results[1]->dueDate);
    }

    public function test_api_failure_throws_exception(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'error' => [
                    'message' => 'Invalid API Key provided',
                ],
            ], 401),
        ]);

        $service = new OpenAiTodoGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API Error: Invalid API Key provided');
        $service->generateTodos('Rencana belajar');
    }

    public function test_parse_content_with_markdown_fences(): void
    {
        $service = new OpenAiTodoGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');
        $raw = "```json\n[{\"title\": \"Code Fence Todo\", \"description\": \"Test desc\"}]\n```";

        $results = $service->parseContent($raw);
        $this->assertCount(1, $results);
        $this->assertEquals('Code Fence Todo', $results[0]->title);
    }

    public function test_parse_content_invalid_json(): void
    {
        $service = new OpenAiTodoGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AI mengembalikan format respon yang tidak valid.');
        $service->parseContent('Not valid JSON');
    }

    public function test_parse_content_empty_todos(): void
    {
        $service = new OpenAiTodoGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tidak ada todo yang berhasil di-generate dari AI.');
        $service->parseContent('{"todos": []}');
    }

    public function test_parse_content_no_valid_items(): void
    {
        $service = new OpenAiTodoGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Format item todo yang dikembalikan AI tidak sesuai spesifikasi.');
        $service->parseContent('{"todos": [{"bad_key": "val"}]}');
    }

    public function test_constructor_handles_swapped_endpoint_and_key(): void
    {
        Http::fake([
            'https://ai.example.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '{"todos":[{"title":"Tugas"}]}']]],
            ]),
        ]);

        // Key given as URL, endpoint given as secret
        $service = new OpenAiTodoGeneratorService('sk-secret-key', 'https://ai.example.com/v1');
        $todos = $service->generateTodos('Rencana');
        $this->assertCount(1, $todos);
    }

    public function test_constructor_auto_prefixes_https(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '{"todos":[{"title":"Tugas"}]}']]],
            ]),
        ]);

        $service = new OpenAiTodoGeneratorService('api.openai.com/v1', 'mock-key');
        $todos = $service->generateTodos('Rencana');
        $this->assertCount(1, $todos);
    }
}
