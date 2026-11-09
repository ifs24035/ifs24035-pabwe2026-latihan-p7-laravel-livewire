<?php

namespace Tests\Unit\Policies;

use App\Features\Todo\Models\Todo;
use App\Features\Todo\Policies\TodoPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TodoPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_any(): void
    {
        $policy = new TodoPolicy();
        $user = User::factory()->create();

        $this->assertTrue($policy->viewAny($user));
    }

    public function test_view_policy(): void
    {
        $policy = new TodoPolicy();
        $owner = User::factory()->member()->create();
        $otherMember = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();

        $todo = Todo::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($policy->view($owner, $todo));
        $this->assertTrue($policy->view($admin, $todo));
        $this->assertFalse($policy->view($otherMember, $todo));
    }

    public function test_create_policy(): void
    {
        $policy = new TodoPolicy();
        $user = User::factory()->create();

        $this->assertTrue($policy->create($user));
    }

    public function test_update_policy(): void
    {
        $policy = new TodoPolicy();
        $owner = User::factory()->member()->create();
        $otherMember = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();

        $todo = Todo::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($policy->update($owner, $todo));
        $this->assertFalse($policy->update($otherMember, $todo));
        $this->assertFalse($policy->update($admin, $todo)); // Admin cannot edit other's todos
    }

    public function test_delete_policy(): void
    {
        $policy = new TodoPolicy();
        $owner = User::factory()->member()->create();
        $otherMember = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();

        $todo = Todo::factory()->create(['user_id' => $owner->id]);

        $this->assertTrue($policy->delete($owner, $todo));
        $this->assertFalse($policy->delete($otherMember, $todo));
        $this->assertFalse($policy->delete($admin, $todo)); // Admin cannot delete other's todos
    }
}

