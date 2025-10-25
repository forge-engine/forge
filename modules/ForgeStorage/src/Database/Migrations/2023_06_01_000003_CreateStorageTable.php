<?php

declare(strict_types=1);


use App\Modules\ForgeDatabaseSQL\DB\Attributes\GroupMigration;
use App\Modules\ForgeDatabaseSQL\DB\Migrations\Migration;

#[GroupMigration(name: 'storage')]
class CreateStorageTable extends Migration
{
    public function up(): void
    {
        $this->queryBuilder->setTable('storage')
            ->createTable(
                'storage',
                [
                    'id' => 'VARCHAR(36) PRIMARY KEY',
                    'bucket_id' => 'VARCHAR(36) NOT NULL',
                    'bucket' => 'VARCHAR(255) NOT NULL',
                    'path' => 'VARCHAR(255) NOT NULL',
                    'size' => 'INTEGER NOT NULL',
                    'mime_type' => 'VARCHAR(255) NOT NULL',
                    'expires_at' => 'TIMESTAMP NULL',
                    'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
                    'updated_at' => 'TIMESTAMP NULL'
                ],
            // TODO: migrate to new migration version
            // [
            //     'CONSTRAINT fk_storage_bucket_id FOREIGN KEY (bucket_id) REFERENCES buckets(id) ON DELETE CASCADE'
            // ]
            );
        $this->execute($this->queryBuilder->getSql());
        $this->execute("CREATE UNIQUE INDEX idx_storage_path ON storage(path);");
        $this->execute("CREATE INDEX idx_storage_bucket_id ON storage(bucket_id);");
    }

    public function down(): void
    {
        $this->execute($this->queryBuilder->dropTable('storage'));
    }
}
