<?php

namespace App\Http\Middleware;

use App\Features\Todo\Models\Todo;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTodoOwnership
{
    /**
     * Handle an incoming request ensuring members do not access other users' data.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $currentUser = $request->user();

        if (!$currentUser) {
            abort(401, 'Unauthenticated');
        }

        // Check if route has {user} parameter
        $routeUser = $request->route('user');
        if ($routeUser) {
            $targetUserId = $routeUser instanceof User ? $routeUser->id : (int) $routeUser;

            if ($currentUser->isMember() && $currentUser->id !== $targetUserId) {
                abort(403, 'Akses ditolak: Member tidak dapat mengakses todolist orang lain.');
            }
        }

        // Check if route has {todo} parameter
        $routeTodo = $request->route('todo');
        if ($routeTodo) {
            $todo = $routeTodo instanceof Todo ? $routeTodo : Todo::find($routeTodo);

            if ($todo) {
                // If member, must strictly own the todo
                if ($currentUser->isMember() && $currentUser->id !== $todo->user_id) {
                    abort(403, 'Akses ditolak: Member tidak dapat mengakses todolist orang lain.');
                }

                // If write method (PUT, PATCH, DELETE), even admin cannot modify other users' todos
                if ($request->isMethodSafe() === false && $currentUser->id !== $todo->user_id) {
                    abort(403, 'Akses ditolak: Hanya pemilik todolist yang dapat mengubah todolist ini.');
                }
            }
        }

        return $next($request);
    }
}

