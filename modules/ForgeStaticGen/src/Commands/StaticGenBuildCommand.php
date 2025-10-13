<?php

namespace App\Modules\ForgeStaticGen\Commands;

use App\Modules\ForgeStaticGen\Contracts\ForgeStaticGenInterface;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\CLICommand;

#[CLICommand(name: "static:build", description: "Build the static site from Markdown content")]
class StaticGenBuildCommand extends Command
{
    use OutputHelper;

    public function execute(array $args): int
    {
        $this->info("Starting static site generation...");

        $container = Container::getInstance();
        /** @var ForgeStaticGenInterface $staticGen */
        $staticGen = $container->get(ForgeStaticGenInterface::class);

        $contentDir = BASE_PATH . "/docs";

        try {
            $staticGen->build($contentDir);
            $this->info("Static site generation completed successfully!");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Static site generation failed!");
            $this->error("Error: " . $e->getMessage());
            return 1;
        }
    }
}
