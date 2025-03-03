<?php

namespace MyApp\Database\Migrations;

use Forge\Modules\ForgeOrm\Migrations\Migration;
use Forge\Modules\ForgeOrm\Schema\Blueprint;

class CreateSectionTable extends Migration
{

    public function up(): void
    {
        $this->schema->create('sections', function (Blueprint $table) {
            $table->integer('id', true)->primary();
            $table->integer('category_id', false);
            $table->string('title');
            $table->string('slug');
            $table->text('content')->nullable();
            $table->timestamps();

            $table->belongsTo('categories', 'id', 'category_id', null, 'cascade');
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        $this->schema->drop('sections');
    }
}