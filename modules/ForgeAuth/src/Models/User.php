<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Models;

use Forge\Core\Database\Table;
use Forge\Core\Database\Column;
use Forge\Core\Database\Model;

#[Table("users")]
final class User extends Model
{
    #[Column("integer", primary: true)]
    public int $id;

    #[Column("varchar(255)")]
    public string $username;

    #[Column("varchar(255)")]
    public string $email;

    #[Column("varchar(255)")]
    public string $password;

    #[Column("timestamp", nullable: true)]
    public ?string $created_at = null;

    #[Column("timestamp", nullable: true)]
    public ?string $updated_at = null;

    #[Column("timestamp", nullable: true)]
    public ?string $deleted_at = null;

    protected bool $softDelete = false;
    protected array $hidden = ["password"];

    public function isAdmin(): bool
    {
        return str_ends_with($this->email, "@upper.do");
    }
}
