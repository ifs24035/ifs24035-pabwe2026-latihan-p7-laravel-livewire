<?php

namespace Tests\Feature\Todo;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedTodoItem;
use App\Features\Todo\Livewire\TodoList;
use App\Features\Todo\Models\Todo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class TodoListLivewireTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_todos(): void
    {
        $this->get('/todos')->assertRedirect(route('login'));
    }

    public function test_member_can_view_own_todos(): void
    {
        $user = User::factory()->member()->create();
        Todo::factory()->create(['user_id' => $user->id, 'title' => 'Tugas Spesifik Member']);

        $otherUser = User::factory()->member()->create();
        Todo::factory()->create(['user_id' => $otherUser->id, 'title' => 'Tugas Rahasia Orang Lain']);

        $this->actingAs($user);

        Livewire::test(TodoList::class)
            ->assertSee('Tugas Spesifik Member')
            ->assertDontSee('Tugas Rahasia Orang Lain');
    }

    public function test_create_todo_validation_and_success(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        // Validation error
        Livewire::test(TodoList::class)
            ->call('openCreateModal')
            ->set('formTitle', '')
            ->set('formStatus', 'invalid_status')
            ->call('saveTodo')
            ->assertHasErrors(['formTitle', 'formStatus']);

        // Success
        Livewire::test(TodoList::class)
            ->call('openCreateModal')
            ->set('formTitle', 'Todo Baru')
            ->set('formDescription', 'Deskripsi todo')
            ->set('formStatus', 'pending')
            ->set('formDueDate', '2026-11-20')
            ->call('saveTodo')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false);

        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'title' => 'Todo Baru',
            'description' => 'Deskripsi todo',
        ]);
    }

    public function test_edit_todo_success(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $todo = Todo::factory()->create(['user_id' => $user->id, 'title' => 'Judul Lama']);

        Livewire::test(TodoList::class)
            ->call('openEditModal', $todo->id)
            ->assertSet('editingTodoId', $todo->id)
            ->assertSet('formTitle', 'Judul Lama')
            ->set('formTitle', 'Judul Telah Diedit')
            ->call('saveTodo')
            ->assertHasNoErrors()
            ->assertSet('showFormModal', false);

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'title' => 'Judul Telah Diedit',
        ]);
    }

    public function test_toggle_complete(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $todo = Todo::factory()->create(['user_id' => $user->id, 'status' => 'pending']);

        Livewire::test(TodoList::class)
            ->call('toggleComplete', $todo->id);

        $this->assertEquals('completed', $todo->fresh()->status);
    }

    public function test_delete_todo(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $todo = Todo::factory()->create(['user_id' => $user->id]);

        Livewire::test(TodoList::class)
            ->call('deleteTodo', $todo->id);

        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_confirm_and_cancel_delete_todo(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $todo = Todo::factory()->create(['user_id' => $user->id]);

        Livewire::test(TodoList::class)
            ->assertSet('confirmingTodoDeletionId', null)
            ->call('confirmDelete', $todo->id)
            ->assertSet('confirmingTodoDeletionId', $todo->id)
            ->call('cancelDelete')
            ->assertSet('confirmingTodoDeletionId', null)
            ->call('confirmDelete', $todo->id)
            ->call('deleteTodo')
            ->assertSet('confirmingTodoDeletionId', null);

        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_search_and_status_filter(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        Todo::factory()->create(['user_id' => $user->id, 'title' => 'Makan Siang', 'status' => 'completed']);
        Todo::factory()->create(['user_id' => $user->id, 'title' => 'Makan Malam', 'status' => 'pending']);

        Livewire::test(TodoList::class)
            ->set('search', 'Siang')
            ->assertSee('Makan Siang')
            ->assertDontSee('Makan Malam')
            ->set('search', '')
            ->set('statusFilter', 'pending')
            ->assertSee('Makan Malam')
            ->assertDontSee('Makan Siang');
    }

    public function test_export_excel(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        Todo::factory()->create(['user_id' => $user->id, 'title' => 'Exportable Todo']);

        Livewire::test(TodoList::class)
            ->call('exportExcel')
            ->assertFileDownloaded();
    }

    public function test_import_excel(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'Title');
        $sheet->setCellValue('C1', 'Description');
        $sheet->setCellValue('D1', 'Status');
        $sheet->setCellValue('E1', 'Due Date');

        $sheet->setCellValue('A2', '1');
        $sheet->setCellValue('B2', 'Imported Excel Todo');
        $sheet->setCellValue('C2', 'Excel Desc');
        $sheet->setCellValue('D2', 'pending');
        $sheet->setCellValue('E2', '2026-12-05');

        $tempPath = tempnam(sys_get_temp_dir(), 'test_upload_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempPath);

        $fileContent = file_get_contents($tempPath);
        @unlink($tempPath);

        $uploadedFile = UploadedFile::fake()->createWithContent('todos.xlsx', $fileContent);

        Livewire::test(TodoList::class)
            ->call('openImportModal')
            ->assertSet('showImportModal', true)
            ->set('importFile', $uploadedFile)
            ->call('importExcel')
            ->assertSet('showImportModal', false);

        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'title' => 'Imported Excel Todo',
            'description' => 'Excel Desc',
        ]);
    }

    public function test_ai_generation_and_saving(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        // Mock AiClientInterface
        $mockAiClient = $this->createStub(AiClientInterface::class);
        $mockAiClient->method('generateTodos')->willReturn([
            new GeneratedTodoItem('AI Task 1', 'Desc 1', '2026-11-25'),
            new GeneratedTodoItem('AI Task 2', 'Desc 2', null),
        ]);
        $this->app->instance(AiClientInterface::class, $mockAiClient);

        // Test validation for prompt and count
        Livewire::test(TodoList::class)
            ->call('openAiModal')
            ->set('aiPrompt', '')
            ->set('aiCount', 0)
            ->call('generateWithAi')
            ->assertHasErrors(['aiPrompt', 'aiCount']);

        $component = Livewire::test(TodoList::class)
            ->call('openAiModal')
            ->assertSet('showAiModal', true)
            ->set('aiPrompt', 'Buatkan saya rencana')
            ->set('aiCount', 2)
            ->call('generateWithAi')
            ->assertCount('aiGeneratedItems', 2)
            ->assertCount('aiSelectedIndices', 2);

        // Try saving with no items selected
        $component->set('aiSelectedIndices', [])
            ->call('saveAiGenerated')
            ->assertSet('aiErrorMessage', 'Pilih minimal satu item todo yang ingin disimpan.');

        // Save selected items
        $component->set('aiSelectedIndices', [0, 1])
            ->call('saveAiGenerated')
            ->assertSet('showAiModal', false);

        $this->assertDatabaseHas('todos', ['user_id' => $user->id, 'title' => 'AI Task 1']);
        $this->assertDatabaseHas('todos', ['user_id' => $user->id, 'title' => 'AI Task 2']);
    }

    public function test_ai_generation_handles_exception(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        $mockAiClient = $this->createStub(AiClientInterface::class);
        $mockAiClient->method('generateTodos')->willThrowException(new \RuntimeException('API quota exceeded'));
        $this->app->instance(AiClientInterface::class, $mockAiClient);

        Livewire::test(TodoList::class)
            ->call('openAiModal')
            ->set('aiPrompt', 'Error test')
            ->call('generateWithAi')
            ->assertSet('aiErrorMessage', 'API quota exceeded');
    }

    public function test_close_modals(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user);

        Livewire::test(TodoList::class)
            ->call('openCreateModal')
            ->call('closeFormModal')
            ->assertSet('showFormModal', false)
            ->call('openImportModal')
            ->call('closeImportModal')
            ->assertSet('showImportModal', false)
            ->call('openAiModal')
            ->call('closeAiModal')
            ->assertSet('showAiModal', false);
    }
}
