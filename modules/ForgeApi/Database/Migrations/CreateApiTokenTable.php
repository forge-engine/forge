<?php

namespace Forge\Modules\ForgeApi\Database\Migrations;

use Forge\Modules\ForgeOrm\Migrations\Migration;
use Forge\Modules\ForgeOrm\Schema\Blueprint;

class CreateApiTokenTable extends Migration
{

    public function up(): void
    {
        $this->schema->create('api_tokens', function (Blueprint $table) {
            $table->string('token')->primary();
            $table->integer('user_id', false);
            $table->timestamp('expires_at');
            $table->timestamps();
            //$table->belongsTo('users', 'id', 'user_id', 'cascade');
        });
    }

    public function down(): void
    {
        $this->schema->drop('api_tokens');
    }
}