<?php

namespace App\Features\Admin\Livewire;

use App\Features\Admin\Services\AdminUserService;
use App\Features\Todo\Enums\TodoStatus;
use App\Features\Todo\Services\TodoExcelService;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('components.layouts.app')]
#[Title('User Todolist (Read Only) - Admin')]
class AdminUserTodoList extends Component
{
    use WithPagination;

    public User $user;
    public string $search = '';
    public string $statusFilter = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    public function mount(User $user): void
    {
        $this->user = $user;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function exportExcel(TodoExcelService $excelService): StreamedResponse
    {
        $todos = $this->user->todos()->latest('id')->get();
        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $this->user->name);

        return $excelService->exportToResponse($todos, "todolist-{$safeName}-".now()->format('Ymd-His').'.xlsx');
    }

    public function render(AdminUserService $adminService): View
    {
        $todos = $adminService->getUserTodos(
            $this->user->id,
            $this->statusFilter ?: null,
            $this->search ?: null,
            10
        );

        return view('livewire.admin.admin-user-todo-list', [
            'todos' => $todos,
            'statuses' => TodoStatus::cases(),
        ]);
    }
}

