<?php

namespace Tests\Feature\Todo;

use App\Features\Todo\Livewire\TodoDetail;
use App\Features\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class TodoDetailLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_todo_detail(): void
    {
        $todo = Todo::factory()->create();

        $this->get(route('todos.show', $todo->id))
            ->assertRedirect(route('login'));
    }

    public function test_member_cannot_access_other_member_todo(): void
    {
        $owner = User::factory()->member()->create();
        $otherMember = User::factory()->member()->create();

        $todo = Todo::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($otherMember);

        $this->get(route('todos.show', $todo->id))
            ->assertStatus(403);
    }

    public function test_member_can_view_own_todo_detail(): void
    {
        $user = User::factory()->member()->create();
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'title' => 'Tugas Utama Pro',
            'description' => '# Deskripsi Markdown',
            'status' => 'pending',
            'due_date' => '2026-11-30',
        ]);

        $this->actingAs($user);

        Livewire::test(TodoDetail::class, ['todo' => $todo])
            ->assertOk()
            ->assertSet('title', 'Tugas Utama Pro')
            ->assertSet('description', '# Deskripsi Markdown')
            ->assertSet('status', 'pending')
            ->assertSet('dueDate', '2026-11-30')
            ->assertSee('Tugas Utama Pro');
    }

    public function test_open_and_close_edit_modal(): void
    {
        $user = User::factory()->member()->create();
        $todo = Todo::factory()->create([
            'user_id' => $user->id,
            'title' => 'Judul Detail',
            'description' => 'Deskripsi lama',
            'status' => 'pending',
        ]);

        $this->actingAs($user);

        Livewire::test(TodoDetail::class, ['todo' => $todo])
            ->assertSet('showEditModal', false)
            ->call('openEditModal')
            ->assertSet('showEditModal', true)
            ->assertSet('title', 'Judul Detail')
            ->assertSet('description', 'Deskripsi lama')
            ->call('closeEditModal')
            ->assertSet('showEditModal', false);
    }

    public function test_update_todo_validation_and_success(): void
    {
        $user = User::factory()->member()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id, 'title' => 'Judul Awal']);

        $this->actingAs($user);

        // Validation errors
        Livewire::test(TodoDetail::class, ['todo' => $todo])
            ->call('openEditModal')
            ->set('title', 'ab') // min:3
            ->set('status', 'invalid_status')
            ->call('saveTodo')
            ->assertHasErrors(['title', 'status']);

        // Success
        Livewire::test(TodoDetail::class, ['todo' => $todo])
            ->call('openEditModal')
            ->set('title', 'Judul Telah Diperbarui')
            ->set('description', '## Rincian Baru')
            ->set('status', 'in_progress')
            ->set('dueDate', '2026-12-15')
            ->call('saveTodo')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'title' => 'Judul Telah Diperbarui',
            'description' => '## Rincian Baru',
            'status' => 'in_progress',
        ]);
    }

    public function test_toggle_complete(): void
    {
        $user = User::factory()->member()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        $this->actingAs($user);

        Livewire::test(TodoDetail::class, ['todo' => $todo])
            ->call('toggleComplete')
            ->assertSet('status', 'completed');

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'status' => 'completed',
        ]);
    }

    public function test_cover_photo_upload_and_delete(): void
    {
        Storage::fake('public');

        $user = User::factory()->member()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id, 'cover_image_path' => null]);

        $this->actingAs($user);

        // Invalid file
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100);
        Livewire::test(TodoDetail::class, ['todo' => $todo])
            ->set('coverPhoto', $invalidFile)
            ->assertHasErrors(['coverPhoto']);

        // Valid image upload
        $imageFile = UploadedFile::fake()->image('cover.jpg', 800, 400);
        $component = Livewire::test(TodoDetail::class, ['todo' => $todo])
            ->set('coverPhoto', $imageFile)
            ->assertHasNoErrors();

        $todo->refresh();
        $this->assertNotNull($todo->cover_image_path);
        Storage::disk('public')->assertExists($todo->cover_image_path);

        // Delete cover photo
        $component->call('deleteCoverPhoto');

        $todo->refresh();
        $this->assertNull($todo->cover_image_path);
    }

    public function test_delete_todo_redirects(): void
    {
        $user = User::factory()->member()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        Livewire::test(TodoDetail::class, ['todo' => $todo])
            ->call('deleteTodo')
            ->assertRedirect(route('todos.index'));

        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_confirm_and_cancel_delete_modal_actions(): void
    {
        $user = User::factory()->member()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user);

        Livewire::test(TodoDetail::class, ['todo' => $todo])
            ->assertSet('confirmingTodoDeletion', false)
            ->call('confirmDeleteTodo')
            ->assertSet('confirmingTodoDeletion', true)
            ->call('cancelDeleteTodo')
            ->assertSet('confirmingTodoDeletion', false)
            ->assertSet('confirmingCoverDeletion', false)
            ->call('confirmDeleteCover')
            ->assertSet('confirmingCoverDeletion', true)
            ->call('cancelDeleteCover')
            ->assertSet('confirmingCoverDeletion', false);
    }
}

