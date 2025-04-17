<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Tests;

use App\Modules\ForgeAuth\Models\User;
use App\Modules\ForgeTesting\Attributes\DataProvider;
use App\Modules\ForgeTesting\Attributes\Group;
use App\Modules\ForgeTesting\Attributes\Incomplete;
use App\Modules\ForgeTesting\Attributes\Skip;
use App\Modules\ForgeTesting\Attributes\Test;
use App\Modules\ForgeTesting\TestCase;
use Forge\Core\Database\QueryBuilder;
use Forge\Core\DI\Container;

#[Group('auth')]
final class AuthenticationTest extends TestCase
{
    private QueryBuilder $queryBuilder;
    private array $exampleUser = [];

    public function __construct()
    {
        $this->queryBuilder = Container::getInstance()->get(QueryBuilder::class);
        $this->exampleUser = [
            'identifier' => 'example',
            'email' => 'test@example.com',
            'password' => password_hash('test1234', PASSWORD_BCRYPT),
            'status' => 'active',
            'metadata' => json_encode([]),
        ];
    }

    #[Test('User login functionality')]
    #[Skip('Waiting on implementation')]
    public function login_works(): void
    {
        $this->assertTrue(true);
    }

    #[Test]
    #[Group('smtp')]
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

    #[Test('Insert a new record in the database')]
    #[Group('database')]
    #[Incomplete('Needs to check save performance in the model')]
    public function create_user(): void
    {
        $user = new User();
        $user->identifier = $this->exampleUser['identifier'];
        $user->password = password_hash($this->exampleUser['password'], PASSWORD_BCRYPT);
        $user->email = $this->exampleUser['email'];
        $user->status = $this->exampleUser['status'];
        $user->metadata = [];
        $user->save();
        $this->assertNotNull($user->id);
    }

    #[Test('Check a record exists in the database by identifier')]
    #[Group('database')]
    public function user_exists(): void
    {
        $this->assertDatabaseHas('users', ['identifier' => $this->exampleUser['identifier']]);
    }

    #[DataProvider('userProvider')]
    #[Test]
    #[Group('database')]
    public function multiple_users(array $users): void
    {
        $this->assertArrayHasKey('email', $users);
    }

    #[Test('Benchmark user lookup')]
    #[Group('database')]
    public function benchmark_user_lookup(): array
    {
        $results = $this->benchmark(function () {
            $this->assertDatabaseHas('users', ['email' => $this->exampleUser['email']]);
        }, 1);

        return $results;
    }

    #[Test('Delete user from the database by using email')]
    #[Group('database')]
    #[Skip('Needs to check delete performance in the model')]
    public function delete_user(): void
    {
        $user = $this->queryBuilder->reset()
        ->where('email', '=', $this->exampleUser['email'])
        ->setTable('users')->delete();
        $this->assertTrue($user ? true : false);
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
            $dataProvider[] = [['email' => $user['email']]];
        }

        return $dataProvider;
    }
}
