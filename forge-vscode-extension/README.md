# Forge Framework VS Code Extension

A VS Code extension that provides go-to-definition support for Forge framework views, components, and layouts. Compatible with VS Code, Cursor, Antigravity, and other VS Code-based editors.

## Features

- **Go-to-Definition for Views**: Command-click (Cmd+Click on macOS, Ctrl+Click on Windows/Linux) on view paths in controllers to navigate to view files
- **Go-to-Definition for Components**: Navigate to component files from component calls in views
- **Go-to-Definition for Layouts**: Navigate to layout files from layout declarations
- **Module Support**: Full support for module-based views, components, and layouts
- **Multi-platform**: Works on macOS, Linux, and Windows

## Installation

### From Source

1. Clone or navigate to the `forge-vscode-extension` directory
2. Install dependencies:
   ```bash
   npm install
   ```
3. Compile the extension:
   ```bash
   npm run compile
   ```
4. Press `F5` in VS Code to open a new window with the extension loaded, or package it for distribution

### Packaging for Distribution

1. Install vsce (VS Code Extension manager):
   ```bash
   npm install -g @vscode/vsce
   ```
2. Package the extension:
   ```bash
   vsce package
   ```
3. Install the generated `.vsix` file:
   - Open VS Code
   - Go to Extensions view (Cmd+Shift+X / Ctrl+Shift+X)
   - Click the `...` menu and select "Install from VSIX..."
   - Select the generated `.vsix` file

## Usage

### Views from Controllers

In your controller files, you can command-click on view paths:

```php
// Named parameter
return $this->view(view: "pages/home/index", data: $data);

// Positional parameter
return $this->view("pages/home/index", $data);
```

This will navigate to: `app/resources/views/pages/home/index.php`

### Components

In your view files, you can command-click on component references:

```php
// Module component
<?= component('ForgeUi:notifications') ?>

// App component (named parameter)
<?= component(name: 'ui/footer', slots: $footerSlots) ?>

// App component (positional parameter)
<?= component('ui/footer', $props) ?>
```

This will navigate to:
- Module: `modules/ForgeUi/src/Resources/components/notifications.php`
- App: `app/resources/components/ui/footer.php`

### Layouts

In your view files, you can command-click on layout names:

```php
// App layout
<?php layout('main') ?>
<?php layout(name: "main") ?>

// Module layout (current syntax)
<?php layout(name: "nexus", fromModule: true) ?>
<?php layout(name: "nexus", fromModule: true, moduleName: "ForgeNexus") ?>

// Module layout (refactored syntax)
<?php layout(name: "ForgeNexus:nexus") ?>
```

This will navigate to:
- App: `app/resources/views/layouts/main.php`
- Module: `modules/ForgeNexus/src/Resources/views/layouts/nexus.php`

## Supported Patterns

### View Patterns
- `$this->view(view: "path/to/view", ...)`
- `$this->view("path/to/view", ...)`

### Component Patterns
- `component('ModuleName:component')`
- `component(name: 'path/to/component', ...)`
- `component('path/to/component', ...)`
- `<?= component(...) ?>` (short echo syntax)

### Layout Patterns
- `layout('name')`
- `layout(name: "name")`
- `layout(name: "name", fromModule: true)`
- `layout(name: "name", fromModule: true, moduleName: "ModuleName")`
- `layout(name: "ModuleName:name")` (refactored syntax)

## Path Resolution

The extension follows Forge framework conventions:

- **Views**: `app/resources/views/{path}.php`
- **App Components**: `app/resources/components/{path}.php`
- **Module Components**: `modules/{ModuleName}/src/Resources/components/{path}.php` or `modules/{ModuleName}/src/resources/components/{path}.php`
- **App Layouts**: `app/resources/views/layouts/{name}.php`
- **Module Layouts**: `modules/{ModuleName}/src/Resources/views/layouts/{name}.php` or `modules/{ModuleName}/src/Resources/views/{name}.php`

The extension checks both `Resources` and `resources` directory variants for module files.

## Requirements

- VS Code 1.74.0 or higher
- Forge framework project (detected by presence of `forge.json` or `engine/` directory)

## Development

### Project Structure

```
forge-vscode-extension/
├── src/
│   ├── extension.ts          # Main entry point
│   ├── definitionProvider.ts # Definition provider implementation
│   └── pathResolver.ts       # Path resolution logic
├── package.json              # Extension manifest
├── tsconfig.json            # TypeScript configuration
└── README.md                # This file
```

### Building

```bash
# Compile TypeScript
npm run compile

# Watch mode for development
npm run watch
```

### Testing

1. Open the extension folder in VS Code
2. Press `F5` to launch a new Extension Development Host window
3. Open a Forge project in the new window
4. Test go-to-definition by command-clicking on view paths, components, and layouts

## License

[Add your license here]

## Contributing

[Add contribution guidelines here]
