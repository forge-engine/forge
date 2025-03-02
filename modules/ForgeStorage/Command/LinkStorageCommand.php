<?php

namespace Forge\Modules\ForgeStorage\Command;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Traits\OutputHelper;

class LinkStorageCommand implements CommandInterface
{
    use OutputHelper;

    public function getName(): string
    {
        return 'storage:link';
    }

    public function getDescription(): string
    {
        return 'Create a symbolic link from "public/storage" to "storage/app/public"';
    }

    /**
     * @param array<int,mixed> $args
     */
    public function execute(array $args): int
    {
        $target = BASE_PATH . '/storage/app';
        $link = BASE_PATH . '/public/storage';

        if (file_exists($link)) {
            $this->info("The [public/storage] link already exists.\n");
            return 0;
        }

        if (!file_exists($target)) {
            if (!mkdir($target, 0755, true) && !is_dir($target)) {
                $this->error("Unable to create the [{$target}] directory.\n");
                return 1;
            }
        }

        if (symlink($target, $link)) {
            $this->info("The [public/storage] link has been created.\n");
            return 0;
        } else {
            $this->error("Failed to create the [public/storage] link.\n");
            return 1;
        }
    }
}
