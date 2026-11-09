<?php

namespace App\Features\Admin\Services;

use App\Features\Todo\Contracts\TodoRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserService
{
    public function __construct(
        protected TodoRepositoryInterface $todoRepository
    ) {}

    /**
     * Get paginated list of all users with their todos count.
     */
    public function getUsersList(?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = User::query()->withCount('todos');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Get read-only list of todos for a specific target user.
     */
    public function getUserTodos(int $userId, ?string $status = null, ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        return $this->todoRepository->forUser($userId, $status, $search, $perPage);
    }
}

