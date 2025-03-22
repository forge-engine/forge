<?php
declare(strict_types=1);

namespace Forge\Core\Module;

use Forge\CLI\Application;
use Forge\CLI\Command;
use Forge\Core\Autoloader;
use Forge\Core\Bootstrap;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\DI\Container;
use Forge\Core\Module\Attributes\CLICommand;
use Forge\Core\Module\Attributes\Compatibility;
use Forge\Core\Module\Attributes\ConfigDefaults;
use Forge\Core\Module\Attributes\LifecycleHook;
use Forge\Core\Module\Attributes\Module;
use Forge\Core\Module\Attributes\Provides;
use Forge\Core\Module\Attributes\Repository;
use Forge\Core\Module\Attributes\Requires;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RuntimeException;

#[Service]
final class ModuleLoader
{
	
	private array $modules;
	private array $moduleRequirements = [];
	/***
	 * @var Application $cliApplication
	 */
	
	public function __construct(
		private Container $container,
		private Config $config,
	)
	{
	}
	
	public function loadModules(): void
	{
		$moduleDirectory = BASE_PATH . '/modules';
		
		if (!is_dir($moduleDirectory)) {
			return;
		}
		
		$directories = array_filter(scandir($moduleDirectory), function($item) use ($moduleDirectory){
			return is_dir($moduleDirectory . '/' . $item) && !in_array($item, ['.', ['..']]);
		});
		
		foreach($directories as $directoryName) {
			$modulePath = $moduleDirectory . '/' . $directoryName;
			$this->loadModule($modulePath);
		}
		
		$this->checkModuleRequirements();
	}
	
	private function loadModule(string $modulePath): void
	{
		$srcPath = $modulePath . '/src';
		if (!is_dir($srcPath)) {
			return;
		}
	
		// Register the autoload path for this module *first*
		$moduleName = basename($modulePath);
		$this->registerModuleAutoloadPath($moduleName, $modulePath);
	
		$directoryIterator = new RecursiveDirectoryIterator($srcPath);
		$iterator = new RecursiveIteratorIterator($directoryIterator);
		$mainModuleReflectionClass = null;
	
		foreach ($iterator as $file) {
			if ($file->isFile() && $file->getExtension() === 'php') {
				$filePath = $file->getRealPath();
				$namespace = $this->getNamespaceFromFile($filePath, BASE_PATH);
				if ($namespace) {
					$filenameWithoutExtension = pathinfo($file->getFilename(), PATHINFO_FILENAME);
					$className = $namespace . '\\' . $filenameWithoutExtension;
					if (class_exists($className)) {
						try {
							$reflectionClass = new ReflectionClass($className);
							$attributes = $reflectionClass->getAttributes(\Forge\Core\Module\Attributes\Module::class);
	
							if (!empty($attributes)) {
								$moduleAttribute = $attributes[0];
								$moduleInstance = $moduleAttribute->newInstance();
								$this->registerModule($moduleName, $className, $modulePath, $moduleInstance);
								$mainModuleReflectionClass = $reflectionClass;
							}
						} catch (\ReflectionException $e) {
							error_log("Reflection Exception for class: " . $className . " - " . $e->getMessage());
						}
					} 
				}
			}
		}
	
		// Register lifecycle hooks for the main module class *after* finding it
		if ($mainModuleReflectionClass) {
			$this->registerModuleLifecycleHooks($mainModuleReflectionClass);
		}
	}
	
	private function registerModuleAutoloadPath(string $moduleName, string $modulePath): void
	{
		$moduleNamespacePrefix = 'App\\Modules\\' . str_replace('-', '\\', $moduleName);
		Autoloader::addPath($moduleNamespacePrefix . '\\', $modulePath . '/src/');
	}
	
