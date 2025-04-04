<?php

namespace Forge\Modules\ForgeQueue\Database\Migrations;

use Forge\Modules\ForgeOrm\Migrations\Migration;
use Forge\Modules\ForgeOrm\Schema\Blueprint;

class CreateQueueTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $this->schema->create('queue_jobs', function (Blueprint $table) {
            $table->integer('id', true, true)->primary();
            $table->string('queue');
            $table->longText('payload');
            $table->tinyInteger('attempts', false, true)->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('locked_until')->nullable();
            $table->dateTime('created_at')->defaultCurrentTimestamp();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('failed_at')->nullable();

            $table->index(['queue', 'status'], 'idx_queue_status');
            $table->index(['scheduled_at'], 'idx_scheduled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $this->schema->dropIfExists('queue_jobs');
    }
}