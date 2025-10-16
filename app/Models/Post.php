<?php

declare(strict_types=1);

namespace App\Models;

use App\Modules\ForgeMultiTenant\Attributes\TenantScoped;
use App\Modules\ForgeMultiTenant\Traits\TenantScopedTrait;
use Forge\Core\Database\Table;
use Forge\Core\Database\Column;
use Forge\Core\Database\Model;
use Forge\Traits\RepositoryTrait;
use Forge\Traits\HasMetaData;
use Forge\Traits\Hastimestamps;
use Forge\Traits\Metadata;

#[TenantScoped]
#[Table("posts")]
class Post extends Model
{
    use Hastimestamps;
    use HasMetaData;
    use Metadata;
    use RepositoryTrait;
    use TenantScopedTrait;

    protected array $hidden = [];

    protected array $casts = [
        'metadata' => 'json'
    ];

    #[Column("integer", primary: true)]
    public int $id;

    #[Column("varchar(255)")]
    public string $title;

    #[Column("text")]
    public string $content;

    #[Column("varchar(255)")]
    public string $tenant_id;

    public function metadata(): array
    {
        return [];
    }

}
