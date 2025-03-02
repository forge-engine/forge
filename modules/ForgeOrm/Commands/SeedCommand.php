<?php

namespace Forge\Modules\ForgeOrm\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Helpers\App;
use Forge\Core\Traits\OutputHelper;
use Forge\Modules\ForgeOrm\Seeder\SeedManager;

class SeedCommand implements CommandInterface
{
    use OutputHelper;

    public function getName(): string
    {
        return 'seed';
    }

    public function getDescription(): string
    {
        return 'Run database seeders';
    }

    public function execute(array $args): int
    {
        $container = App::getContainer();
        $seedManager = $container->get(SeedManager::class);

        try {
            $options = $this->parseOptions($args);

            $seedManager->runSeeds(
                $options['refresh'],
                $options['class']
            );

            $this->success("Seeding completed successfully.");
            return 0;
        } catch (\Throwable $e) {
            $this->error("Seeding failed: " . $e->getMessage());
            return 1;
        }
    }

    private function parseOptions(array $args): array
    {
        $options = [
            'refresh' => in_array('--refresh', $args),
            'class' => $this->getClassArgument($args)
        ];

        return $options;
    }

    private function getClassArgument(array $args): ?string
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--class=')) {
                return substr($arg, 8);
            }
        }
        return null;
    }
}