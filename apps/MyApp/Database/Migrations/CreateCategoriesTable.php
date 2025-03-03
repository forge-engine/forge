<?php

namespace MyApp\Database\Migrations;

use Forge\Modules\ForgeOrm\Migrations\Migration;
use Forge\Modules\ForgeOrm\Schema\Blueprint;

class CreateCategoriesTable extends Migration
{

    public function up(): void
    {
        $this->schema->create('categories', function (Blueprint $table) {
            $table->integer('id', true)->primary();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();

            $table->unique('slug');
        });
    }

    public function down(): void
    {
        $this->schema->drop('categories');
    }
}