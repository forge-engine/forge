<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Models;

use Forge\Core\Database\Table;
use Forge\Core\Database\Column;
use Forge\Core\Database\Model;

#[Table("buckets")]
final class Bucket extends Model
{
    #[Column("varchar(36)", primary: true)]
    public string $id;

    #[Column("varchar(255)")]
    public string $name;

    #[Column("timestamp", nullable: true)]
    public ?string $created_at = null;

    #[Column("timestamp", nullable: true)]
    public ?string $updated_at = null;

    protected bool $softDelete = false;
    protected array $hidden = [];
}
