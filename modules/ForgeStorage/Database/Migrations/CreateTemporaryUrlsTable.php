<?php

namespace Forge\Modules\ForgeStorage\Database\Migrations;

use Forge\Modules\ForgeOrm\Migrations\Migration;
use Forge\Modules\ForgeOrm\Schema\Blueprint;

class CreateTemporaryUrlsTable extends Migration
{

    public function up(): void
    {
        $this->schema->create('temporary_urls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('clean_path');
            $table->string('bucket');
            $table->string('path');
            $table->timestamp('expires_at');
            $table->string('token');
            $table->timestamps();

            $table->unique('clean_path');
        });
    }

    public function down(): void
    {
        $this->schema->drop('temporary_urls');
    }
}