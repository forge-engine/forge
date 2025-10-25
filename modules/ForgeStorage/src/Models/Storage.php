<?php

declare(strict_types=1);

namespace App\Modules\ForgeStorage\Models;


use App\Modules\ForgeSqlOrm\ORM\Attributes\Column;
use App\Modules\ForgeSqlOrm\ORM\Attributes\Table;
use App\Modules\ForgeSqlOrm\ORM\Model;
use App\Modules\ForgeSqlOrm\ORM\Values\Cast;
use App\Modules\ForgeSqlOrm\Traits\HasMetaData;
use App\Modules\ForgeSqlOrm\Traits\HasTimeStamps;

#[Table("storage")]
final class Storage extends Model
{
    use HasTimeStamps;
    use HasMetaData;

    #[Column(primary: true, cast: Cast::STRING)]
    public string $id;

    #[Column(cast: Cast::STRING)]
    public string $bucket_id;

    #[Column(cast: Cast::STRING)]
    public string $bucket;

    #[Column(cast: Cast::STRING)]
    public string $path;

    #[Column(cast: Cast::INT)]
    public string $size;

    #[Column(cast: Cast::STRING)]
    public string $mime_type;

    #[Column(cast: Cast::TIMESTAMP)]
    public ?string $expires_at = null;

}
