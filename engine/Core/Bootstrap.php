<?php
declare(strict_types=1);

namespace Forge\Core;

use Forge\CLI\Application;
use Forge\CLI\Commands\HelpCommand;
use Forge\Core\Config\Config;
use Forge\Core\DI\Attributes\Service;
use Forge\Core\Database\{DatabaseConfig, Connection};
use Forge\Core\Config\Environment;
use Forge\Core\Config\EnvParser;
use Forge\Core\DI\Container;
use Forge\Core\Database\Migrator;
use Forge\Core\Http\Kernel;
use Forge\Core\Module\ModuleLoader;
use Forge\Core\Routing\{ControllerLoader, Router};
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

require_once('Version.php');

final class Bootstrap
{
    private const CLASS_MAP_CACHE_FILE =
        BASE_PATH . "/storage/framework/cache/class-map.php";
    private static array $hooks = [];
    
    private static bool $modulesLoaded = false;
    
    private static ?self $instance = null;
    
    private ?Kernel $kernel = null;
    
    private static bool $cliContainerSetup = false;
    
    private function  __construct()
    {
        $this->kernel = $this->init();
    }
    
    public static function getInstance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @throws \ReflectionException
     */
    private static function init(): Kernel
    {
        self::loadEnvironment();
        self::setupErrorHandling();
        self::initSession();

        self::setupDatabase();
        $container = self::setupContainer();
        $router = self::setupRouter($container);
        self::triggerHook('app.booted');

        return new Kernel($router, $container);
    }
    
    private static function initSession(): void
    {
        ini_set('session.cookie_httponly', true);
        ini_set('session.cookie_secure', true);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', true);
        ini_set('session.use_only_cookies', true);
    }

    private static function loadEnvironment(): void
    {
        $envPath = BASE_PATH . "/.env";

        if (file_exists($envPath)) {
            EnvParser::load($envPath);
        }
        Environment::getInstance();
    }

    private static function setupErrorHandling(): void
    {
        ini_set(
            "display_errors",
            Environment::getInstance()->isDevelopment() ? "1" : "0"
        );
        error_reporting(E_ALL);
    }

    public static function shouldCacheViews(): bool
    {
        return Environment::getInstance()->get("VIEW_CACHE") &&
            !Environment::getInstance()->isDevelopment();
    }

    private static function setupDatabase(): void
    {
        if (!is_dir(BASE_PATH . "/storage/database")) {
            mkdir(BASE_PATH . "/storage/database", 0755, true);
        }
    }

    public static function setupContainer(): Container
    {
        
        $env = Environment::getInstance();

        $container = Container::getInstance();
            
        $container->singleton(Config::class, function() {
            return new Config(BASE_PATH . '/config');
        });
        
        $container->singleton(Application::class, function() use ($container){
            $application = Application::getInstance($container);
            return $application;
        });

        self::initConnection($container, $env);
        self::loadModulesOnce($container);
        
        self::autoDiscoverServices($container);

        return $container;
    }
    public static function setupCliContainer(): Container
    {
        if (self::$cliContainerSetup) {
            return Container::getInstance();
        }
        
        
        $env = Environment::getInstance();
        $container = Container::getInstance();
            
        $container->singleton(Config::class, function() {
            return new Config(BASE_PATH . '/config');
        });
        
        $container->singleton(Application::class, function() use ($container) {
            $application = Application::getInstance($container);
            return $application;
        });

        self::initConnection($container, $env);
        self::loadModulesOnce($container);

        $container->singleton(Migrator::class, function () use ($container) {
            return new Migrator($container->get(Connection::class));
        });

        $container->singleton(HelpCommand::class, function () use ($container) {
            return new HelpCommand($container);
        });

        self::autoDiscoverServices($container);
            
        self::$cliContainerSetup = true;
  
        return $container;
    }
    
    private static function loadModulesOnce(Container $container): void
    {
        if (!self::$modulesLoaded) {
            self::initModules($container);
            self::$modulesLoaded = true;
        }
    }
    
    private static function initModules(Container $container): void
    {
        $container->singleton(ModuleLoader::class, function() use ($container) {
            return new ModuleLoader(
                container: $container,
                config: $container->get(Config::class)
            );
        });
        
        /*** @var ModuleLoader $moduleLoader */
        $moduleLoader = $container->get(ModuleLoader::class);
        $moduleLoader->loadModules();
    }

    private static function initConnection(
        Container $container,
        Environment $env
    ): void {
        $container->singleton(DatabaseConfig::class, function () use ($env) {
            $env->get("DB_DRIVER") . "\n";
            return new DatabaseConfig(
                driver: $env->get("DB_DRIVER"),
                database: $env->get("DB_DRIVER") === "sqlite"
                    ? BASE_PATH . $env->get("DB_NAME")
                    : $env->get("DB_NAME"),
                host: $env->get("DB_HOST"),
                username: $env->get("DB_USER"),
                password: $env->get("DB_PASS"),
                port: $env->get("DB_PORT")
            );
        });

        $container->singleton(Connection::class, function () use ($container) {
            $config = $container->get(DatabaseConfig::class);
            return new Connection($config);
        });

        $container->singleton(PDO::class, function () use ($container) {
            $connection = $container->get(Connection::class);
            return $connection->getPdo();
        });
    }

