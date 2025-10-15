<?php

declare(strict_types=1);

use Forge\Core\Database\Attributes\Column;
use Forge\Core\Database\Attributes\GroupMigration;
use Forge\Core\Database\Attributes\SoftDelete;
use Forge\Core\Database\Attributes\Timestamps;
use Forge\Core\Database\Attributes\Index;
use Forge\Core\Database\Attributes\Table;
use Forge\Core\Database\Enums\ColumnType;
use Forge\Core\Database\Migrations\Migration;

#[GroupMigration(name: 'test')]
#[Table(name: 'test_users')]
#[Index(columns: ['email'], name: 'idx_test3_users_email')]
#[Timestamps]
#[SoftDelete]
class CreateTestUsersTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::INTEGER, primaryKey: true)]
    public readonly string $id;

    #[Column(name: 'test_uuid', type: ColumnType::UUID, nullable: false, default: null, unique: true, primaryKey: false, autoIncrement: false, length: 255)]
    public readonly string $test_uuid;

    #[Column(name: 'status', type: ColumnType::ENUM, enum: ['active', 'inactive', 'pending'])]
    public readonly string $status;

    #[Column(name: 'username', type: ColumnType::STRING, unique: true, length: 255)]
    public readonly string $username;

    #[Column(name: 'email', type: ColumnType::STRING, unique: true, length: 255)]
    public readonly string $email;

    #[Column(name: 'password', type: ColumnType::STRING, length: 255)]
    public readonly string $password;
}
