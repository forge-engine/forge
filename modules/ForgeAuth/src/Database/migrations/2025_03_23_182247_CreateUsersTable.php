<?php

declare(strict_types=1);

use Forge\Core\Database\Attributes\Column;
use Forge\Core\Database\Attributes\Index;
use Forge\Core\Database\Attributes\Table;
use Forge\Core\Database\Enums\ColumnType;
use Forge\Core\Database\Migrations\Migration;

#[Table(name: 'users')]
#[Index(columns: ['email'], name: 'idx_users_email')]
class CreateUsersTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::INTEGER, primaryKey: true)]
    public readonly int $id;

    #[Column(name: 'status', type: ColumnType::ENUM, enum: ['active', 'inactive', 'pending'])]
    public readonly string $status;

    #[Column(name: 'username', type: ColumnType::STRING, length: 255, unique: true)]
    public readonly string $username;

    #[Column(name: 'email', type: ColumnType::STRING, length: 255, unique: true)]
    public readonly string $email;

    #[Column(name: 'password', type: ColumnType::STRING, length: 255)]
    public readonly string $password;

    #[Column(name: 'created_at', type: ColumnType::TIMESTAMP, default: ColumnType::TIMESTAMP->defaultValue())]
    public readonly ?string $created_at;

    #[Column(name: 'updated_at', type: ColumnType::TIMESTAMP, nullable: true)]
    public readonly ?string $updated_at;

    #[Column(name: 'deleted_at', type: ColumnType::TIMESTAMP, nullable: true)]
    public readonly ?string $deleted_at;
}
