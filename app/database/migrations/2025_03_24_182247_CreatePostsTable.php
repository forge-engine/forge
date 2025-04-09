<?php

declare(strict_types=1);

use App\Modules\ForgeAuth\Models\User;
use Forge\Core\Database\Attributes\Column;
use Forge\Core\Database\Attributes\Index;
use Forge\Core\Database\Attributes\Relations\BelongsTo;
use Forge\Core\Database\Attributes\Table;
use Forge\Core\Database\Migrations\Migration;

#[Table(name: 'posts')]
#[BelongsTo(related: User::class)]
#[Index(columns: ['title'], name: 'idx_posts_title')]
class CreatePostsTable extends Migration
{
    #[Column(name: 'id', type: 'INT', primaryKey: true, autoIncrement: true)]
    private int $id;

    #[Column(name: 'title', type: 'STRING')]
    private string $title;

    #[Column(name: 'content', type: 'TEXT')]
    private string $content;

    #[Column(name: 'metadata', type: 'JSON')]
    private array $metadata;
}
