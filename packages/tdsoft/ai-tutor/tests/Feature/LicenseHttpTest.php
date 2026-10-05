<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TDSoft\AiTutor\Contracts\Entitlements;
use TDSoft\AiTutor\Contracts\LicenseAdministrator;
use TDSoft\AiTutor\Licensing\Http\RequireLicenseAdministrator;
use TDSoft\AiTutor\Licensing\Http\RequireModule;
use TDSoft\AiTutor\Licensing\InstallationIdentity;
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class LicenseHttpTest extends FoundationTestCase
{
    public function test_admin_routes_require_web_auth_and_administrator_and_are_outside_api_exemption(): void
    {
        $router = new Router($this->app['events'], $this->app);
        Route::swap($router);
        require __DIR__.'/../../routes/web.php';
        $this->assertCount(4, $router->getRoutes());
        foreach ($router->getRoutes() as $route) {
            $this->assertStringStartsWith('admin/ai/license', $route->uri());
            $this->assertContains('web', $route->gatherMiddleware());
            $this->assertContains('auth', $route->gatherMiddleware());
            $this->assertContains(RequireLicenseAdministrator::class, $route->gatherMiddleware());
        }
    }

    public function test_non_admin_is_denied_even_with_authenticated_session(): void
    {
        $guard = new class implements LicenseAdministrator
        {
            public function allows(): bool
            {
                return false;
            }

            public function actorId(): ?string
            {
                return 'student-1';
            }
        };
        $this->expectException(HttpException::class);
        (new RequireLicenseAdministrator($guard))->handle(Request::create('/admin/ai/license'), fn () => $this->fail('Must not reach controller'));
    }

    public function test_license_post_requires_csrf_despite_existing_api_wildcard_exception(): void
    {
        $this->app->instance('env', 'production'); // Disable Laravel's unit-test CSRF bypass.
        $middleware = new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected $except = ['api/*'];

            protected $addHttpCookie = false;
        };
        $session = new Store('license-test', new ArraySessionHandler(120));
        $session->put('_token', 'expected-token');
        $request = Request::create('https://lms.example.test/admin/ai/license/activate', 'POST');
        $request->setLaravelSession($session);
        try {
            $middleware->handle($request, fn () => response('unexpected'));
            $this->fail('Expected CSRF rejection');
        } catch (TokenMismatchException) {
            $this->addToAssertionCount(1);
        }
        $request->request->set('_token', 'expected-token');
        $this->assertSame('accepted', $middleware->handle($request, fn () => 'accepted'));
    }

    public function test_module_middleware_denies_unlicensed_module(): void
    {
        config(['app.url' => 'https://lms.example.test']);
        $entitlements = new class implements Entitlements
        {
            public function allows(string $module): bool
            {
                return false;
            }
        };
        try {
            (new RequireModule($entitlements, new InstallationIdentity))->handle(
                Request::create('https://lms.example.test/ai-tutor'), fn () => $this->fail('Denied module'),
                'ai_tutor_speaking'
            );
            $this->fail('Expected 403');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
    }
}
