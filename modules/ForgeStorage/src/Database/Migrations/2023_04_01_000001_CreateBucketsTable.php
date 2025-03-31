<?php

declare(strict_types=1);

use Forge\Core\Database\Migrations\Migration;

class CreateBucketsTable extends Migration
{
    public function up(): void
    {
        $this->queryBuilder->setTable('buckets')
            ->createTable([
                'id' => 'VARCHAR(36) PRIMARY KEY',
                'name' => 'VARCHAR(255) NOT NULL',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'updated_at' => 'TIMESTAMP NULL'
            ]);
        $this->execute($this->queryBuilder->getSql());
        $this->execute("CREATE UNIQUE INDEX idx_buckets_id ON buckets(id);");
    }

    public function down(): void
    {
        $this->execute($this->queryBuilder->dropTable('buckets'));
    }
}
