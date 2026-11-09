<?php

namespace Tests\Unit\Services;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Todo\Models\Todo;
use App\Features\Todo\Repositories\TodoRepository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUserService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AdminUserService(new TodoRepository());
    }

    public function test_get_users_list_without_search(): void
    {
        User::factory()->count(3)->create();

        $list = $this->service->getUsersList();
        $this->assertCount(3, $list);
    }

    public function test_get_users_list_with_search(): void
    {
        User::factory()->create(['name' => 'Alice Wonder', 'email' => 'alice@test.com']);
        User::factory()->create(['name' => 'Bob Marley', 'email' => 'bob@test.com']);

        $resultsByName = $this->service->getUsersList(search: 'Alice');
        $this->assertCount(1, $resultsByName);

        $resultsByEmail = $this->service->getUsersList(search: 'bob@test.com');
        $this->assertCount(1, $resultsByEmail);

        User::factory()->create(['name' => 'Charlie Chaplin', 'username' => 'charlie_legend', 'email' => 'charlie@test.com']);
        $resultsByUsername = $this->service->getUsersList(search: 'charlie_legend');
        $this->assertCount(1, $resultsByUsername);
    }

    public function test_get_user_todos(): void
    {
        $user = User::factory()->create();
        Todo::factory()->count(4)->create(['user_id' => $user->id]);

        $todos = $this->service->getUserTodos($user->id);
        $this->assertCount(4, $todos);
    }
}

