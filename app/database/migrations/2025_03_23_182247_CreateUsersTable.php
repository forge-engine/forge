<?php

declare(strict_types=1);

use Forge\Core\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->execute(
            "CREATE TABLE IF NOT EXISTS users (
               id INTEGER PRIMARY KEY AUTOINCREMENT,
               username VARCHAR(255) NOT NULL,
               email VARCHAR(255) UNIQUE NOT NULL,
               password VARCHAR(255) NOT NULL,
               created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
               updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
               deleted_at TIMESTAMP NULL
           ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        );
        $this->execute("CREATE INDEX idx_users_email ON users(email);");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE IF EXISTS users");
    }
}
