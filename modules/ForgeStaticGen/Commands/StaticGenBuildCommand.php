<?php

namespace Forge\Modules\ForgeStaticGen\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Traits\OutputHelper;
use Forge\Core\DependencyInjection\Container;
use Forge\Modules\ForgeStaticGen\Contacts\ForgeStaticGenInterface;
use Forge\Core\Helpers\Path;

class StaticGenBuildCommand implements CommandInterface
{
    use OutputHelper;

    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getName(): string
    {
        return 'static:build';
    }

    public function getDescription(): string
    {
        return 'Build the static site from Markdown content';
    }

    public function execute(array $args): int
    {
        $this->info("Starting static site generation...");
        

        /** @var ForgeStaticGenInterface $staticGen */
        $staticGen = $this->container->get(ForgeStaticGenInterface::class);

        $contentDir = Path::contentPath();

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