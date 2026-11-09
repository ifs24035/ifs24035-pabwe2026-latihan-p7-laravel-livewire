<?php

namespace Tests\Unit\DTO;

use App\Features\Ai\DTO\GeneratedTodoItem;
use PHPUnit\Framework\TestCase;

class GeneratedTodoItemTest extends TestCase
{
    public function test_dto_properties_and_to_array(): void
    {
        $dto = new GeneratedTodoItem(
            title: 'Tugas Kuliah',
            description: 'Mengerjakan tugas praktikum 7',
            dueDate: '2026-11-10',
            status: 'pending'
        );

        $this->assertEquals('Tugas Kuliah', $dto->title);
        $this->assertEquals('Mengerjakan tugas praktikum 7', $dto->description);
        $this->assertEquals('2026-11-10', $dto->dueDate);
        $this->assertEquals('pending', $dto->status);

        $array = $dto->toArray();
        $this->assertEquals([
            'title' => 'Tugas Kuliah',
            'description' => 'Mengerjakan tugas praktikum 7',
            'due_date' => '2026-11-10',
            'status' => 'pending',
        ], $array);
    }
}

