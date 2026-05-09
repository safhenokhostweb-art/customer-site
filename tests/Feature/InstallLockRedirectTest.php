<?php

namespace Tests\Feature;

use App\Http\Middleware\RedirectIfNotInstalled;
use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class InstallLockRedirectTest extends TestCase
{
    protected function tearDown(): void
    {
        if (! is_file(storage_path('installed.lock'))) {
            file_put_contents(storage_path('installed.lock'), '1');
        }
        parent::tearDown();
    }

    public function test_guest_is_redirected_to_install_wizard_when_lock_missing(): void
    {
        @unlink(storage_path('installed.lock'));

        $middleware = app(RedirectIfNotInstalled::class);
        $request = Request::create('/', 'GET');
        $response = $middleware->handle($request, fn () => response('ok', 200));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('install.welcome'), $response->headers->get('Location'));
    }

    public function test_authenticated_user_passes_when_lock_missing(): void
    {
        @unlink(storage_path('installed.lock'));

        $user = new User(['id' => 1, 'name' => 'Test']);
        $user->exists = true;

        $middleware = app(RedirectIfNotInstalled::class);
        $request = Request::create('/', 'GET');
        $request->setUserResolver(static fn () => $user);

        $response = $middleware->handle($request, fn () => response('ok', 200));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getContent());
    }
}
