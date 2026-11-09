<?php

namespace Tests\Unit\Middleware;

use App\Features\Todo\Models\Todo;
use App\Http\Middleware\EnsureTodoOwnership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class EnsureTodoOwnershipMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocks_unauthenticated_request(): void
    {
        $middleware = new EnsureTodoOwnership();
        $request = Request::create('/todos', 'GET');

        $this->expectException(HttpException::class);
        $middleware->handle($request, fn () => new Response('OK'));
    }

    public function test_route_user_parameter_ownership(): void
    {
        $middleware = new EnsureTodoOwnership();
        $member1 = User::factory()->member()->create();
        $member2 = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();

        // Member accessing own
        $req1 = Request::create('/admin/users/'.$member1->id.'/todos', 'GET');
        $req1->setUserResolver(fn () => $member1);
        $req1->setRouteResolver(fn () => new class($member1) {
            public function __construct(public $user) {}
            public function parameter($name) { return $this->user; }
            public function route($name) { return $this->user; }
        });
        // We can bind route parameters using Laravel's Route
        $req1 = Request::create('/test', 'GET');
        $req1->setUserResolver(fn () => $member1);
        $route = new \Illuminate\Routing\Route('GET', '/test/{user}', []);
        $route->bind($req1);
        $route->setParameter('user', $member1);
        $req1->setRouteResolver(fn () => $route);

        $res1 = $middleware->handle($req1, fn () => new Response('OK'));
        $this->assertEquals(200, $res1->getStatusCode());

        // Member accessing other user -> 403
        $req2 = Request::create('/test', 'GET');
        $req2->setUserResolver(fn () => $member1);
        $route2 = new \Illuminate\Routing\Route('GET', '/test/{user}', []);
        $route2->bind($req2);
        $route2->setParameter('user', $member2);
        $req2->setRouteResolver(fn () => $route2);

        try {
            $middleware->handle($req2, fn () => new Response('OK'));
            $this->fail('Expected HttpException 403');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }

        // Admin accessing other user -> allowed
        $req3 = Request::create('/test', 'GET');
        $req3->setUserResolver(fn () => $admin);
        $route3 = new \Illuminate\Routing\Route('GET', '/test/{user}', []);
        $route3->bind($req3);
        $route3->setParameter('user', $member2);
        $req3->setRouteResolver(fn () => $route3);

        $res3 = $middleware->handle($req3, fn () => new Response('OK'));
        $this->assertEquals(200, $res3->getStatusCode());
    }

    public function test_route_todo_parameter_ownership(): void
    {
        $middleware = new EnsureTodoOwnership();
        $member1 = User::factory()->member()->create();
        $member2 = User::factory()->member()->create();
        $admin = User::factory()->admin()->create();

        $todo1 = Todo::factory()->create(['user_id' => $member1->id]);

        // Member1 accessing own todo GET
        $req1 = Request::create('/test', 'GET');
        $req1->setUserResolver(fn () => $member1);
        $route1 = new \Illuminate\Routing\Route('GET', '/test/{todo}', []);
        $route1->bind($req1);
        $route1->setParameter('todo', $todo1);
        $req1->setRouteResolver(fn () => $route1);

        $res1 = $middleware->handle($req1, fn () => new Response('OK'));
        $this->assertEquals(200, $res1->getStatusCode());

        // Member2 accessing Member1's todo GET -> 403
        $req2 = Request::create('/test', 'GET');
        $req2->setUserResolver(fn () => $member2);
        $route2 = new \Illuminate\Routing\Route('GET', '/test/{todo}', []);
        $route2->bind($req2);
        $route2->setParameter('todo', $todo1);
        $req2->setRouteResolver(fn () => $route2);

        try {
            $middleware->handle($req2, fn () => new Response('OK'));
            $this->fail('Expected HttpException 403');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }

        // Admin accessing Member1's todo GET -> allowed
        $req3 = Request::create('/test', 'GET');
        $req3->setUserResolver(fn () => $admin);
        $route3 = new \Illuminate\Routing\Route('GET', '/test/{todo}', []);
        $route3->bind($req3);
        $route3->setParameter('todo', $todo1);
        $req3->setRouteResolver(fn () => $route3);

        $res3 = $middleware->handle($req3, fn () => new Response('OK'));
        $this->assertEquals(200, $res3->getStatusCode());

        // Admin attempting to MODIFY Member1's todo (PUT/DELETE) -> 403
        $req4 = Request::create('/test', 'DELETE');
        $req4->setUserResolver(fn () => $admin);
        $route4 = new \Illuminate\Routing\Route('DELETE', '/test/{todo}', []);
        $route4->bind($req4);
        $route4->setParameter('todo', $todo1);
        $req4->setRouteResolver(fn () => $route4);

        try {
            $middleware->handle($req4, fn () => new Response('OK'));
            $this->fail('Expected HttpException 403');
        } catch (HttpException $e) {
            $this->assertEquals(403, $e->getStatusCode());
        }
    }
}

