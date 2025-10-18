<?php

declare(strict_types=1);

namespace App\Commands;

use Exception;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\CliGenerator;
use Forge\Traits\StringHelper;

#[Cli(
    command: 'testing:greet',
    description: 'This is just a test',
    usage: 'testing:greet [--type=app|module] [--module=ModuleName] [--name=Example]',
    examples: [
        'testing:event --type=app --name=Example',
        'testing:event --type=app --name=Example --path=Test',
        'testing:event --type=module --module=Blog --name=Example',
        'testing:event   (starts wizard)',
    ]
)]
final class testingCommand extends Command
{
    use StringHelper;
    use CliGenerator;

    #[Arg(name: 'type', description: 'app or module', default: 'app', validate: 'app|module')]
    private string $type = 'app';

    #[Arg(name: 'module', description: 'Module name when type=module', required: false)]
    private ?string $module = null;

    #[Arg(name: 'name', description: 'Whats your name')]
    private string $name = '';

    #[Arg(
        name: 'path',
        description: 'Optional subfolder (e.g., Admin, Api/V1)',
        default: '',
        required: false
    )]
    private string $path = '';

    /**
     * @throws Exception
     */
    public function execute(array $args): int
    {
        $this->wizard($args);

        if ($this->type === 'module' && !$this->module) {
            $this->error('--module=Name required when --type=module');
            return 1;
        }

        $this->log("Hi {$this->name} ", 'testingCommand');
        return 0;
    }
}