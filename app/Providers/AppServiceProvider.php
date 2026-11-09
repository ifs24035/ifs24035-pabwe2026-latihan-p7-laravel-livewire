<?php

namespace App\Providers;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\Services\OpenAiTodoGeneratorService;
use App\Features\Todo\Contracts\TodoRepositoryInterface;
use App\Features\Todo\Models\Todo;
use App\Features\Todo\Policies\TodoPolicy;
use App\Features\Todo\Repositories\TodoRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TodoRepositoryInterface::class, TodoRepository::class);
        $this->app->bind(AiClientInterface::class, OpenAiTodoGeneratorService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.force_https', false) || $this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::policy(Todo::class, TodoPolicy::class);

        RateLimiter::for('1000-per-second', function (Request $request) {
            return Limit::perSecond(1000)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('global', function (Request $request) {
            return Limit::perSecond(1000)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perSecond(1000)->by($request->user()?->id ?: $request->ip());
        });
    }
}