    private static function autoDiscoverServices(Container $container): void
    {
        $classMap = self::loadClassMapCache();
    
        if ($classMap) {
            foreach ($classMap as $class => $filepath) {
                if (class_exists($class)) {
                    try {
                        $reflectionClass = new ReflectionClass($class);
                        if (
                            !$reflectionClass->isInterface() &&
                            !$reflectionClass->isAbstract() &&
                            !empty($reflectionClass->getAttributes(Service::class))
                        ) {
                            $container->register($class);
                        }
                    } catch (\ReflectionException $e) {
                        // Handle reflection exceptions (e.g., class not found)
                    }
                }
            }
            return;
        }
    
        $serviceDirectories = [
            BASE_PATH . "/app/Repositories",
            BASE_PATH . "/app/Middlewares",
            BASE_PATH . "/app/Services",
            BASE_PATH . "/engine/Core/Database",
            BASE_PATH . "/engine/Core/Http/Middlewares",
        ];
    
        $newClassMap = [];
    
        foreach ($serviceDirectories as $directory) {
            if (is_dir($directory)) {
                $directoryIterator = new RecursiveDirectoryIterator($directory);
                $iterator = new RecursiveIteratorIterator($directoryIterator);
    
                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === "php") {
                        $filepath = $file->getPathname();
    
                        if (strpos($filepath, '/config/') !== false) {
                            continue;
                        }
    
                        $class = self::fileToClass($filepath, BASE_PATH);
                        if (class_exists($class)) {
                            try {
                                $reflectionClass = new ReflectionClass($class);
                                if (
                                    !$reflectionClass->isInterface() &&
                                    !$reflectionClass->isAbstract() &&
                                    !empty($reflectionClass->getAttributes(Service::class))
                                ) {
                                    $container->register($class);
                                    $newClassMap[$class] = $filepath;
                                }
                            } catch (\ReflectionException $e) {
                                // Handle reflection exceptions
                            }
                        }
                    }
                }
            }
        }
        self::generateClassMapCache($newClassMap);
    }

    /**
     * Helper function to convert file path to class name
     */
private static function fileToClass(
         string $filepath,
         string $basePath
     ): string {
         $relativePath = str_replace($basePath, "", $filepath);
         $class = str_replace([".php", "/"], ["", "\\"], $relativePath);
     
         $class = ltrim($class, "\\");
         if (str_starts_with($class, "engine\\Core\\")) {
             $class = str_replace("engine\\Core\\", "Forge\\Core\\", $class);
         } elseif (str_starts_with($class, "app\\")) {
             $class = str_replace("app\\", "App\\", $class);
         }
         // Remove the elseif for modules
         return $class;
     }

    /**
     * @throws \ReflectionException
     */
    private static function setupRouter(Container $container): Router
    {
        $loader = new ControllerLoader(
            $container,
            BASE_PATH . "/app/Controllers"
        );
        $loader->registerControllers();

        $router = new Router($container);
        foreach ($container->getServiceIds() as $id) {
            if (str_starts_with($id, "App\Controllers")) {
                $router->registerControllers($id);
            }
        }

        return $router;
    }

    /**
     * Loads the class map from cache if it exists and is valid.
     * @return array<string, string>|null Class map array or null if cache is not found or invalid.
     */
    private static function loadClassMapCache(): ?array
    {
        if (file_exists(self::CLASS_MAP_CACHE_FILE)) {
            try {
                $cachedData = include self::CLASS_MAP_CACHE_FILE;
                if (is_array($cachedData)) {
                    return $cachedData;
                }
            } catch (\Exception $e) {
            }
        }
        return null;
    }

    /**
     * Generates and caches the class map to a file.
     * @param array<string, string> $classMap
     */
    private static function generateClassMapCache(array $classMap): void
    {
        if (!is_dir(dirname(self::CLASS_MAP_CACHE_FILE))) {
            mkdir(dirname(self::CLASS_MAP_CACHE_FILE), 0777, true);
        }

        $cacheContent = "<?php return " . var_export($classMap, true) . ";";
        file_put_contents(self::CLASS_MAP_CACHE_FILE, $cacheContent);
    }
    
   public static function addHook(string $hookName, callable|array $callback): void
   {
       if (is_callable($callback)) {
           self::$hooks[$hookName][] = $callback;
       } elseif (is_array($callback) && isset($callback[0]) && isset($callback[1])) {
           self::$hooks[$hookName][] = $callback;
       }
   }
    
    public static function triggerHook(string $hookName, ...$args): void
    {
        if (isset(self::$hooks[$hookName])) {
            foreach (self::$hooks[$hookName] as $callback) {
                if (is_callable($callback)) {
                    call_user_func_array($callback, $args);
                } elseif (is_array($callback) && count($callback) === 2) {
                    call_user_func_array($callback, $args);
                } else {
                    error_log("Invalid callback format: " . print_r($callback, true));
                }
            }
        } else {
            error_log("No Hooks Registered for: " . $hookName);
        }
    }
    
    public function getKernel(): ?Kernel
    {
        return $this->kernel;
    }
}
