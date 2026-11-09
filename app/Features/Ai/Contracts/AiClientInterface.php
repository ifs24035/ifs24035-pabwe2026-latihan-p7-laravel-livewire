<?php

namespace App\Features\Ai\Contracts;

use App\Features\Ai\DTO\GeneratedTodoItem;

interface AiClientInterface
{
    /**
     * Generate multiple structured todo items based on a prompt.
     *
     * @return array<int, GeneratedTodoItem>
     */
    public function generateTodos(string $prompt, int $count = 3): array;
}

