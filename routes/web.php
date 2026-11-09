<?php

use App\Features\Admin\Livewire\AdminUserTodoList;
use App\Features\Admin\Livewire\UserList;
use App\Features\Auth\Livewire\Login;
use App\Features\Auth\Livewire\Register;
use App\Features\Todo\Livewire\TodoList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:1000-per-second'])->group(function () {
    // Root Redirection
    Route::get('/', function () {
        if (Auth::check()) {
            return Auth::user()->isAdmin()
                ? redirect()->route('admin.users')
                : redirect()->route('todos.index');
        }
        return redirect()->route('login');
    });

    // Guest Authentication Routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', Login::class)->name('login');
        Route::get('/register', Register::class)->name('register');
    });

    // Authenticated Routes
    Route::middleware('auth')->group(function () {
        Route::post('/logout', function (Request $request) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login');
        })->name('logout');

        // Member Todo Routes protected by ownership middleware
        Route::get('/todos', TodoList::class)
            ->middleware('owns.todo')
            ->name('todos.index');
        Route::get('/todos/{todo}', \App\Features\Todo\Livewire\TodoDetail::class)
            ->middleware('owns.todo')
            ->name('todos.show');

        // User Profile Route
        Route::get('/profile', \App\Features\Profile\Livewire\Profile::class)->name('profile');

        // Admin Routes protected by Role middleware
        Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
            Route::get('/users', UserList::class)->name('users');
            Route::get('/users/{user}/todos', AdminUserTodoList::class)->name('users.todos');
        });
    });
});
