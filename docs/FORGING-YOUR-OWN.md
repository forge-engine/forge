# FORGING-YOUR-OWN.md

So you want to build your own version of Forge?
Awesome — this doc will walk you through exactly what to fork, what to change, and how to get your own system up and running.

---

## Overview
Forge is modular and transparent by design, and everything lives in public repositories under the [forge-engine](https://github.com/forge-engine) GitHub org.

You can fork any part — just the modules, just the starter, or the whole system.

If you're going all in, here's what to do.

---

## 🧬 Repositories to Fork

To fully own and rebrand your Forge stack, fork the following repositories:

### Core Framework
- [forge-engine/framework](https://github.com/forge-engine/framework) – the minimal core framework (DI, routing, bootstrapping, routing, view engine, database and orm)

### Registry
- [forge-engine/framework-registry](https://github.com/forge-engine/framework-registry) – maps module names to GitHub URLs (used by the package manager)

### Starter Template
- [forge-engine/forge-starter](https://github.com/forge-engine/forge-starter) – a ready-to-go project template with minimal setup

### Modules
- [forge-engine/modules](https://github.com/forge-engine/modules) – all optional, official Forge modules live here (auth, storage, logger, etc.)

### Installer
- [forge-engine/installer](https://github.com/forge-engine/installer) – bash script for bootstrapping a new project quickly

### (Optional) Docs
- [forge-engine/forge-engine.github.io](https://github.com/forge-engine/forge-engine.github.io) – the documentation site
- [forge-engine/forge-schemas](https://github.com/forge-engine/forge-schemas) – schema definitions for module manifests, etc.

### Main Dev Repo
- [forge-engine/forge](https://github.com/forge-engine/forge) – the full repo containing all modules, installer, and framework glued together. This is mostly for local dev.

---

## ⚙️ What to Change

### 1. Package Manager Service
In your fork of the framework, update the `PackageManagerService`:

```php
#[Service]
#[Provides(interface: PackageManagerInterface::class, version: '0.1.0')]
#[Requires()]
final class PackageManagerService implements PackageManagerInterface
{
	use OutputHelper;

	private const OFFICIAL_REGISTRY_NAME = 'your-org-modules';
	private const OFFICIAL_REGISTRY_BASE_URL = 'https://github.com/your-org/modules';
	private const OFFICIAL_REGISTRY_BRANCH = 'main';
	private const FRAMEWORK_MODULE_NAME = 'your-org/framework';
	private const PACKAGE_MANAGER_MODULE_NAME = 'forge-package-manager'; // Or rename it
}
```

### 2. Installer Script
In your fork of the installer repo, edit `installer.sh`:

```bash
STARTER_REPO_BASE_URL="https://github.com/your-org/forge-starter/archive/refs/heads/main.zip"
```

### 3. install.php Scripts
In both:
- `forge/installer/install.php`
- `forge-starter/install.php`

Update the framework URL:

```php
const FRAMEWORK_REPO_URL = 'https://github.com/your-org/framework-registry';
```

### 4. Starter Updates
In your `forge-starter` fork:
- Replace the `forge-package-manager` module with your version (after modifying it)
- Update `.env.example`, `composer.json`, and docs if needed

### 5. Optional Module Prefix Rename
If you want, you can rename all module prefixes from `Forge` to something else. This takes some work:
- Rename namespaces and folder names in each module
- Adjust module manifest files (`forge.json`)
- Update your registry entries

I might release a script to help with this — or maybe not. Depends on time and demand.

---

## ✅ Done
After forking, updating URLs, and optionally renaming modules — you’ve got your own framework stack.

At this point, Forge becomes a base you control fully. Fork it, ship it, and evolve it how you want.

You're not a user. You're a builder.

---

MIT licensed. Take what helps. Ignore what doesn’t.

