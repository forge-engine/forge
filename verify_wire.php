<?php

define('BASE_PATH', __DIR__);
require_once __DIR__ . "/engine/Core/Support/helpers.php";
require_once __DIR__ . "/engine/Core/Autoloader.php";
\Forge\Core\Autoloader::register();
\Forge\Core\Autoloader::addPath('App\\Modules\\ForgeWire\\', __DIR__ . '/modules/ForgeWire/src/');
\Forge\Core\Autoloader::addPath('App\\', __DIR__ . '/app/');

// Mock container and dependencies
use Forge\Core\DI\Container;
use Forge\Core\Config\Config;
use App\Modules\ForgeWire\Core\WireKernel;
use Forge\Core\Session\SessionInterface;

$container = Container::getInstance();

$container->singleton(Config::class, function () {
    $config = new Config(BASE_PATH . '/config');
    if (!$config->get('security.app_key')) {
        $config->set('security.app_key', 'test-key-for-forgewire-verification');
    }
    return $config;
});

// Mock Request
$request = new \Forge\Core\Http\Request(
    queryParams: [],
    postData: [],
    serverParams: ['HTTP_HOST' => 'localhost', 'REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/search'],
    requestMethod: 'POST',
    cookies: []
);
$container->setInstance(\Forge\Core\Http\Request::class, $request);

// Mock Session
$session = new class implements SessionInterface {
    private $data = [];
    public function start(): void
    {
    }
    public function save(): void
    {
    }
    public function isStarted(): bool
    {
        return true;
    }
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }
    public function remove(string $key): void
    {
        unset($this->data[$key]);
    }
    public function all(): array
    {
        return $this->data;
    }
    public function clear(): void
    {
        $this->data = [];
    }
    public function getId(): string
    {
        return 'test-session';
    }
    public function regenerate(bool $deleteOldSession = true): void
    {
    }
};

$container->setInstance(SessionInterface::class, $session);

$kernel = $container->make(WireKernel::class);

$payload = [
    'id' => 'search-id',
    'controller' => 'App\Controllers\SearchController',
    'action' => 'index',
    'dirty' => ['query' => 'apple'],
    'checksum' => null, // First request
    'fingerprint' => ['path' => '/search']
];

try {
    $result = $kernel->process($payload, $session);
    echo "--- Result HTML ---\n";
    echo $result['html'] . "\n";
    echo "--- Result State ---\n";
    print_r($result['state']);
    echo "--- End ---\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
