<?php

declare(strict_types=1);

use Forge\Core\Database\Attributes\Column;
use Forge\Core\Database\Attributes\Index;
use Forge\Core\Database\Attributes\MetaData;
use Forge\Core\Database\Attributes\SoftDelete;
use Forge\Core\Database\Attributes\Table;
use Forge\Core\Database\Attributes\Timestamps;
use Forge\Core\Database\Enums\ColumnType;
use Forge\Core\Database\Migrations\Migration;

#[Table(name: 'users')]
#[Index(columns: ['id'], name: 'idx_users_id')]
#[Index(columns: ['email'], name: 'idx_users_email')]
#[Index(columns: ['deleted_at'], name: 'idx_users_deleted_at')]
#[MetaData]
#[Timestamps]
#[SoftDelete]
class CreateUsersTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::INTEGER, primaryKey: true)]
    public readonly int $id;

    #[Column(name: 'status', type: ColumnType::ENUM, enum: ['active', 'inactive', 'pending'])]
    public readonly string $status;

    #[Column(name: 'identifier', type: ColumnType::STRING, length: 255, unique: true)]
    public readonly string $username;

    #[Column(name: 'email', type: ColumnType::STRING, length: 255, unique: true)]
    public readonly string $email;

    #[Column(name: 'password', type: ColumnType::STRING, length: 255)]
    public readonly string $password;
}
