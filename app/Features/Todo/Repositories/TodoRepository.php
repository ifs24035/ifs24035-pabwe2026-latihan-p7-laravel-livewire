<?php

namespace App\Features\Todo\Repositories;

use App\Features\Todo\Contracts\TodoRepositoryInterface;
use App\Features\Todo\Models\Todo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class TodoRepository implements TodoRepositoryInterface
{
    /**
     * Get paginated todos for a given user with optional status and search filters.
     */
    public function forUser(int $userId, ?string $status = null, ?string $search = null, int $perPage = 10): LengthAwarePaginator
    {
        $query = Todo::query()->where('user_id', $userId);

        if (!empty($status)) {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->latest('id')->paginate($perPage);
    }

    /**
     * Get all todos for a given user without pagination.
     *
     * @return Collection<int, Todo>
     */
    public function allForUser(int $userId): Collection
    {
        return Todo::query()
            ->where('user_id', $userId)
            ->latest('id')
            ->get();
    }

    /**
     * Find a todo by id.
     */
    public function find(int $id): ?Todo
    {
        return Todo::find($id);
    }

    /**
     * Find a todo by id or throw an exception.
     */
    public function findOrFail(int $id): Todo
    {
        return Todo::findOrFail($id);
    }

    /**
     * Create a new todo.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Todo
    {
        return Todo::create($attributes);
    }

    /**
     * Update an existing todo.
     *
     * @param array<string, mixed> $attributes
     */
    public function update(Todo $todo, array $attributes): bool
    {
        return $todo->update($attributes);
    }

    /**
     * Delete an existing todo.
     */
    public function delete(Todo $todo): bool
    {
        return (bool) $todo->delete();
    }

    /**
     * Insert multiple todos at once.
     *
     * @param array<int, array<string, mixed>> $records
     */
    public function insertMany(array $records): bool
    {
        if (empty($records)) {
            return true;
        }

        return Todo::insert($records);
    }
}

