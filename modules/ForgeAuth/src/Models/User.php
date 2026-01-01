<?php

declare(strict_types=1);

namespace App\Modules\ForgeAuth\Models;

use App\Modules\ForgeAuth\Dto\UserMetadataDto;
use App\Modules\ForgeSqlOrm\ORM\CanLoadRelations;
use App\Modules\ForgeSqlOrm\ORM\Values\Cast;
use App\Modules\ForgeSqlOrm\ORM\Values\Relate;
use App\Modules\ForgeSqlOrm\ORM\Values\Relation;
use App\Modules\ForgeSqlOrm\ORM\Values\RelationKind;
use App\Modules\ForgeSqlOrm\Traits\{HasTimeStamps};
use App\Modules\ForgeSqlOrm\ORM\Attributes\{Table, Column};
use App\Modules\ForgeSqlOrm\ORM\Model;

#[Table("users")]
class User extends Model
{
    use HasTimeStamps;
    use CanLoadRelations;

    #[Column(primary: true, cast: Cast::INT)]
    public int $id;

    #[Column(cast: Cast::STRING)]
    public string $status;

    #[Column(cast: Cast::STRING)]
    public string $identifier;

    #[Column(cast: Cast::STRING)]
    public string $email;

    #[Column(cast: Cast::STRING)]
    public string $password;

    #[Column(cast: Cast::JSON)]
    public ?UserMetadataDto $metadata;

    #[Relate(RelationKind::HasOne, Profile::class, "user_id")]
    public function profile(): Relation
    {
        return self::describe(__FUNCTION__);
    }

    public function toArray(): array
    {
        $out = parent::toArray();
        unset($out["password"]);
        return $out;
    }
}
