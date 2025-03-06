<?php

namespace Forge\Modules\ForgeQueue\Enums;

enum JobStatus: string
{
    case PENDING = "pending";
    case PROCESSING = "processing";
    case COMPLETED = "completed";
    case FAILED = "failed";
}
