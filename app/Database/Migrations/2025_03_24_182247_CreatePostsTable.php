<?php

declare(strict_types=1);

use App\Modules\ForgeAuth\Models\User;
use App\Modules\ForgeMultiTenant\Attributes\TenantScoped;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Column;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Index;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Relations\BelongsTo;
use App\Modules\ForgeDatabaseSQL\DB\Attributes\Table;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;
use App\Modules\ForgeDatabaseSQL\DB\Enums\ColumnType;

#[TenantScoped]
#[GroupMigration('tenants')]
#[Table(name: 'posts')]
#[BelongsTo(related: User::class)]
#[Index(columns: ['title'], name: 'idx_posts_title')]
class CreatePostsTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::INTEGER, primaryKey: true, autoIncrement: true)]
    public readonly int $id;

    #[Column(name: 'title', type: ColumnType::STRING)]
    public readonly string $title;

    #[Column(name: 'content', type: ColumnType::TEXT)]
    public readonly string $content;

    #[Column(name: 'metadata', type: ColumnType::JSON)]
    public readonly array $metadata;
}
