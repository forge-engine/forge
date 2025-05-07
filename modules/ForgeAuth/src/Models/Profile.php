<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Models;

use Forge\Core\Database\Table;
use Forge\Core\Database\Column;
use Forge\Core\Database\Model;
use Forge\Traits\HasMetaData;
use Forge\Traits\Hastimestamps;
use Forge\Traits\Metadata;
use Forge\Traits\RepositoryTrait;
use Forge\Traits\SoftDeletes;

#[Table("profiles")]
class Profile extends Model
{
    use Hastimestamps;
    use SoftDeletes;
    use HasMetaData;
    use Metadata;
    use RepositoryTrait;

    protected array $hidden = [];
    protected bool $softDelete = true;

    protected array $casts = [
        'metadata' => 'json'
    ];

    #[Column("integer", primary: true)]
    public int $id;

    #[Column("integer")]
    public int $user_id;

    #[Column("varchar(255)")]
    public string $first_name;

    #[Column("varchar(255)")]
    public ?string $last_name;

    #[Column("varchar(255)")]
    public ?string $avatar;

    #[Column("varchar(255)")]
    public ?string $email;

    #[Column("varchar(255)")]
    public ?string $phone;

    #[Column("varchar(255)")]
    public ?string $pending_email;

    #[Column("varchar(255)")]
    public ?string $pending_phone;

    #[Column("varchar(255)")]
    public ?string $email_confirmed;

    #[Column("varchar(255)")]
    public ?string $phone_confirmed;
}
