<?php

namespace Forge\Modules\ForgeOrm\Database\Migrations;

use Forge\Modules\ForgeOrm\Migrations\Migration;
use Forge\Modules\ForgeOrm\Schema\Blueprint;

class CreatePostTable extends Migration
{

    public function up(): void
    {
        $this->schema->create('posts', function (Blueprint $table) {
            $table->integer('id', true)->primary();
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->schema->drop('posts');
    }
}