	private function registerModule(string $moduleName, string $className, string $modulePath, Module $moduleAttributeInstance): void
	{
		$this->modules[$moduleName] = $className;
		$reflectionClass = new ReflectionClass($className);
	
		// Add module's source directory to the autoloader
		$moduleNamespacePrefix = 'App\\Modules\\' . str_replace('-', '\\', $moduleName);
		Autoloader::addPath($moduleNamespacePrefix . '\\', $modulePath . '/src/');
	
		// Register services
		$this->registerModuleServices($reflectionClass);
	
		// Register CLI commands
		$this->registerModuleCommands($reflectionClass, $moduleName);
	
		// Register configuration defaults
		$this->registerModuleConfig($reflectionClass);
	
		// Register lifecycle hooks
		$this->registerModuleLifecycleHooks($reflectionClass);
	
		// Register provides and requires
		$this->registerModuleProvidesAndRequires($reflectionClass);
	
		// Register compatibility
		$this->registerModuleCompatibility($reflectionClass, $moduleAttributeInstance);

		// Register repository
		$this->registerModuleRepository($reflectionClass, $moduleAttributeInstance);
		
		// Instantiate and register the main module class
		$moduleInstance = $this->container->make($className);
		if (method_exists($moduleInstance, 'register')) {
			$moduleInstance->register($this->container);
		}
	}
	
	private function registerModuleServices(ReflectionClass $reflectionClass): void
	{
		
		$moduleNamespace = $reflectionClass->getNamespaceName();
		$modulePath = dirname($reflectionClass->getFileName());
	
		$directoryIterator = new RecursiveDirectoryIterator($modulePath);
		$iterator = new RecursiveIteratorIterator($directoryIterator);
	
		foreach ($iterator as $file) {
			if ($file->isFile() && $file->getExtension() === 'php') {
				$filePath = $file->getRealPath();
				$fileNamespace = $this->getNamespaceFromFile($filePath, BASE_PATH);
				if (str_starts_with($fileNamespace, $moduleNamespace)) {
					$className = $fileNamespace . '\\' . pathinfo($file->getFilename(), PATHINFO_FILENAME);
					if (class_exists($className)) {
						$classReflection = new ReflectionClass($className);
						if ($classReflection->getAttributes(Service::class)) {
							$this->container->register($className);
						}
					}
				}
			}
		}
	}
	
	private function registerModuleCommands(ReflectionClass $reflectionClass, string $moduleName): void
	{
		$moduleNamespace = $reflectionClass->getNamespaceName();
		$modulePath = dirname($reflectionClass->getFileName());
	
		$directoryIterator = new RecursiveDirectoryIterator($modulePath);
		$iterator = new RecursiveIteratorIterator($directoryIterator);
		
		$cliApplication = $this->container->get(Application::class);
	
		foreach ($iterator as $file) {
			if ($file->isFile() && $file->getExtension() === 'php' && str_ends_with($file->getFilename(), 'Command.php')) {
				$filePath = $file->getRealPath();
				$fileNamespace = $this->getNamespaceFromFile($filePath, BASE_PATH);
				if (str_starts_with($fileNamespace, $moduleNamespace)) {
					$className = $fileNamespace . '\\' . pathinfo($file->getFilename(), PATHINFO_FILENAME);
					if (class_exists($className) && is_subclass_of($className, Command::class)) {
						$commandAttribute = (new ReflectionClass($className))->getAttributes(CLICommand::class)[0] ?? null;
						if ($commandAttribute) {
							$commandInstance = $commandAttribute->newInstance();
							$commandName = $commandInstance->name;
							$description = $commandInstance->description;
							$cliApplication->registerCommandClass($className, $commandName, $description);
						} else {
							error_log("CLICommand attribute not found on class: " . $className);
						}
					} else {
						error_log("Class " . $className . " is not a valid CLI Command.");
					}
				}
			}
		}
	}
	
	private function registerModuleConfig(ReflectionClass $reflectionClass): void
	{
		$configDefaultsAttribute = $reflectionClass->getAttributes(ConfigDefaults::class)[0] ?? null;
		if ($configDefaultsAttribute) {
			$configDefaults = $configDefaultsAttribute->newInstance()->defaults;
			$this->config->mergeModuleDefaults($configDefaults);
		}
	}
	
