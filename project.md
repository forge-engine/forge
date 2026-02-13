# Forge Kernel

**Application Hosted Kernel with Pluggable Capabilities** | PHP 8.3+ | Zero Dependencies

## Architecture Overview

```
forge-v3/
├── engine/              # Core kernel (DI, routing, view, events)
├── modules/             # Pluggable capability modules
├── app/                 # Application code (Controllers, Services, Models)
├── config/              # Configuration files
├── public/              # Web root
└── storage/             # Logs, cache, sessions
```

**Core Principle**: Capabilities (database, auth, ORM) are modules, not built-ins. Kernel stays lean (~400KB).

## Module System

Modules are first-class citizens in `modules/` folder. Each module:

- Has a `{ModuleName}Module.php` entry point class
- Uses attributes for metadata: `#[Module]`, `#[Service]`, `#[Compatibility]`
- Implements `register(Container $container)` for bindings
- Has lifecycle: install → register → boot

**Module Structure**:
```
modules/ForgeAuth/
├── ForgeAuthModule.php          # Entry point
└── src/
    ├── Controllers/             #[Service] + #[Route] attributes
    ├── Services/                # Business logic, #[Service] marked
    ├── Repositories/            # Data access, extends RecordRepository
    ├── Models/                  # Entities
    ├── Dto/                     # Data transfer objects
    ├── Middlewares/             # HTTP middleware
    ├── Commands/                # CLI commands
    ├── Resources/views/         # PHP view files (NOT Blade)
    └── Database/                # Migrations, seeders
```

## Key Conventions

### Classes
- **Suffixes**: `Controller`, `Service`, `Repository`, `Module`, `Middleware`, `Command`
- **Strict typing**: `declare(strict_types=1)` everywhere
- **Final classes**: Prefer `final class` for controllers/services
- **Readonly props**: Heavy use of PHP 8.1+ `readonly`

### Namespaces
- **App**: `App\Controllers`, `App\Services`, `App\Models`
- **Modules**: `App\Modules\{ModuleName}\{Component}`
- **Engine**: `Forge\Core\{Component}`, `Forge\Traits`

### Attributes (Auto-wiring)

```php
#[Service]                      # Auto-wire to DI container
#[Module(name: 'X', version: '1.0.0')]  # Module metadata
#[Route('/path', 'GET')]        # HTTP routes on controller methods
#[ApiRoute('/path')]             # Auto-prefix /api/v1
#[Middleware('web')]            # Middleware groups
#[Cache(key: 'user_{id}', ttl: 3600)]   # Method-level caching
#[NoCache]                      # Disable caching
```

### Dependency Injection

```php
// Constructor injection (preferred)
final class UserController {
    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepository $userRepository
    ) {}
}

// Container access
$userService = Container::get(UserService::class);
```

### View System (Native PHP)

**NOT Blade.** Views are plain PHP files with helper functions:

```php
<?php
layout(name: 'auth', fromModule: true, moduleName: 'ForgeAuth');
?>
<div><?= $variable ?></div>

<?= component(name: 'ForgeHub:input', props: ['name' => 'email']) ?>
<?= form_open(attrs: ['class' => 'space-y-6']) ?>
<?= form_close() ?>
```

**View Helpers**:
- `layout(name: string, fromModule?: bool, moduleName?: string)` - Set layout
- `component(name: string, props: array)` - Render component
- `form_open(attrs: array)`, `form_close()` - Form helpers
- `csrf_meta()` - CSRF token meta tag
- `raw(string)` - Output unescaped HTML

### Repository Pattern

```php
final class UserRepository extends RecordRepository implements UserRepositoryInterface {
    #[Cache(key: 'user_{id}', ttl: 3600)]
    public function findById(int $id): ?User {
        return $this->query()->where('id', $id)->first();
    }
}
```

**Rules**:
- Never access DB directly outside repositories
- Use DTOs for create/update: `CreateUserData`, `UpdateUserData`
- Cache at method level with attributes
- Manual invalidation: `$this->cache->invalidate('users')`

### Controllers

```php
#[Service]
#[Middleware('web')]
final class LoginController {
    use ControllerHelper;
    
    #[Route('/login', 'GET')]
    public function show(): Response {
        return $this->view(view: 'pages/login', data: ['title' => 'Login']);
    }
    
    #[Route('/login', 'POST')]
    public function store(LoginRequest $request): Response {
        // ...
        return Redirect::to('/dashboard');
    }
}
```

**Controller Helpers** (via `ControllerHelper` trait):
- `$this->view(view: string, data: array)` - Render view
- `$this->jsonResponse(data: array)` - JSON response
- `$this->apiResponse(data: array)` - API response wrapper
- `Redirect::to(path: string)` - Redirects

### Configuration

PHP arrays in `config/`:

```php
// config/app.php
return [
    'name' => 'Forge Kernel',
    'cors' => [
        'allowed_origins' => env('CORS_ALLOWED_ORIGINS', ['*']),
    ],
];
```

Access: `config('app.name')` or `env('APP_NAME', 'default')`

## Code Style Rules

1. **Performance first**: Avoid N+1, use indexed loops, O(n) preferred
2. **No magic**: Explicit over implicit, no autoloading surprises
3. **Semantic naming**: `assignRoleToUser()` not `handleRole()`
4. **Minimal comments**: Clear code > docblocks (but docblocks OK when human-written)
5. **No unnecessary complexity**: Simple solutions preferred
6. **Security**: Always validate, never trust user input

## CLI

Entry point: `forge.php` (or `php forge.php`)

```bash
php forge.php package:install-module --module=forge-auth
php forge.php generate:controller UserController
php forge.php generate:module MyModule
php forge.php migrate
```

## Key Files Reference

| File | Purpose |
|------|---------|
| `engine/Core/Engine.php` | Kernel bootstrap |
| `engine/Core/DI/Container.php` | DI container |
| `engine/Core/Routing/Router.php` | Route dispatch |
| `engine/Core/View/View.php` | View engine |
| `engine/Traits/ControllerHelper.php` | Controller utilities |
| `forge.json` | Module manifest |

## Testing

Modules include `tests/` folder. Use `forge-testing` module for test utilities.

## Remember

- **This is NOT Laravel**: No Blade, no Eloquent, no artisan
- **Kernel ≠ Framework**: You own the code, customize freely
- **Capabilities are optional**: Only install what you need
- **Strict types everywhere**: PHP 8.3+ features encouraged
