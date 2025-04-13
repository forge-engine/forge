<?php

declare(strict_types=1);

namespace App\Tests;

use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Skip;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use Forge\Core\Http\Response;

#[Group('http')]
final class HomeTest extends TestCase
{
    #[Test('Home / route is working')]
    #[Skip('Waiting on TestHttpService implementation')]
    public function home_route_is_ok(): void
    {
        /*** @var Response $response */
        //$response = $this->get('/');
        //$this->assertHttpStatus(200, $response);
    }

    #[Test]
    #[Skip('Waiting on TestHttpService implementation')]
    public function register_route_returns_redirect_on_validation_error(): void
    {
        // Test implementation
    }

    #[Test]
    #[Skip('Waiting on TestHttpService implementation')]
    public function update_user_route_returns_unauthorized_if_not_authenticated(): void
    {
        /** @var Response $response */
        //$response = $this->patch('/1', ['username' => 'newuser', 'email' => 'new@example.com']);
        // Assuming your AuthMiddleware returns a 403 or redirects
        //$this->assertHttpStatus(403, $response);
    }
}
