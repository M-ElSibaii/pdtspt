<?php

use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\Support\PdtFixtures as F;

/*
 * Process (a): who reaches the admin. Built from the live route table, so a route added
 * later without the admin middleware fails here instead of shipping open.
 */

function audit_admin_routes(): array
{
    $out = [];
    foreach (Route::getRoutes() as $r) {
        if (in_array(AdminMiddleware::class, Route::gatherRouteMiddleware($r), true)) {
            $uri = preg_replace(
                ['/\{guid\}/', '/\{entityType\}/', '/\{[a-zA-Z]+\}/'],
                [str_repeat('a', 32), 'pdt', '1'],
                $r->uri()
            );
            $method = collect($r->methods())->first(fn($m) => $m !== 'HEAD');
            $out[] = [$method, '/' . ltrim($uri, '/')];
        }
    }

    return $out;
}

function audit_table_counts(): array
{
    $t = ['productdatatemplates', 'groupofproperties', 'properties', 'propertiesdatadictionaries',
        'referencedocuments', 'constructionobjects', 'users', 'entity_relationships'];

    return collect($t)->mapWithKeys(fn($x) => [$x => DB::table($x)->count()])->all();
}

test('every /admin route carries the admin middleware', function () {
    $open = [];
    foreach (Route::getRoutes() as $r) {
        $mw = Route::gatherRouteMiddleware($r);
        if (str_starts_with($r->uri(), 'admin') && !in_array(AdminMiddleware::class, $mw, true)) {
            $open[] = $r->uri();
        }
    }
    expect($open)->toBe([]);
    expect(count(audit_admin_routes()))->toBeGreaterThan(50);
});

test('guests are sent to login from every admin route and nothing is written', function () {
    F::pdt([1]);
    $before = audit_table_counts();
    $wrong = [];
    foreach (audit_admin_routes() as [$method, $uri]) {
        $res = $this->call($method, $uri);
        if (!$res->isRedirect(route('login'))) {
            $wrong[] = "{$method} {$uri} -> {$res->status()}";
        }
    }
    expect($wrong)->toBe([]);
    expect(audit_table_counts())->toBe($before);
});

test('logged-in non-admins are refused on every admin route and nothing is written', function () {
    F::pdt([1]);
    $this->actingAs(F::user());
    $before = audit_table_counts();
    $wrong = [];
    foreach (audit_admin_routes() as [$method, $uri]) {
        $res = $this->call($method, $uri, ['attrs' => ['pdtNameEn' => 'hijack'], 'isAdmin' => [1 => 1]]);
        if (!$res->isRedirect(url('/'))) {
            $wrong[] = "{$method} {$uri} -> {$res->status()}";
        }
    }
    expect($wrong)->toBe([]);
    expect(audit_table_counts())->toBe($before);
});

test('an admin whose e-mail is not verified is refused', function () {
    $admin = F::admin();
    $admin->forceFill(['email_verified_at' => null])->save();
    $this->actingAs($admin)->get('/admin')->assertRedirect(route('verification.notice'));
});

test('an admin reaches the hub', function () {
    $this->actingAs(F::admin())->get('/admin')->assertOk();
});

test('only the known public forms accept writes without a login', function () {
    $public = [];
    foreach (Route::getRoutes() as $r) {
        $writes = array_diff($r->methods(), ['GET', 'HEAD', 'OPTIONS']);
        $mw = Route::gatherRouteMiddleware($r);
        $authed = collect($mw)->contains(fn($m) => str_starts_with($m, \App\Http\Middleware\Authenticate::class));
        // Ignition's routes are the dev error page's, registered only with APP_DEBUG on.
        if ($writes && !$authed && !str_starts_with($r->uri(), '_ignition')) {
            $public[] = implode('|', $writes) . ' ' . $r->uri();
        }
    }
    sort($public);
    expect($public)->toBe([
        'POST contact', 'POST forgot-password', 'POST login', 'POST pdt-export/json/{pdtId}',
        'POST pdt-export/xml/{pdtId}', 'POST register', 'POST reset-password',
    ]);
});

test('the public API is read-only and rate-limited', function () {
    foreach (Route::getRoutes() as $r) {
        if ($r->uri() !== 'api' && !str_starts_with($r->uri(), 'api/')) {
            continue;
        }
        expect(array_values(array_diff($r->methods(), ['GET', 'HEAD'])))->toBe([], $r->uri());
        expect($r->gatherMiddleware())->toContain('api');
    }
    expect(app(\App\Http\Kernel::class)->getMiddlewareGroups()['api'])->toContain('throttle:api');
    $this->postJson('/api/productDataTemplates')->assertStatus(405);
});

test('admin forms are CSRF-protected (no admin route is exempt)', function () {
    $except = (fn() => $this->except)->call(app(\App\Http\Middleware\VerifyCsrfToken::class));
    expect($except)->toBe([]);
    expect(app(\App\Http\Kernel::class)->getMiddlewareGroups()['web'])
        ->toContain(\App\Http\Middleware\VerifyCsrfToken::class);
});
