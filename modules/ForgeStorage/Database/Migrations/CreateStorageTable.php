<?php

namespace Forge\Modules\ForgeStorage\Database\Migrations;

use Forge\Modules\ForgeOrm\Migrations\Migration;
use Forge\Modules\ForgeOrm\Schema\Blueprint;

class CreateStorageTable extends Migration
{

    public function up(): void
    {
        $this->schema->create('storage', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bucket_id');
            $table->string('bucket');
            $table->string('path');
            $table->integer('size', false);
            $table->string('mime_type');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique('path');
            $table->belongsTo('buckets', 'id', 'bucket_id', null, 'cascade');
        });
    }

    public function down(): void
    {
        $this->schema->drop('storage');
    }
}