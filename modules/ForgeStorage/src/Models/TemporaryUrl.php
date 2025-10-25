<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Models;

use App\Modules\ForgeSqlOrm\ORM\Attributes\Column;
use App\Modules\ForgeSqlOrm\ORM\Attributes\Table;
use App\Modules\ForgeSqlOrm\ORM\Model;
use App\Modules\ForgeSqlOrm\ORM\Values\Cast;
use App\Modules\ForgeSqlOrm\Traits\HasMetaData;
use App\Modules\ForgeSqlOrm\Traits\HasTimeStamps;

#[Table("temporary_urls")]
final class TemporaryUrl extends Model
{
    use HasTimeStamps;
    use HasMetaData;

    #[Column(primary: true, cast: Cast::INT)]
    public int $id;

    #[Column(cast: Cast::STRING)]
    public string $clean_path;

    #[Column(cast: Cast::STRING)]
    public string $bucket;

    #[Column(cast: Cast::STRING)]
    public string $path;

    #[Column(cast: Cast::STRING)]
    public string $token;

    #[Column(cast: Cast::STRING)]
    public string $storage_id;

    #[Column(cast: Cast::STRING)]
    public ?string $expires_at = null;

    #[Column(cast: Cast::TIMESTAMP)]
    public ?string $deleted_at = null;

}
