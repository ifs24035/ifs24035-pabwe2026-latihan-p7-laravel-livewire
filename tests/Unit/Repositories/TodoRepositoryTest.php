<?php

namespace Tests\Unit\Repositories;

use App\Features\Todo\Models\Todo;
use App\Features\Todo\Repositories\TodoRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected TodoRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new TodoRepository();
    }

    public function test_create_and_find(): void
    {
        $user = User::factory()->create();
        $todo = $this->repository->create([
            'user_id' => $user->id,
            'title' => 'Test Todo',
            'description' => 'Test Desc',
            'status' => 'pending',
        ]);

        $this->assertInstanceOf(Todo::class, $todo);
        $this->assertDatabaseHas('todos', ['id' => $todo->id, 'title' => 'Test Todo']);

        $found = $this->repository->find($todo->id);
        $this->assertNotNull($found);
        $this->assertEquals($todo->id, $found->id);

        $foundOrFail = $this->repository->findOrFail($todo->id);
        $this->assertEquals($todo->id, $foundOrFail->id);
    }

    public function test_find_or_fail_throws_exception_when_not_found(): void
    {
        $this->expectException(ModelNotFoundException::class);
        $this->repository->findOrFail(9999);
    }

    public function test_update_and_delete(): void
    {
        $user = User::factory()->create();
        $todo = $this->repository->create([
            'user_id' => $user->id,
            'title' => 'Original',
        ]);

        $updated = $this->repository->update($todo, ['title' => 'Updated Title']);
        $this->assertTrue($updated);
        $this->assertDatabaseHas('todos', ['id' => $todo->id, 'title' => 'Updated Title']);

        $deleted = $this->repository->delete($todo);
        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_all_for_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Todo::factory()->count(3)->create(['user_id' => $user->id]);
        Todo::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $todos = $this->repository->allForUser($user->id);
        $this->assertCount(3, $todos);
    }

    public function test_for_user_with_filters(): void
    {
        $user = User::factory()->create();

        Todo::factory()->create(['user_id' => $user->id, 'title' => 'Belajar Laravel', 'status' => 'pending']);
        Todo::factory()->create(['user_id' => $user->id, 'title' => 'Belajar Vue', 'status' => 'completed']);
        Todo::factory()->create(['user_id' => $user->id, 'title' => 'Tidur Nyenyak', 'description' => 'Laravel rest', 'status' => 'completed']);

        // Test without filter
        $all = $this->repository->forUser($user->id);
        $this->assertCount(3, $all);

        // Test status filter
        $pending = $this->repository->forUser($user->id, status: 'pending');
        $this->assertCount(1, $pending);

        // Test search filter on title
        $searchTitle = $this->repository->forUser($user->id, search: 'Laravel');
        $this->assertCount(2, $searchTitle);

        // Test search filter on description
        $searchDesc = $this->repository->forUser($user->id, search: 'rest');
        $this->assertCount(1, $searchDesc);
    }

    public function test_insert_many(): void
    {
        $user = User::factory()->create();

        $emptyResult = $this->repository->insertMany([]);
        $this->assertTrue($emptyResult);

        $records = [
            ['user_id' => $user->id, 'title' => 'Batch 1', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $user->id, 'title' => 'Batch 2', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ];

        $result = $this->repository->insertMany($records);
        $this->assertTrue($result);
        $this->assertDatabaseCount('todos', 2);
    }
}

