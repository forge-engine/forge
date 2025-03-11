<?php

declare(strict_types=1);

use Forge\Core\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->execute("
         CREATE TABLE users(
          id INT AUTO_INCREMENT PRIMARY KEY,
           username VARCHAR(255) NOT NULL,
           password VARCHAR(255) NOT NULL,
           email VARCHAR(255) NOT NULL,
           created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
           updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
           deleted_at DATETIME DEFAULT NULL
         );
         ");
    }

    public function down(): void
    {
        $this->execute("DROP TABLE users;");
    }
}
