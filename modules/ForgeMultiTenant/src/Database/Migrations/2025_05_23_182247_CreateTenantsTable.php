<?php

declare(strict_types=1);

use Forge\Core\Database\Attributes\Column;
use Forge\Core\Database\Attributes\GroupMigration;
use Forge\Core\Database\Attributes\Index;
use Forge\Core\Database\Attributes\MetaData;
use Forge\Core\Database\Attributes\SoftDelete;
use Forge\Core\Database\Attributes\Table;
use Forge\Core\Database\Attributes\Timestamps;
use Forge\Core\Database\Enums\ColumnType;
use Forge\Core\Database\Migrations\Migration;

#[Table(name: 'tenants')]
#[Index(columns: ['domain'], name: 'idx_tenants_domain')]
#[Index(columns: ['subdomain'], name: 'idx_tenants_subdomain')]
#[MetaData]
#[Timestamps]
#[SoftDelete]
class CreateTenantsTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::STRING, primaryKey: true, length: 36)]
    public readonly string $id;

    #[Column(name: 'domain', type: ColumnType::STRING, nullable: false, length: 255)]
    public readonly string $domain;

    #[Column(name: 'subdomain', type: ColumnType::STRING, nullable: true, length: 255)]
    public readonly ?string $subdomain;

    #[Column(name: 'strategy', type: ColumnType::STRING, default: 'column', length: 20)]
    public readonly string $strategy;

    #[Column(name: 'db_name', type: ColumnType::STRING, nullable: true, length: 64)]
    public readonly ?string $dbName;

    #[Column(name: 'connection', type: ColumnType::STRING, nullable: true, length: 64)]
    public readonly ?string $connection;
}
