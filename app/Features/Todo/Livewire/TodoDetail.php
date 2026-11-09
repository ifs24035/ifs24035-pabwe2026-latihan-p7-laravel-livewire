<?php

namespace App\Features\Todo\Livewire;

use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Models\Todo;
use App\Features\Todo\Services\TodoService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class TodoDetail extends Component
{
    use WithFileUploads;

    public Todo $todo;
    public string $title = '';
    public ?string $description = '';
    public string $status = 'pending';
    public ?string $dueDate = null;
    public mixed $coverPhoto = null;
    public bool $showEditModal = false;
    public bool $confirmingTodoDeletion = false;
    public bool $confirmingCoverDeletion = false;

    public function mount(Todo $todo): void
    {
        Gate::authorize('view', $todo);

        $this->todo = $todo;
        $this->title = $todo->title;
        $this->description = $todo->description ?? '';
        $this->status = $todo->status;
        $this->dueDate = $todo->due_date ? $todo->due_date->format('Y-m-d') : null;
    }

    public function openEditModal(): void
    {
        Gate::authorize('update', $this->todo);

        $this->title = $this->todo->title;
        $this->description = $this->todo->description ?? '';
        $this->status = $this->todo->status;
        $this->dueDate = $this->todo->due_date ? $this->todo->due_date->format('Y-m-d') : null;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
    }

    public function updatedCoverPhoto(TodoService $todoService): void
    {
        Gate::authorize('update', $this->todo);

        $this->validate([
            'coverPhoto' => ['required', 'image', 'max:5120'], // max 5MB
        ]);

        $todoService->updateCoverPhoto($this->todo, $this->coverPhoto);
        $this->todo->refresh();
        $this->reset('coverPhoto');

        session()->flash('success', 'Cover todo berhasil diperbarui.');
    }

    public function confirmDeleteCover(): void
    {
        $this->confirmingCoverDeletion = true;
    }

    public function cancelDeleteCover(): void
    {
        $this->confirmingCoverDeletion = false;
    }

    public function deleteCoverPhoto(TodoService $todoService): void
    {
        Gate::authorize('update', $this->todo);

        $todoService->deleteCoverPhoto($this->todo);
        $this->todo->refresh();
        $this->confirmingCoverDeletion = false;

        session()->flash('success', 'Cover todo berhasil dihapus.');
    }

    public function saveTodo(TodoService $todoService): void
    {
        Gate::authorize('update', $this->todo);

        $validated = $this->validate([
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(['pending', 'in_progress', 'completed'])],
            'dueDate' => ['nullable', 'date'],
        ]);

        $todoService->updateTodo($this->todo, [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'status' => $validated['status'],
            'due_date' => $validated['dueDate'],
        ]);

        $this->todo->refresh();
        $this->closeEditModal();

        session()->flash('success', 'Informasi todo berhasil disimpan.');
    }

    public function toggleComplete(TodoService $todoService): void
    {
        Gate::authorize('update', $this->todo);

        $todoService->toggleComplete($this->todo);
        $this->todo->refresh();
        $this->status = $this->todo->status;

        session()->flash('success', 'Status todo berhasil diperbarui.');
    }

    public function confirmDeleteTodo(): void
    {
        $this->confirmingTodoDeletion = true;
    }

    public function cancelDeleteTodo(): void
    {
        $this->confirmingTodoDeletion = false;
    }

    public function deleteTodo(TodoService $todoService): mixed
    {
        Gate::authorize('delete', $this->todo);

        $this->confirmingTodoDeletion = false;
        $todoService->deleteTodo($this->todo);

        session()->flash('success', 'Todo berhasil dihapus.');

        return redirect()->route('todos.index');
    }

    public function render(): mixed
    {
        return view('livewire.todo.todo-detail', [
            'statuses' => TodoStatus::cases(),
        ])->layout('components.layouts.app', ['title' => 'Detail Todo: ' . $this->todo->title]);
    }
}

