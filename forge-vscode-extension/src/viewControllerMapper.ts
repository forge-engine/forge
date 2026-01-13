import * as vscode from 'vscode';
import * as fs from 'fs';
import * as path from 'path';
import { ControllerResolver, ControllerMetadata } from './controllerResolver';

export class ViewControllerMapper {
    private resolver: ControllerResolver;
    private cache: Map<string, ControllerMetadata | null> = new Map();
    private workspaceRoot: string;

    constructor(workspaceRoot: string, resolver: ControllerResolver) {
        this.workspaceRoot = workspaceRoot;
        this.resolver = resolver;
    }

    getControllerForView(viewPath: string): ControllerMetadata | null {
        if (this.cache.has(viewPath)) {
            return this.cache.get(viewPath)!;
        }

        const controller = this.findControllerForView(viewPath);
        this.cache.set(viewPath, controller);
        return controller;
    }

    private findControllerForView(viewPath: string): ControllerMetadata | null {
        const normalizedPath = path.isAbsolute(viewPath)
            ? viewPath
            : path.join(this.workspaceRoot, viewPath);

        if (!fs.existsSync(normalizedPath)) {
            return null;
        }

        const viewContent = fs.readFileSync(normalizedPath, 'utf-8');

        const fwIdMatch = viewContent.match(/fw:id=["']([^"']+)["']/);
        if (fwIdMatch) {
            const componentId = fwIdMatch[1];
            return this.findControllerByComponentId(componentId, viewPath);
        }

        const controllerFromRoute = this.findControllerFromRoute(viewPath);
        if (controllerFromRoute) {
            return controllerFromRoute;
        }

        const controllerFromNaming = this.findControllerFromNaming(viewPath);
        if (controllerFromNaming) {
            return controllerFromNaming;
        }

        return null;
    }

    private findControllerByComponentId(componentId: string, viewPath: string): ControllerMetadata | null {
        const viewDir = path.dirname(viewPath);
        const relativePath = path.relative(
            path.join(this.workspaceRoot, 'app', 'resources', 'views'),
            viewDir
        );

        const possibleControllers = [
            path.join(this.workspaceRoot, 'app', 'Controllers', `${this.toPascalCase(componentId)}Controller.php`),
            path.join(this.workspaceRoot, 'app', 'Controllers', `${this.toPascalCase(path.basename(viewDir))}Controller.php`),
        ];

        for (const controllerPath of possibleControllers) {
            if (fs.existsSync(controllerPath)) {
                const metadata = this.resolver.resolveController(controllerPath);
                if (metadata && metadata.isReactive) {
                    return metadata;
                }
            }
        }

        return null;
    }

    private findControllerFromRoute(viewPath: string): ControllerMetadata | null {
        const controllersDir = path.join(this.workspaceRoot, 'app', 'Controllers');
        if (!fs.existsSync(controllersDir)) {
            return null;
        }

        const viewRelativePath = path.relative(
            path.join(this.workspaceRoot, 'app', 'resources', 'views'),
            viewPath
        ).replace(/\.php$/, '');

        const controllers = this.resolver.getAllControllers();

        for (const controller of controllers) {
            const controllerContent = fs.readFileSync(controller.filePath, 'utf-8');
            const viewPathPattern = new RegExp(
                `view\\([^)]*["']${this.escapeRegex(viewRelativePath)}["']`,
                'i'
            );

            if (viewPathPattern.test(controllerContent)) {
                return controller;
            }
        }

        return null;
    }

    private findControllerFromNaming(viewPath: string): ControllerMetadata | null {
        const viewName = path.basename(viewPath, '.php');
        const viewDir = path.dirname(viewPath);
        const relativePath = path.relative(
            path.join(this.workspaceRoot, 'app', 'resources', 'views'),
            viewDir
        );

        const pathParts = relativePath.split(path.sep).filter(p => p);
        const controllerName = pathParts.length > 0
            ? this.toPascalCase(pathParts[pathParts.length - 1])
            : this.toPascalCase(viewName);

        const controllerPath = path.join(
            this.workspaceRoot,
            'app',
            'Controllers',
            `${controllerName}Controller.php`
        );

        if (fs.existsSync(controllerPath)) {
            const metadata = this.resolver.resolveController(controllerPath);
            if (metadata && metadata.isReactive) {
                return metadata;
            }
        }

        return null;
    }

    private toPascalCase(str: string): string {
        return str
            .split(/[-_\s]/)
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join('');
    }

    private escapeRegex(str: string): string {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    clearCache(): void {
        this.cache.clear();
    }
}
