<?php
declare(strict_types=1);

namespace App\Modules\ForgeMultiTenant\Traits;

use App\Modules\ForgeMultiTenant\Services\TenantQueryRewriter;
use Forge\Core\Database\QueryBuilder;

trait TenantScopedTrait
{
    protected function newQuery(): QueryBuilder
    {
        $builder = parent::newQuery();
        return TenantQueryRewriter::scope($builder);
    }
}