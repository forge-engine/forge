# Forge

**Forge is a PHP framework for builders, not followers.**

It's not trying to be the next Symfony, CodeIgniter, Laravel, etc. It's not here to please everyone. It's here because I
wanted a fast, simple, no-magic framework that puts *me* in control. That's what Forge is. If that works for you too,
welcome.

---

## Philosophy

Forge is not here to be everything for everyone.  
It’s not here to mimic other frameworks.  
It’s here to give you a strong, minimal foundation you can **own**.

You’re not a user. You’re a builder.

- If you use Forge, it belongs to you now. Your rules. Your way.
- You get a solid base, and updates if you want them.
- If my direction doesn’t fit yours — fork it, and forge your own path.
- I’ll keep publishing modules and improvements that help me build real-world apps. You’re free to take what helps,
  ignore what doesn’t.

This isn’t a product. This is a toolbox.

---

## What’s In The Box

- Simple, fast dependency injection container
- Native PHP view engine with layouts and components
- Modular structure (install only what you need)
- Zero dependencies, zero magic
- Built-in router, database, orm, configuration manager, and more
- Module system with life cycle hooks
- CLI for installing modules, project, scaffold commands etc.

Everything is structured for clarity. No magic files. No guesswork.

---

## Install

### With the Installer (Recommended)

```bash
bash <(curl -Ls https://raw.githubusercontent.com/forge-engine/installer/main/installer.sh)
```

### Manually

```bash
git clone https://github.com/forge-engine/forge-starter
cd forge-starter
cp env-example .env
php install.php
php forge.php key:generate
php forge.php package:install-project
```

---

## Modules

Forge starts minimal (under 400KB), and you add modules as needed.

```bash
php forge.php package:install-module forge-auth
php forge.php package:install-module forge-storage
```

Or clone from [github.com/forge-engine/modules](https://github.com/forge-engine/modules) and drop them in `/modules`.

I publish modules that help me build real-world projects. If something’s too specific, I won’t. Or I’ll release a
simplified version.

---

## Want to Make Your Own Framework?

Do it. I’ll even show you how.  
See: [`FORGING-YOUR-OWN.md`](./docs/FORGING-YOUR-OWN.md)

You can rename the CLI, change the bootstrap flow, use your own registry — everything is yours now. That’s the point.

---

## License

MIT — take it, use it, change it.  
Just don’t whine if it’s not what you expected.  
Nobody owes you anything. Build your own vision.