	private function registerModuleLifecycleHooks(ReflectionClass $reflectionClass): void
	{
		$moduleAttribute = $reflectionClass->getAttributes(Module::class)[0] ?? null;
		if ($moduleAttribute) {
			$moduleInstance = $this->container->make($reflectionClass->getName());
			foreach ($reflectionClass->getMethods() as $method) {
				$lifecycleHookAttributes = $method->getAttributes(LifecycleHook::class);
				foreach ($lifecycleHookAttributes as $attribute) {
					$hookInstance = $attribute->newInstance();
					$hookName = $hookInstance->hookName;
					$methodName = $method->getName();
					Bootstrap::addHook($hookName, [$moduleInstance, $methodName]);
				}
			}
		}
	}
	
	private function registerModuleProvidesAndRequires(ReflectionClass $reflectionClass): void
	{
		$moduleName = $reflectionClass->getShortName(); // Or get the name from the Module attribute
		
		// Handle #[Provides] attributes
		foreach ($reflectionClass->getAttributes(Provides::class) as $attribute) {
			$provideInstance = $attribute->newInstance();
			$this->container->bind($provideInstance->interface, $reflectionClass->getName());
		}
		
		// Handle #[Requires] attributes
		foreach ($reflectionClass->getAttributes(Requires::class) as $attribute) {
			$requireInstance = $attribute->newInstance();
			$this->moduleRequirements[$moduleName][$requireInstance->interface] = $requireInstance->version;
		}
	}
	
	private function checkModuleRequirements(): void
	{
		foreach ($this->moduleRequirements as $moduleName => $requirements) {
			foreach ($requirements as $interface => $version) {
				if (!$this->container->has($interface)) {
					throw new RuntimeException(
						"Module '{$moduleName}' requires service '{$interface}' (version {$version}) which is not provided."
					);
				}
				// You could add version checking logic here if needed
			}
		}
	}
	
	private function registerModuleCompatibility(ReflectionClass $reflectionClass, Module $moduleAttributeInstance): void
	{
		$compatibilityAttribute = $reflectionClass->getAttributes(Compatibility::class)[0] ?? null;
		if ($compatibilityAttribute) {
			$compatibilityInstance = $compatibilityAttribute->newInstance();
			$frameworkCompatibility = $compatibilityInstance->framework;
			$phpCompatibility = $compatibilityInstance->php;
	
			if ($frameworkCompatibility && !version_compare(FRAMEWORK_VERSION, $frameworkCompatibility, '>=')) {
				throw new RuntimeException(
					"Module '{$moduleAttributeInstance->name}' is not compatible with the current framework version. " .
					"Requires framework version: {$frameworkCompatibility}, current version: " . FRAMEWORK_VERSION
				);
			}
	
			if ($phpCompatibility && !version_compare(PHP_VERSION, $phpCompatibility, '>=')) {
				throw new RuntimeException(
					"Module '{$moduleAttributeInstance->name}' requires PHP version {$phpCompatibility} or higher. " .
					"Your current PHP version is " . PHP_VERSION
				);
			}
		}
	}
	
	private function registerModuleRepository(ReflectionClass $reflectionClass, Module $moduleAttributeInstance): void
	{
		$repositoryAttribute = $reflectionClass->getAttributes(Repository::class)[0] ?? null;
		if ($repositoryAttribute) {
			$repositoryInstance = $repositoryAttribute->newInstance();
			// You might want to store this information somewhere for later use (e.g., for module management)
			// $this->moduleRepositories[$moduleAttributeInstance->name] = ['type' => $repositoryInstance->type, 'url' => $repositoryInstance->url];
		}
	}
	
	private function getNamespaceFromFile(string $filePath, string $basePath): ?string
	{
		$content = file_get_contents($filePath);
		if (preg_match('#^namespace\s+(.+?);#sm', $content, $match)) {
			return trim($match[1]);
		}
		return null;
	}
	
	public function getModules(): array
	{
		return $this->modules;
	}
}