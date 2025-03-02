<?php

namespace Forge\Console\Commands;

use Forge\Core\Contracts\Command\CommandInterface;
use Forge\Core\Traits\OutputHelper;
use Forge\Core\Services\TemplateGenerator;

class MakeModuleCommand implements CommandInterface
{
    use OutputHelper;

    protected TemplateGenerator $templateGenerator;

    public function __construct()
    {
        $this->templateGenerator = new TemplateGenerator();
    }

    public function getName(): string
    {
        return 'make:module';
    }

    public function getDescription(): string
    {
        return 'Create a new Forge module with basic structure';
    }

    public function execute(array $args): int
    {
        $moduleName = $args[0] ?? $this->askForModuleName();
        if (!$moduleName) {
            $this->error("Module name is required.");
            return 1;
        }

        if (!preg_match('/^[A-Za-z]+$/', $moduleName)) {
            $this->error("Invalid module name. Use PascalCase (e.g., MyModule).");
            return 1;
        }

        $moduleDescription = $this->templateGenerator->askQuestion("Module description: ", "A brief description of the module.");
        $moduleVersion = $this->templateGenerator->askQuestion("Module version (e.g., 1.0.0): ", "1.0.0");

        $moduleDir = BASE_PATH . '/modules/' . $moduleName;

        if (is_dir($moduleDir)) {
            $this->error("Module directory already exists: {$moduleDir}");
            return 1;
        }

        mkdir($moduleDir, 0755, true);
        $this->generateManifestFile($moduleDir, $moduleName, $moduleDescription, $moduleVersion);
        $this->generateModuleInterfaceFile($moduleDir, $moduleName);
        $this->generateModuleLogicClassFile($moduleDir, $moduleName);
        $this->generateModuleClassFile($moduleDir, $moduleName);
        $this->generateConfigFile($moduleDir, $moduleName);

        $this->info("Module '{$moduleName}' created successfully!");
        return 0;
    }

    private function askForModuleName(): ?string
    {
        return $this->templateGenerator->askQuestion("Enter module name (PascalCase, e.g., MyModule): ", '');
    }

    private function generateModuleLogicClassFile(string $moduleDir, string $moduleName): void
    {
        $templateName = 'modules/logic_class.php.template';
        $outputPath = $moduleDir . '/' . $moduleName . '.php';
        $replacements = [
            '{{ moduleName }}' => $moduleName,
            '{{ className }}' => $moduleName,
            '{{ interfaceName }}' => $moduleName . 'Interface',
        ];
        $this->templateGenerator->generateFileFromTemplate($templateName, $outputPath, $replacements);
    }

    private function generateModuleInterfaceFile(string $moduleDir, string $moduleName): void
    {
        $templateName = 'modules/interface.php.template';
        $outputPath = $moduleDir . '/Contracts/' . $moduleName . 'Interface.php';
        $replacements = [
            '{{ moduleName }}' => $moduleName,
            '{{ interfaceName }}' => $moduleName . 'Interface',
        ];
        mkdir($moduleDir . '/Contracts', 0755, true);
        $this->templateGenerator->generateFileFromTemplate($templateName, $outputPath, $replacements);
    }

    private function generateModuleClassFile(string $moduleDir, string $moduleName): void
    {
        $templateName = 'modules/module_class.php.template';
        $outputPath = $moduleDir . '/' . $moduleName . 'Module.php';
        $replacements = [
            '{{ moduleName }}' => $moduleName,
            '{{ moduleClassName }}' => $moduleName . 'Module',
            '{{ className }}' => $moduleName,
            '{{ interfaceName }}' => $moduleName . 'Interface',
        ];
        $this->templateGenerator->generateFileFromTemplate($templateName, $outputPath, $replacements);
    }

    private function generateConfigFile(string $moduleDir, string $moduleName): void
    {
        $templateName = 'modules/config.php.template';
        $configFileName = strtolower(preg_replace('/(?<!^)([A-Z])/', '_\\1', $moduleName));
        $outputPath = $moduleDir . '/config/' . $configFileName . '.php';
        $replacements = [
            '{{ moduleName }}' => $moduleName,
        ];
        mkdir($moduleDir . '/config', 0755, true);
        $this->templateGenerator->generateFileFromTemplate($templateName, $outputPath, $replacements);
    }

    private function generateManifestFile(string $moduleDir, string $moduleName, string $moduleDescription, string $moduleVersion): void
    {
        $schemaPath = BASE_PATH . '/engine/Core/Schema/module-schema.json';
        $schemaContent = file_get_contents($schemaPath);
        if ($schemaContent === false) {
            $this->error("Error: Could not read module schema file from: {$schemaPath}");
            return;
        }
        $schema = json_decode($schemaContent, true);
        if ($schema === null && json_last_error() !== JSON_ERROR_NONE) {
            $this->error("Error: Invalid JSON in module schema file: {$schemaPath}");
            return;
        }

        $templateName = 'modules/forge.json.template';
        $outputPath = $moduleDir . '/forge.json';
        $replacements = [
            '{{ moduleName }}' => $moduleName,
            '{{ interfaceName }}' => $moduleName . 'Interface',
            '{{ moduleDescription }}' => $moduleDescription,
            '{{ moduleVersion }}' => $moduleVersion
        ];
        $this->templateGenerator->generateFileFromTemplate($templateName, $outputPath, $replacements);
    }
}
