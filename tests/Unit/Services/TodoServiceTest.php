<?php

namespace Tests\Unit\Services;

use App\Features\Todo\Contracts\TodoRepositoryInterface;
use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Models\Todo;
use App\Features\Todo\Repositories\TodoRepository;
use App\Features\Todo\Services\TodoService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TodoService $service;
    protected TodoRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TodoRepository();
        $this->service = new TodoService($this->repository);
    }

    public function test_get_todos_for_user(): void
    {
        $user = User::factory()->create();
        Todo::factory()->count(2)->create(['user_id' => $user->id]);

        $paginator = $this->service->getTodosForUser($user->id);
        $this->assertCount(2, $paginator);
    }

    public function test_create_todo(): void
    {
        $user = User::factory()->create();
        $todo = $this->service->createTodo($user->id, [
            'title' => 'Service Todo',
            'description' => 'Desc',
            'status' => 'pending',
            'due_date' => '2026-11-20',
        ]);

        $this->assertInstanceOf(Todo::class, $todo);
        $this->assertEquals('Service Todo', $todo->title);
        $this->assertEquals('pending', $todo->status);
    }

    public function test_update_todo(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id, 'title' => 'Old Title']);

        $updated = $this->service->updateTodo($todo, [
            'title' => 'New Title',
            'description' => 'Updated Desc',
        ]);

        $this->assertTrue($updated);
        $todo->refresh();
        $this->assertEquals('New Title', $todo->title);
        $this->assertEquals('Updated Desc', $todo->description);
    }

    public function test_delete_todo(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $deleted = $this->service->deleteTodo($todo);
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_toggle_complete(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id, 'status' => TodoStatus::PENDING->value]);

        // Toggle from pending to completed
        $this->service->toggleComplete($todo);
        $todo->refresh();
        $this->assertEquals(TodoStatus::COMPLETED->value, $todo->status);

        // Toggle from completed back to pending
        $this->service->toggleComplete($todo);
        $todo->refresh();
        $this->assertEquals(TodoStatus::PENDING->value, $todo->status);
    }

    public function test_cover_photo_management(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $fakeFile = \Illuminate\Http\UploadedFile::fake()->image('cover.jpg');

        // Update cover
        $path = $this->service->updateCoverPhoto($todo, $fakeFile);
        $todo->refresh();
        $this->assertEquals($path, $todo->cover_image_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($path);

        // Update cover again (replaces old)
        $newFakeFile = \Illuminate\Http\UploadedFile::fake()->image('new-cover.jpg');
        $newPath = $this->service->updateCoverPhoto($todo, $newFakeFile);
        $todo->refresh();
        $this->assertEquals($newPath, $todo->cover_image_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($newPath);

        // Delete cover
        $deleted = $this->service->deleteCoverPhoto($todo);
        $this->assertTrue($deleted);
        $todo->refresh();
        $this->assertNull($todo->cover_image_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($newPath);

        // Delete todo cleans up cover photo if present
        $anotherFile = \Illuminate\Http\UploadedFile::fake()->image('another.jpg');
        $anotherPath = $this->service->updateCoverPhoto($todo, $anotherFile);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($anotherPath);

        $this->service->deleteTodo($todo);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($anotherPath);
    }
}

