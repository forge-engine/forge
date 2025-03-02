<?php

namespace Forge\Modules\ForgeStorage\Database\Migrations;

use Forge\Modules\ForgeOrm\Migrations\Migration;
use Forge\Modules\ForgeOrm\Schema\Blueprint;

class CreateBucketsTable extends Migration
{
    public function up(): void
    {
        $this->schema->create('buckets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();

            $table->unique('id');
        });
    }

    public function down(): void
    {
        $this->schema->drop('buckets');
    }
}
//