<?php

namespace App\Modules\ForgeStaticHtml\Commands;

use App\Modules\ForgeStaticHtml\StaticGenerator;
use Forge\CLI\Command;
use Forge\CLI\Traits\OutputHelper;
use Forge\Core\Module\Attributes\CLICommand;
use Forge\Core\Config\Config;

#[CLICommand(name: 'static:generate:html', description: 'Generate static HTML version of the site')]
class GenerateStaticCommand extends Command
{
    use OutputHelper;

    public function __construct(private Config $config)
    {
    }

    public function execute(array $args): int
    {
        try {
            $config = $this->config->get('forge_static_html');
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
