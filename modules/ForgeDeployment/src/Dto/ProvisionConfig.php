<?php

declare(strict_types=1);

namespace App\Modules\ForgeDeployment\Dto;

final class ProvisionConfig
{
  public function __construct(
    public readonly string $phpVersion,
    public readonly string $databaseType,
    public readonly ?string $databaseVersion = null
  ) {
  }

  public function toArray(): array
  {
    return [
      'php_version' => $this->phpVersion,
      'database_type' => $this->databaseType,
      'database_version' => $this->databaseVersion,
    ];
  }
}
