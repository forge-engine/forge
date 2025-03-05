<?php

namespace Forge\Modules\ForgeAuth\Database\Migrations;

use Forge\Modules\ForgeDatabase\Contracts\DatabaseInterface;

class CreateUsersTable
{
    public function up(DatabaseInterface $db): void
    {
        $db->execute("
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        $db->execute("CREATE INDEX idx_users_email ON users(email);");
    }

    public function down(DatabaseInterface $db): void
    {
        $db->execute("DROP TABLE IF EXISTS users");
    }
}