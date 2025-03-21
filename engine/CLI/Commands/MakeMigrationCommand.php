<?php

declare(strict_types=1);

namespace Forge\CLI\Commands;

use Forge\CLI\Command;

class MakeMigrationCommand extends Command
{
    public static function getName(): string
    {
        return "make:migration";
    }

    public static function getDescription(): string
    {
        return "Create a new migration file";
    }

    public function execute(array $args): int
    {
        if (empty($args[0])) {
            $this->error("Error: Migration name required");
            return 1;
        }

        $name = $this->normalizeName($args[0]);
        $filename = date("Y_m_d_His") . "_$name.php";
        $path = BASE_PATH . "/app/database/migrations/$filename";

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $this->generateStub($name));
        $this->success("Migration created: $filename");
        return 0;
    }

    private function normalizeName(string $name): string
    {
        return preg_replace("/[^a-zA-Z0-9]/", "_", $name);
    }

    private function generateStub(string $className): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

use Forge\Core\Database\Migrations\Migration;
use Forge\Core\Database\Connection;

class $className extends Migration {
    public function up(): void {
        // Implement your migration
    }

    public function down(): void {
        // Implement rollback
    }
}
PHP;
    }
}
