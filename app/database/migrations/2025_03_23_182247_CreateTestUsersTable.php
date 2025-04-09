<?php

declare(strict_types=1);

use Forge\Core\Database\Attributes\Column;
use Forge\Core\Database\Attributes\Index;
use Forge\Core\Database\Attributes\Table;
use Forge\Core\Database\Migrations\Migration;

#[Table(name: 'test_users')]
#[Index(columns: ['email'], name: 'idx_test3_users_email')]
class CreateTestUsersTable extends Migration
{
    #[Column(name: 'id', type: 'INTEGER', primaryKey: true)]
    private string $id;

    #[Column(name: 'test_uuid', type: 'UUID', unique: true, nullable: false, autoIncrement: false, default: null, primaryKey: false)]
    private string $test_uuid;

    #[Column(name: 'status', type: 'ENUM', enum: ['active', 'inactive', 'pending'])]
    private string $status;

    #[Column(name: 'username', type: 'VARCHAR(255)', unique: true)]
    private string $username;

    #[Column(name: 'email', type: 'VARCHAR(255)', unique: true)]
    private string $email;

    #[Column(name: 'password', type: 'VARCHAR(255)')]
    private string $password;

    #[Column(name: 'created_at', type: 'TIMESTAMP', default: 'CURRENT_TIMESTAMP')]
    private ?string $created_at;

    #[Column(name: 'updated_at', type: 'TIMESTAMP', nullable: true)]
    private ?string $updated_at;

    #[Column(name: 'deleted_at', type: 'TIMESTAMP', nullable: true)]
    private ?string $deleted_at;
}
