<?php

namespace Forge\Modules\ForgeStorage\Command;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Traits\OutputHelper;

class UnlinkStorageCommand implements CommandInterface
{
    use OutputHelper;

    public function getName(): string
    {
        return 'storage:unlink';
    }

    public function getDescription(): string
    {
        return 'Remove the symbolic link from "public/storage"';
    }

    /**
     * @param array<int,mixed> $args
     */
    public function execute(array $args): int
    {
        $link = BASE_PATH . '/public/storage';

        if (file_exists($link)) {
            if (is_link($link)) {
                if (unlink($link)) {
                    $this->info("The [public/storage] link has been removed.\n");
                    return 0;
                } else {
                    $this->error("Failed to remove the [public/storage] link.\n");
                    return 1;
                }
            } else {
                $this->error("The [public/storage] path is not a symbolic link.\n");
                return 1;
            }
        } else {
            $this->info("The [public/storage] link does not exist.\n");
            return 0;
        }
    }
}
