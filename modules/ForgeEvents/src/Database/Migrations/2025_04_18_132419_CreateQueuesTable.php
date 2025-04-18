<?php

declare(strict_types=1);


declare(strict_types=1);

use Forge\Core\Database\Attributes\Column;
use Forge\Core\Database\Attributes\Index;
use Forge\Core\Database\Attributes\Table;
use Forge\Core\Database\Attributes\Timestamps;
use Forge\Core\Database\Migrations\Migration;
use Forge\Core\Database\Enums\ColumnType;

#[Table(name: 'queue_jobs')]
#[Index(columns: ['queue', 'process_at'], name: 'idx_queue_process_at')]
#[Index(columns: ['attempts'], name: 'idx_attempts')]
#[Timestamps]
class CreateQueuesTable extends Migration
{
    #[Column(name: 'id', type: ColumnType::INTEGER, primaryKey: true, autoIncrement: true)]
    public readonly string $id;

    #[Column(name: 'queue', type: ColumnType::STRING, length: 255, nullable: false, default: 'default')]
    public readonly string $queue;

    #[Column(name: 'payload', type: ColumnType::TEXT, nullable: false)]
    public readonly string $payload;

    #[Column(name: 'attempts', type: ColumnType::INTEGER, default: 0, nullable: true)]
    public readonly int $attempts;

    #[Column(name: 'max_retries', type: ColumnType::INTEGER, default: 1, nullable: true)]
    public readonly int $max_retries;

    #[Column(name: 'priority', type: ColumnType::INTEGER, default: 100, nullable: true)]
    public readonly int $priority;

    #[Column(name: 'process_at', type: ColumnType::TIMESTAMP, nullable: true, default: null)]
    public readonly ?string $process_at;

    #[Column(name: 'reserved_at', type: ColumnType::TIMESTAMP, nullable: true, default: null)]
    public readonly ?string $reserved_at;

    #[Column(name: 'failed_at', type: ColumnType::TIMESTAMP, nullable: true, default: null)]
    public readonly ?string $failed_at;
}
