import * as vscode from 'vscode';
import * as path from 'path';
import * as fs from 'fs';

export interface ResolvedPath {
    filePath: string;
    exists: boolean;
}

export class PathResolver {
    private workspaceRoot: string;

    constructor(workspaceRoot: string) {
        this.workspaceRoot = workspaceRoot;
    }

    /**
     * Resolve view path from controller
     * Pattern: $this->view(view: "pages/home/index", ...) or $this->view("pages/home/index", ...)
     * Resolution: app/resources/views/pages/home/index.php
     */
    resolveView(viewPath: string): ResolvedPath | null {
        const viewFile = path.join(
            this.workspaceRoot,
            'app',
            'resources',
            'views',
            `${viewPath}.php`
        );

        return {
            filePath: viewFile,
            exists: fs.existsSync(viewFile)
        };
    }

    /**
     * Resolve component path
     * Module: component('ForgeUi:notifications') -> modules/ForgeUi/src/Resources/components/notifications.php
     * App: component(name: 'ui/footer', ...) -> app/resources/components/ui/footer.php
     */
    resolveComponent(componentName: string, currentFile?: string): ResolvedPath | null {
        // Check if it's a module component (ModuleName:component)
        if (componentName.includes(':')) {
            const [moduleName, componentPath] = componentName.split(':', 2);
            return this.resolveModuleComponent(moduleName, componentPath);
        }

        // App component
        const componentFile = path.join(
            this.workspaceRoot,
            'app',
            'resources',
            'components',
            `${componentName}.php`
        );

        return {
            filePath: componentFile,
            exists: fs.existsSync(componentFile)
        };
    }

    /**
     * Resolve module component
     */
    private resolveModuleComponent(moduleName: string, componentPath: string): ResolvedPath | null {
        // Try both Resources and resources directory variants
        const resourceVariants = ['Resources', 'resources'];
        
        for (const resourceVariant of resourceVariants) {
            // Try components directory first
            let componentFile = path.join(
                this.workspaceRoot,
                'modules',
                moduleName,
                'src',
                resourceVariant,
                'components',
                `${componentPath}.php`
            );

            if (fs.existsSync(componentFile)) {
                return {
                    filePath: componentFile,
                    exists: true
                };
            }

            // Try views/components directory
            componentFile = path.join(
                this.workspaceRoot,
                'modules',
                moduleName,
                'src',
                resourceVariant,
                'views',
                'components',
                `${componentPath}.php`
            );

            if (fs.existsSync(componentFile)) {
                return {
                    filePath: componentFile,
                    exists: true
                };
            }
        }

        // Return the first path we tried even if it doesn't exist
        return {
            filePath: path.join(
                this.workspaceRoot,
                'modules',
                moduleName,
                'src',
                'Resources',
                'components',
                `${componentPath}.php`
            ),
            exists: false
        };
    }

    /**
     * Resolve layout path
     * App: layout('main') -> app/resources/views/layouts/main.php
     * Module (current): layout(name: "nexus", fromModule: true) -> modules/{currentModule}/src/Resources/views/layouts/nexus.php
     * Module (with moduleName): layout(name: "nexus", fromModule: true, moduleName: "ForgeNexus") -> modules/ForgeNexus/src/Resources/views/layouts/nexus.php
     * Module (refactored): layout(name: "ForgeNexus:nexus") -> modules/ForgeNexus/src/Resources/views/layouts/nexus.php
     */
    resolveLayout(
        layoutName: string,
        fromModule: boolean = false,
        moduleName?: string,
        currentFile?: string
    ): ResolvedPath | null {
        // Check if it's the refactored syntax (ModuleName:layout)
        if (layoutName.includes(':')) {
            const [module, layout] = layoutName.split(':', 2);
            return this.resolveModuleLayout(module, layout);
        }

        // App layout
        if (!fromModule) {
            const layoutFile = path.join(
                this.workspaceRoot,
                'app',
                'resources',
                'views',
                'layouts',
                `${layoutName}.php`
            );

            return {
                filePath: layoutFile,
                exists: fs.existsSync(layoutFile)
            };
        }

        // Module layout
        let targetModule: string | undefined = moduleName;
        
        // If no moduleName specified, try to detect from current file
        if (!targetModule && currentFile) {
            const detected = this.detectModuleFromPath(currentFile);
            targetModule = detected || undefined;
        }

        if (targetModule) {
            return this.resolveModuleLayout(targetModule, layoutName);
        }

        // Fallback: return a path even if we can't determine the module
        return {
            filePath: path.join(
                this.workspaceRoot,
                'modules',
                'Unknown',
                'src',
                'Resources',
                'views',
                'layouts',
                `${layoutName}.php`
            ),
            exists: false
        };
    }

    /**
     * Resolve module layout
     */
    private resolveModuleLayout(moduleName: string, layoutName: string): ResolvedPath | null {
        const resourceVariants = ['Resources', 'resources'];

        for (const resourceVariant of resourceVariants) {
            // Try layouts subdirectory first
            let layoutFile = path.join(
                this.workspaceRoot,
                'modules',
                moduleName,
                'src',
                resourceVariant,
                'views',
                'layouts',
                `${layoutName}.php`
            );

            if (fs.existsSync(layoutFile)) {
                return {
                    filePath: layoutFile,
                    exists: true
                };
            }

            // Try directly in views directory (without layouts folder)
            layoutFile = path.join(
                this.workspaceRoot,
                'modules',
                moduleName,
                'src',
                resourceVariant,
                'views',
                `${layoutName}.php`
            );

            if (fs.existsSync(layoutFile)) {
                return {
                    filePath: layoutFile,
                    exists: true
                };
            }
        }

        // Return the first path we tried even if it doesn't exist
        return {
            filePath: path.join(
                this.workspaceRoot,
                'modules',
                moduleName,
                'src',
                'Resources',
                'views',
                'layouts',
                `${layoutName}.php`
            ),
            exists: false
        };
    }

    /**
     * Detect module name from file path
     * e.g., modules/ForgeNexus/src/Resources/views/pages/dashboard.php -> ForgeNexus
     */
    private detectModuleFromPath(filePath: string): string | null {
        const normalizedPath = path.normalize(filePath);
        const modulesMatch = normalizedPath.match(/modules[\/\\]([^\/\\]+)[\/\\]src/);
        
        if (modulesMatch && modulesMatch[1]) {
            return modulesMatch[1];
        }

        return null;
    }

    /**
     * Check if workspace is a Forge framework project
     */
    static isForgeProject(workspaceRoot: string): boolean {
        const forgeJson = path.join(workspaceRoot, 'forge.json');
        const engineDir = path.join(workspaceRoot, 'engine');
        
        return fs.existsSync(forgeJson) || fs.existsSync(engineDir);
    }
}
