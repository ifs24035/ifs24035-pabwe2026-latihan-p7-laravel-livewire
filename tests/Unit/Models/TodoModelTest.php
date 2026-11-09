<?php

namespace Tests\Unit\Models;

use App\Features\Todo\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_todo_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $todo->user);
        $this->assertEquals($user->id, $todo->user->id);
    }

    public function test_todo_casts_due_date(): void
    {
        $todo = Todo::factory()->create(['due_date' => '2026-12-31']);
        $this->assertInstanceOf(Carbon::class, $todo->due_date);
        $this->assertEquals('2026-12-31', $todo->due_date->format('Y-m-d'));
    }

    public function test_todo_factory_states(): void
    {
        $completedTodo = Todo::factory()->completed()->create();
        $this->assertEquals('completed', $completedTodo->status);

        $inProgressTodo = Todo::factory()->inProgress()->create();
        $this->assertEquals('in_progress', $inProgressTodo->status);
    }

    public function test_cover_image_url_returns_correct_url_or_null(): void
    {
        $todoWithoutCover = Todo::factory()->create(['cover_image_path' => null]);
        $this->assertNull($todoWithoutCover->coverImageUrl());

        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('todo-covers/sample.png', 'content');

        $todoWithCover = Todo::factory()->create(['cover_image_path' => 'todo-covers/sample.png']);
        $this->assertNotNull($todoWithCover->coverImageUrl());
        $this->assertStringContainsString('todo-covers/sample.png', $todoWithCover->coverImageUrl());
    }
}

