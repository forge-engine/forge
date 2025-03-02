<?php

namespace Forge\Modules\ForgeOrm\Database\Seeds;

use Forge\Modules\ForgeOrm\Seeder\Seeder;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $this->db->execute(
            "INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, ?)",
            [
                'Admin User',
                'admin@example.com',
                password_hash('secret', PASSWORD_DEFAULT),
                date('Y-m-d H:i:s')
            ]
        );

        // Add more sample users
        $this->db->execute(
            "INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, ?)",
            [
                'Regular User',
                'user@example.com',
                password_hash('password', PASSWORD_DEFAULT),
                date('Y-m-d H:i:s')
            ]
        );
    }
}