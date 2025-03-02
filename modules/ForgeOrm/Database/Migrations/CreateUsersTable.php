<?php

namespace Forge\Modules\ForgeOrm\Database\Migrations;

use Forge\Modules\ForgeOrm\Migrations\Migration;
use Forge\Modules\ForgeOrm\Schema\Blueprint;

class CreateUsersTable extends Migration
{

    public function up(): void
    {
        $this->schema->create('users', function (Blueprint $table) {
            $table->integer('id', true)->primary();
            $table->string('name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->schema->drop('users');
    }
}