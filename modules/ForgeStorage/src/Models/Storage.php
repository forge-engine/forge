<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Models;

use Forge\Core\Database\Table;
use Forge\Core\Database\Column;
use Forge\Core\Database\Model;

#[Table("storage")]
final class Storage extends Model
{
    #[Column("varchar(255)", primary: true)]
    public string $id;

    #[Column("varchar(255)")]
    public string $bucket_id;

    #[Column("varchar(255)")]
    public string $bucket;

    #[Column("varchar(255)")]
    public string $path;

    #[Column("integer")]
    public string $size;

    #[Column("varchar(255)")]
    public string $mime_type;

    #[Column("timestamp", nullable: true)]
    public ?string $expires_at = null;

    #[Column("timestamp", nullable: true)]
    public ?string $created_at = null;

    #[Column("timestamp", nullable: true)]
    public ?string $updated_at = null;

    protected bool $softDelete = false;
    protected array $hidden = [];
}
