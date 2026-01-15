<?php

declare(strict_types=1);


use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: 'storage')]
class CreateBucketsTable extends Migration
{
  public function up(): void
  {
    $sql = $this->createTable('buckets', [
      'id' => 'VARCHAR(36) PRIMARY KEY',
      'name' => 'VARCHAR(255) NOT NULL',
      'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
      'updated_at' => 'TIMESTAMP NULL'
    ]);
    $this->execute($sql);
    $this->execute("CREATE UNIQUE INDEX idx_buckets_id ON buckets(id);");
  }

  public function down(): void
  {
    $this->execute($this->queryBuilder->dropTable('buckets'));
  }
}
