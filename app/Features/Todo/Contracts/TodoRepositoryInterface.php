<?php

namespace App\Features\Todo\Contracts;

use App\Features\Todo\Models\Todo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface TodoRepositoryInterface
{
    /**
     * Get paginated todos for a given user with optional status and search filters.
     */
    public function forUser(int $userId, ?string $status = null, ?string $search = null, int $perPage = 10): LengthAwarePaginator;

    /**
     * Get all todos for a given user without pagination.
     *
     * @return Collection<int, Todo>
     */
    public function allForUser(int $userId): Collection;

    /**
     * Find a todo by id.
     */
    public function find(int $id): ?Todo;

    /**
     * Find a todo by id or throw an exception.
     */
    public function findOrFail(int $id): Todo;

    /**
     * Create a new todo.
     *
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): Todo;

    /**
     * Update an existing todo.
     *
     * @param array<string, mixed> $attributes
     */
    public function update(Todo $todo, array $attributes): bool;

    /**
     * Delete an existing todo.
     */
    public function delete(Todo $todo): bool;

    /**
     * Insert multiple todos at once.
     *
     * @param array<int, array<string, mixed>> $records
     */
    public function insertMany(array $records): bool;
}

