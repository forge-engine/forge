<?php

declare(strict_types=1);

namespace App\Modules\ForgeBilling\Commands;

use Exception;
use Forge\CLI\Attributes\Arg;
use Forge\CLI\Attributes\Cli;
use Forge\CLI\Command;
use Forge\CLI\Traits\CliGenerator;
use Forge\Traits\StringHelper;

#[Cli(
    command: 'forge-billing:greet',
    description: 'Example command for ForgeBilling Module',
    usage: 'forge-billing:greet [--type=app|module] [--module=ModuleName] [--name=Example]',
    examples: [
        'forge-billing:event --type=app --name=Example',
        'forge-billing:event --type=app --name=Example --path=Test',
        'forge-billing:event --type=module --module=Blog --name=Example',
        'forge-billing:event   (starts wizard)',
    ]
)]
final class ForgeBillingCommand extends Command
{
    use StringHelper;
    use CliGenerator;

    #[Arg(name: 'type', description: 'app or module', validate: 'app|module', default: 'app')]
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

        $this->log("Hi {$this->name} from generated:greet", 'generatedModule');
        return 0;
    }
}