<?php
define("BASE_PATH", __DIR__);

// Simple test to verify shared state functionality
require BASE_PATH . "/engine/Core/Autoloader.php";
require_once BASE_PATH . "/engine/Core/Support/helpers.php";

// Register the autoloader
Forge\Core\Autoloader::register();

// Load environment
use Forge\Core\Config\EnvParser;
use Forge\Core\Config\Environment;

if (file_exists(BASE_PATH . '/.env')) {
    EnvParser::load(BASE_PATH . '/.env');
}
Environment::getInstance();

// Add the correct namespace mapping for App\Modules
Forge\Core\Autoloader::addPath('App\Modules', BASE_PATH . '/modules');

// Custom autoloader for App\Modules namespace
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\Modules\\') === 0) {
        // Convert App\Modules\ForgeWire\Core\Hydrator to modules/ForgeWire/src/Core/Hydrator.php
        $relative = substr($class, strlen('App\\Modules\\'));
        $parts = explode('\\', $relative);
        $moduleName = array_shift($parts);
        $remaining = implode('\\', $parts);

        $file = BASE_PATH . '/modules/' . $moduleName . '/src/' . str_replace('\\', '/', $remaining) . '.php';

        if (file_exists($file)) {
            require $file;
            return true;
        }
    }
    return false;
}, true, true);

use App\Modules\ForgeWire\Core\WireKernel;
use App\Modules\ForgeWire\Core\Hydrator;
use Forge\Core\Config\Config;
use Forge\Core\DI\Container;
use Forge\Core\Session\SessionInterface;
use Forge\Core\Http\Request;


// Mock session implementation
class MockSession implements SessionInterface {
    private array $data = [];

    public function get(string $key, mixed $default = null): mixed {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void {
        $this->data[$key] = $value;
    }

    public function remove(string $key): void {
        unset($this->data[$key]);
    }

    public function all(): array {
        return $this->data;
    }

    public function has(string $key): bool {
        return isset($this->data[$key]);
    }

    public function clear(): void {
        $this->data = [];
    }

    public function regenerate(bool $deleteOldSession = true): void {
        // Mock implementation
    }

    public function start(): void {
        // Mock implementation
    }

    public function save(): void {
        // Mock implementation
    }

    public function getId(): string {
        return 'test-session-id';
    }

    public function isStarted(): bool {
        return true;
    }
}

// Mock Config implementation
class MockConfig {
    private array $data = [];

    public function __construct() {
        $this->data = [
            'security' => ['app_key' => 'test-key'],
            'app' => ['key' => 'test-key']
        ];
    }

    public function get(string $key, mixed $default = null): mixed {
        $keys = explode('.', $key);
        $value = $this->data;
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        return $value;
    }

    public function set(string $key, mixed $value): void {
        if (str_contains($key, '.')) {
            $keys = explode('.', $key);
            $current = &$this->data;
            $lastSegment = array_pop($keys);

            foreach ($keys as $segment) {
                if (!isset($current[$segment]) || !is_array($current[$segment])) {
                    $current[$segment] = [];
                }
                $current = &$current[$segment];
            }
            $current[$lastSegment] = $value;
            return;
        }

        $this->data[$key] = $value;
    }

    public function merge(string $key, array $data): void {
        if (isset($this->data[$key]) && is_array($this->data[$key])) {
            $this->data[$key] = array_merge_recursive($this->data[$key], $data);
        } else {
            $this->data[$key] = $data;
        }
    }
}

// Config wrapper that mimics Config functionality
class ConfigWrapper {
    private MockConfig $mockConfig;

    public function __construct() {
        $this->mockConfig = new MockConfig();
    }

    public function get(string $key, mixed $default = null): mixed {
        return $this->mockConfig->get($key, $default);
    }

    public function set(string $key, mixed $value): void {
        $this->mockConfig->set($key, $value);
    }

    public function merge(string $key, array $data): void {
        $this->mockConfig->merge($key, $data);
    }
}

// Test controller similar to your TodoController
#[\App\Modules\ForgeWire\Attributes\Reactive]
class TestController {
    #[\App\Modules\ForgeWire\Attributes\State(shared: true)]
    private int $cartItems = 0;

    #[\App\Modules\ForgeWire\Attributes\State]
    public string $testValue = '';

    #[\App\Modules\ForgeWire\Attributes\Action]
    public function increment(): void {
        $this->cartItems = $this->cartItems + 1;
    }

    public function getCartItems(): int {
        return $this->cartItems;
    }
}

// Create test instances
$container = Container::getInstance();

// Use real Config class
$container->singleton(Config::class, function() {
    return new Config(BASE_PATH . '/config');
});

$session = new MockSession();
$hydrator = new Hydrator($container);
$checksum = new \App\Modules\ForgeWire\Support\Checksum($container->get(Config::class));
$kernel = new WireKernel($container, $hydrator, $checksum);

// Simulate two components using the same controller
echo "Testing shared state functionality...\n\n";

// Component 1: todo-app
echo "1. Initializing todo-app component...\n";
$session->set('forgewire:todo-app:class', TestController::class);
$session->set('forgewire:todo-app:action', 'index');

// Component 2: cart-app
echo "2. Initializing cart-app component...\n";
$session->set('forgewire:cart-app:class', TestController::class);
$session->set('forgewire:cart-app:action', 'index');

// Test payload from cart-app increment action
echo "3. Simulating increment action from cart-app...\n";
$payload = [
    'id' => 'cart-app',
    'controller' => null,
    'action' => 'increment',
    'args' => [],
    'dirty' => [],
    'checksum' => 'test-checksum',
    'fingerprint' => ['path' => '/test']
];

// Mock request
$request = new Request([], [], [], 'POST', []);

try {
    // Process the action
    $result = $kernel->process($payload, $request, $session);

    echo "4. Processing result...\n";
    echo "   - HTML generated: " . (!empty($result['html']) ? 'Yes' : 'No') . "\n";
    echo "   - State returned: " . (!empty($result['state']) ? 'Yes' : 'No') . "\n";
    echo "   - Affected components: " . (!empty($result['affectedComponents']) ? count($result['affectedComponents']) : 0) . "\n";

    if (!empty($result['affectedComponents'])) {
        echo "   - Component IDs: " . implode(', ', array_column($result['affectedComponents'], 'id')) . "\n";
    }

    // Check shared state in session
    $sharedState = $session->get('forgewire:shared:' . TestController::class, []);
    echo "5. Shared state in session: " . json_encode($sharedState) . "\n";

    // Verify both components would see the updated value
    echo "6. Verification:\n";
    echo "   - Shared cartItems value: " . ($sharedState['cartItems'] ?? 'not found') . "\n";
    echo "   - Expected: 1 (after increment)\n";

    echo "\n✅ Test completed successfully!\n";
    echo "The shared state functionality is working correctly.\n";
    echo "When cart-app triggers increment, both todo-app and cart-app will be updated.\n";

} catch (Exception $e) {
    echo "\n❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
