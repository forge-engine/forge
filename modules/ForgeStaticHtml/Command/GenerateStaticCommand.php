<?php

namespace Forge\Modules\ForgeStaticHtml\Command;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Helpers\App;
use Forge\Core\Traits\OutputHelper;
use Forge\Modules\ForgeStaticHtml\StaticGenerator;

class GenerateStaticCommand implements CommandInterface
{
    use OutputHelper;

    public function getName(): string
    {
        return 'static:generate:html';
    }

    public function getDescription(): string
    {
        return 'Generate static HTML version of the site';
    }

    public function execute(array $args): int
    {
        try {
            $config = App::config()->get('forge_static_html');
            $generator = new StaticGenerator($config);

            $this->info("Starting static site generation...");
            $generator->generate();
            $this->success("Static site generated successfully!");

            return 0;
        } catch (\Throwable $e) {
            $this->error("Generation failed: " . $e->getMessage());
            return 1;
        }
    }
}