<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Models;

use App\Modules\ForgeAuth\Dto\UserMetadataDto;
use Forge\Core\Database\Table;
use Forge\Core\Database\Column;
use Forge\Core\Database\Model;
use Forge\Traits\RepositoryTrait;
use Forge\Traits\HasMetaData;
use Forge\Traits\Hastimestamps;
use Forge\Traits\Metadata;
use Forge\Traits\SoftDeletes;

#[Table("users")]
final class User extends Model
{
    use Hastimestamps;
    use SoftDeletes;
    use HasMetaData;
    use Metadata;
    use RepositoryTrait;

    protected array $hidden = ["password"];
    protected bool $softDelete = true;

    protected array $casts = [
        'metadata' => 'json'
    ];

    #[Column("integer", primary: true)]
    public int $id;

    #[Column("varchar(255)")]
    public string $status;

    #[Column("varchar(255)")]
    public string $identifier;

    #[Column("varchar(255)")]
    public string $email;

    #[Column("varchar(255)")]
    public string $password;

    /**
     * Get the user Metada as a friendly DTO object
     */
    public function metadata(): UserMetadataDto
    {
        return new UserMetadataDto(...$this->metadata);
    }

    /**
     * Get the user profile relation
     */
    public function profile(): ?Profile
    {
        return Profile::findBy('user_id', $this->id);
    }
}
