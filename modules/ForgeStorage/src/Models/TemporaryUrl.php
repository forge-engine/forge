<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Models;

use Forge\Core\Database\Table;
use Forge\Core\Database\Column;
use Forge\Core\Database\Model;

#[Table("temporary_urls")]
final class TemporaryUrl extends Model
{
    #[Column("integer", primary: true)]
    public int $id;

    #[Column("varchar(255)")]
    public string $clean_path;

    #[Column("varchar(255)")]
    public string $bucket;

    #[Column("varchar(255)")]
    public string $path;

    #[Column("varchar(255)")]
    public string $token;

    #[Column("varchar(255)")]
    public string $storage_id;

    #[Column("timestamp", nullable: true)]
    public ?string $expires_at = null;

    #[Column("timestamp", nullable: true)]
    public ?string $created_at = null;

    #[Column("timestamp", nullable: true)]
    public ?string $updated_at = null;

    #[Column("timestamp", nullable: true)]
    public ?string $deleted_at = null;

    protected bool $softDelete = false;
    protected array $hidden = ["password"];
}
