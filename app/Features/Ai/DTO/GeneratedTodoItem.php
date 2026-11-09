<?php

namespace App\Features\Ai\DTO;

class GeneratedTodoItem
{
    public function __construct(
        public string $title,
        public ?string $description = null,
        public ?string $dueDate = null,
        public string $status = 'pending',
    ) {}

    /**
     * Convert the DTO to an associative array for persistence.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'due_date' => $this->dueDate,
            'status' => $this->status,
        ];
    }
}

