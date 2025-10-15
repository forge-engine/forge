<?php

declare(strict_types=1);

use Forge\Core\Database\Attributes\GroupMigration;
use Forge\Core\Database\Migrations\Migration;

#[GroupMigration(name: 'storage')]
class CreateTemporaryUrlsTable extends Migration
{
    public function up(): void
    {
        $this->queryBuilder->setTable('temporary_urls')
            ->createTable('temporary_urls', [
                'id' => 'VARCHAR(36) PRIMARY KEY',
                'clean_path' => 'VARCHAR(255) NOT NULL',
                'bucket' => 'VARCHAR(255) NOT NULL',
                'path' => 'VARCHAR(255) NOT NULL',
                'expires_at' => 'TIMESTAMP NOT NULL',
                'token' => 'VARCHAR(255) NOT NULL',
                'storage_id' => 'VARCHAR(255) NOT NULL',
                'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                'updated_at' => 'TIMESTAMP NULL'
            ]);
        $this->execute($this->queryBuilder->getSql());
        $this->execute("CREATE UNIQUE INDEX idx_temporary_urls_clean_path ON temporary_urls(clean_path);");
    }

    public function down(): void
    {
        $this->execute($this->queryBuilder->dropTable('temporary_urls'));
    }
}
