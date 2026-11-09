<?php

namespace App\Features\Todo\Models;

use App\Models\User;
use Database\Factories\TodoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['user_id', 'title', 'description', 'status', 'due_date', 'cover_image_path'])]
class Todo extends Model
{
    /** @use HasFactory<TodoFactory> */
    use HasFactory;

    /**
     * Get the public URL for the cover image.
     */
    public function coverImageUrl(): ?string
    {
        if ($this->cover_image_path && Storage::disk('public')->exists($this->cover_image_path)) {
            return Storage::disk('public')->url($this->cover_image_path);
        }

        return null;
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'todos';

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
        ];
    }

    /**
     * Get the user that owns the todo.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): TodoFactory
    {
        return TodoFactory::new();
    }
}

