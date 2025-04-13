<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Tests;

use App\Modules\ForgeTesting\Attributes\DataProvider;
use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Skip;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use Forge\Core\Database\QueryBuilder;
use Forge\Core\DI\Container;

#[Group('auth')]
final class AuthenticationTest extends TestCase
{
    private QueryBuilder $queryBuilder;

    public function __construct()
    {
        $this->queryBuilder = Container::getInstance()->get(QueryBuilder::class);
    }

    #[Test('User login functionality')]
    public function login_works(): void
    {
        $this->assertTrue(true);
    }

    #[Test]
    #[Skip('Waiting on SMTP implementation')]
    public function password_reset_email(): void
    {
        // Test implementation
    }

    #[Test]
    #[Skip('Need to implememnt 2FA checks')]
    public function two_factor_authentication(): void
    {
        // Partial implementation
    }

    #[Test]
    #[Group('database')]
    public function user_registration(): void
    {
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    #[DataProvider('userProvider')]
    #[Test]
    public function multiple_users(array $users): void
    {
        $this->assertArrayHasKey('email', $users);
    }

    #[Test('Benchmark user lookup')]
    #[Group('database')]
    public function benchmark_user_lookup(): array
    {
        $results = $this->benchmark(function () {
            $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        }, 100);

        return $results;
    }

    public function userProvider(): array
    {
        $users = $this->queryBuilder
        ->reset()
        ->setTable('users')
        ->select('*')
        ->limit(10)
        ->get(null);

        $dataProvider = [];
        foreach ($users as $user) {
            $dataProvider[] = [['email' => $user->email]];
        }

        return $dataProvider;
    }
}
