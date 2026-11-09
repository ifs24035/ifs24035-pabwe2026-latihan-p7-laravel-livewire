<?php

namespace App\Features\Todo\Livewire;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Models\Todo;
use App\Features\Todo\Services\TodoExcelService;
use App\Features\Todo\Services\TodoService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
#[Title('My Todolist - TodoApp')]
class TodoList extends Component
{
    use WithPagination, WithFileUploads;

    // Filters
    public string $search = '';
    public string $statusFilter = '';

    // Create / Edit Modal State
    public bool $showFormModal = false;
    public ?int $editingTodoId = null;
    public string $formTitle = '';
    public ?string $formDescription = null;
    public string $formStatus = 'pending';
    public ?string $formDueDate = null;

    // Delete Confirmation Modal State
    public ?int $confirmingTodoDeletionId = null;

    // Excel Import State
    public bool $showImportModal = false;
    public mixed $importFile = null;

    // AI Generator State
    public bool $showAiModal = false;
    public string $aiPrompt = '';
    public int $aiCount = 3;
    public array $aiGeneratedItems = [];
    public array $aiSelectedIndices = [];
    public bool $aiIsGenerating = false;
    public ?string $aiErrorMessage = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    // --- CRUD Operations ---

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function openEditModal(int $id): void
    {
        $todo = Todo::findOrFail($id);
        Gate::authorize('update', $todo);

        $this->resetErrorBag();
        $this->editingTodoId = $todo->id;
        $this->formTitle = $todo->title;
        $this->formDescription = $todo->description;
        $this->formStatus = $todo->status;
        $this->formDueDate = $todo->due_date ? Carbon::parse($todo->due_date)->format('Y-m-d') : null;
        $this->showFormModal = true;
    }

    public function closeFormModal(): void
    {
        // Tutup dulu tanpa reset berat agar response terasa instan.
        $this->showFormModal = false;
    }

    public function resetForm(): void
    {
        $this->reset(['editingTodoId', 'formTitle', 'formDescription', 'formDueDate']);
        $this->formStatus = TodoStatus::PENDING->value;
        $this->resetErrorBag();
    }

    public function saveTodo(TodoService $todoService): void
    {
        $this->validate([
            'formTitle' => ['required', 'string', 'max:255'],
            'formDescription' => ['nullable', 'string'],
            'formStatus' => ['required', 'in:pending,in_progress,completed'],
            'formDueDate' => ['nullable', 'date'],
        ]);

        $data = [
            'title' => $this->formTitle,
            'description' => $this->formDescription,
            'status' => $this->formStatus,
            'due_date' => $this->formDueDate,
        ];

        if ($this->editingTodoId) {
            $todo = Todo::findOrFail($this->editingTodoId);
            Gate::authorize('update', $todo);
            $todoService->updateTodo($todo, $data);
            session()->flash('success', 'Todo berhasil diperbarui.');
        } else {
            $todoService->createTodo(Auth::id(), $data);
            session()->flash('success', 'Todo baru berhasil ditambahkan.');
        }

        $this->closeFormModal();
    }

    public function toggleComplete(int $id, TodoService $todoService): void
    {
        $todo = Todo::findOrFail($id);
        Gate::authorize('update', $todo);

        $todoService->toggleComplete($todo);
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmingTodoDeletionId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmingTodoDeletionId = null;
    }

    public function deleteTodo(?int $id = null, ?TodoService $todoService = null): void
    {
        $todoService = $todoService ?? app(TodoService::class);
        $targetId = $id ?? $this->confirmingTodoDeletionId;

        if ($targetId) {
            $todo = Todo::findOrFail($targetId);
            Gate::authorize('delete', $todo);

            $todoService->deleteTodo($todo);
            $this->confirmingTodoDeletionId = null;
            session()->flash('success', 'Todo berhasil dihapus.');
        }
    }

    // --- Excel Export & Import ---

    public function exportExcel(TodoExcelService $excelService): StreamedResponse
    {
        $userId = Auth::id();
        $user = Auth::user();
        $todos = $user ? $user->todos()->latest('id')->get() : collect();

        return $excelService->exportToResponse($todos, 'todolist-'.now()->format('Ymd-His').'.xlsx');
    }

    public function openImportModal(): void
    {
        $this->reset(['importFile']);
        $this->showImportModal = true;
    }

    public function closeImportModal(): void
    {
        $this->showImportModal = false;
    }

    public function importExcel(TodoExcelService $excelService): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $path = $this->importFile->getRealPath();
        $result = $excelService->import($path, Auth::id());

        $this->closeImportModal();
        session()->flash('success', "Berhasil mengimpor {$result['imported']} item todolist.");
    }

    // --- AI Todo Generator ---

    public function openAiModal(): void
    {
        $this->reset(['aiPrompt', 'aiGeneratedItems', 'aiSelectedIndices', 'aiErrorMessage', 'aiIsGenerating']);
        $this->aiCount = 3;
        $this->showAiModal = true;
    }

    public function closeAiModal(): void
    {
        $this->showAiModal = false;
    }

    public function generateWithAi(AiClientInterface $aiClient): void
    {
        $this->validate([
            'aiPrompt' => ['required', 'string', 'min:3', 'max:500'],
            'aiCount' => ['required', 'integer', 'min:1'],
        ]);

        $this->aiIsGenerating = true;
        $this->aiErrorMessage = null;

        try {
            $items = $aiClient->generateTodos($this->aiPrompt, $this->aiCount);
            $this->aiGeneratedItems = array_map(fn ($item) => $item->toArray(), $items);
            $this->aiSelectedIndices = array_keys($this->aiGeneratedItems);
        } catch (\Throwable $e) {
            $this->aiErrorMessage = $e->getMessage();
        } finally {
            $this->aiIsGenerating = false;
        }
    }

    public function saveAiGenerated(TodoService $todoService): void
    {
        if (empty($this->aiSelectedIndices) || empty($this->aiGeneratedItems)) {
            $this->aiErrorMessage = 'Pilih minimal satu item todo yang ingin disimpan.';
            return;
        }

        $savedCount = 0;
        foreach ($this->aiSelectedIndices as $index) {
            if (isset($this->aiGeneratedItems[$index])) {
                $item = $this->aiGeneratedItems[$index];
                $todoService->createTodo(Auth::id(), [
                    'title' => $item['title'],
                    'description' => $item['description'] ?? null,
                    'status' => TodoStatus::PENDING->value,
                    'due_date' => $item['due_date'] ?? null,
                ]);
                $savedCount++;
            }
        }

        $this->closeAiModal();
        session()->flash('success', "Berhasil menambahkan {$savedCount} todo yang digenerate oleh AI.");
    }

    public function render(TodoService $todoService): View
    {
        $todos = $todoService->getTodosForUser(
            Auth::id(),
            $this->statusFilter ?: null,
            $this->search ?: null,
            8
        );

        return view('livewire.todo.todo-list', [
            'todos' => $todos,
            'statuses' => TodoStatus::cases(),
        ]);
    }
}

