<?php

namespace Tests\Feature\Admin;

use App\Features\Admin\Livewire\AdminUserTodoList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class AdminLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_forbidden_from_admin_pages(): void
    {
        $member = User::factory()->member()->create();
        $targetUser = User::factory()->member()->create();

        $this->actingAs($member)
            ->get('/admin/users')
            ->assertStatus(403);

        $this->actingAs($member)
            ->get("/admin/users/{$targetUser->id}/todos")
            ->assertStatus(403);
    }

    public function test_admin_can_view_user_list(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@test.com']);
        User::factory()->create(['name' => 'Citra Lestari', 'email' => 'citra@test.com']);

        $this->actingAs($admin);

        Livewire::test(UserList::class)
            ->assertSee('Budi Santoso')
            ->assertSee('Citra Lestari')
            ->set('search', 'Budi')
            ->assertSee('Budi Santoso')
            ->assertDontSee('Citra Lestari');
    }

    public function test_admin_can_view_user_todos_read_only(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create(['name' => 'Dewi Sartika']);

        Todo::factory()->create(['user_id' => $member->id, 'title' => 'Tugas Matematika', 'status' => 'pending']);
        Todo::factory()->create(['user_id' => $member->id, 'title' => 'Tugas Fisika', 'status' => 'completed']);

        $this->actingAs($admin);

        Livewire::test(AdminUserTodoList::class, ['user' => $member])
            ->assertSee('Dewi Sartika')
            ->assertSee('Tugas Matematika')
            ->assertSee('Tugas Fisika')
            ->set('search', 'Matematika')
            ->assertSee('Tugas Matematika')
            ->assertDontSee('Tugas Fisika')
            ->set('search', '')
            ->set('statusFilter', 'completed')
            ->assertSee('Tugas Fisika')
            ->assertDontSee('Tugas Matematika');
    }

    public function test_admin_can_export_user_todos(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create(['name' => 'Eko Prasetyo']);
        Todo::factory()->create(['user_id' => $member->id, 'title' => 'Tugas Export Admin']);

        $this->actingAs($admin);

        Livewire::test(AdminUserTodoList::class, ['user' => $member])
            ->call('exportExcel')
            ->assertFileDownloaded();
    }
}
