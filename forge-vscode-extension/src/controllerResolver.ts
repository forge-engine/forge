import * as vscode from 'vscode';
import * as fs from 'fs';
import * as path from 'path';

export interface ControllerAction {
    name: string;
    line: number;
    isSubmit: boolean;
    parameters: string[];
}

export interface ControllerState {
    name: string;
    line: number;
    type: string;
    isShared: boolean;
}

export interface ControllerMetadata {
    className: string;
    filePath: string;
    actions: ControllerAction[];
    states: ControllerState[];
    isReactive: boolean;
}

export class ControllerResolver {
    private cache: Map<string, ControllerMetadata> = new Map();
    private fileWatchers: Map<string, vscode.FileSystemWatcher> = new Map();
    private workspaceRoot: string;

    constructor(workspaceRoot: string) {
        this.workspaceRoot = workspaceRoot;
    }

    resolveController(controllerPath: string): ControllerMetadata | null {
        const normalizedPath = path.isAbsolute(controllerPath)
            ? controllerPath
            : path.join(this.workspaceRoot, controllerPath);

        if (this.cache.has(normalizedPath)) {
            return this.cache.get(normalizedPath)!;
        }

        if (!fs.existsSync(normalizedPath)) {
            return null;
        }

        const metadata = this.parseController(normalizedPath);
        if (metadata) {
            this.cache.set(normalizedPath, metadata);
            this.setupFileWatcher(normalizedPath);
        }

        return metadata;
    }

    private parseController(filePath: string): ControllerMetadata | null {
        const content = fs.readFileSync(filePath, 'utf-8');
        const lines = content.split('\n');

        const className = this.extractClassName(content);
        if (!className) {
            return null;
        }

        const isReactive = this.hasReactiveAttribute(content);
        const actions = this.extractActions(content, lines);
        const states = this.extractStates(content, lines);

        return {
            className,
            filePath,
            actions,
            states,
            isReactive,
        };
    }

    private extractClassName(content: string): string | null {
        const classMatch = content.match(/class\s+(\w+)/);
        if (!classMatch) {
            return null;
        }

        const namespaceMatch = content.match(/namespace\s+([\w\\]+)/);
        const namespace = namespaceMatch ? namespaceMatch[1] : '';
        const className = classMatch[1];

        return namespace ? `${namespace}\\${className}` : className;
    }

    private hasReactiveAttribute(content: string): boolean {
        return /#\[Reactive\]/.test(content) || /#\[\\?App\\?\\?Modules\\?\\?ForgeWire\\?\\?Attributes\\?\\?Reactive\]/.test(content);
    }

    private extractActions(content: string, lines: string[]): ControllerAction[] {
        const actions: ControllerAction[] = [];
        const actionRegex = /#\[Action(\([^)]*\))?\]\s*(public\s+)?function\s+(\w+)\s*\([^)]*\)/g;
        let match: RegExpExecArray | null;

        while ((match = actionRegex.exec(content)) !== null) {
            const fullMatch = match[0];
            const methodName = match[3];
            const isSubmit = /submit\s*:\s*true/.test(match[1] || '');

            const lineIndex = content.substring(0, match.index).split('\n').length - 1;
            const parameters = this.extractMethodParameters(fullMatch);

            actions.push({
                name: methodName,
                line: lineIndex,
                isSubmit,
                parameters,
            });
        }

        return actions;
    }

    private extractMethodParameters(methodSignature: string): string[] {
        const paramMatch = methodSignature.match(/\(([^)]*)\)/);
        if (!paramMatch || !paramMatch[1]) {
            return [];
        }

        const params = paramMatch[1].split(',').map(p => p.trim()).filter(p => p);
        return params.map(p => {
            const nameMatch = p.match(/(\w+)\s*[:=]/);
            return nameMatch ? nameMatch[1] : p.split(/\s+/).pop() || '';
        });
    }

    private extractStates(content: string, lines: string[]): ControllerState[] {
        const states: ControllerState[] = [];
        const stateRegex = /#\[State(\([^)]*\))?\]\s*(public\s+)?(?:readonly\s+)?(\w+)\s+(\$\w+)/g;
        let match: RegExpExecArray | null;

        while ((match = stateRegex.exec(content)) !== null) {
            const fullMatch = match[0];
            const propertyName = match[4].replace('$', '');
            const propertyType = match[3];
            const isShared = /shared\s*:\s*true/.test(match[1] || '');

            const lineIndex = content.substring(0, match.index).split('\n').length - 1;

            states.push({
                name: propertyName,
                line: lineIndex,
                type: propertyType,
                isShared,
            });
        }

        return states;
    }

    private setupFileWatcher(filePath: string): void {
        if (this.fileWatchers.has(filePath)) {
            return;
        }

        const watcher = vscode.workspace.createFileSystemWatcher(
            new vscode.RelativePattern(path.dirname(filePath), path.basename(filePath))
        );

        watcher.onDidChange(() => {
            this.cache.delete(filePath);
        });

        watcher.onDidDelete(() => {
            this.cache.delete(filePath);
            this.fileWatchers.delete(filePath);
            watcher.dispose();
        });

        this.fileWatchers.set(filePath, watcher);
    }

    findControllerByView(viewPath: string): ControllerMetadata | null {
        const viewName = path.basename(viewPath, '.php');
        const viewDir = path.dirname(viewPath);

        const possibleControllers = [
            path.join(this.workspaceRoot, 'app', 'Controllers', `${this.toPascalCase(viewName)}Controller.php`),
            path.join(viewDir.replace('/resources/views', '/Controllers'), `${this.toPascalCase(viewName)}Controller.php`),
        ];

        for (const controllerPath of possibleControllers) {
            if (fs.existsSync(controllerPath)) {
                return this.resolveController(controllerPath);
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

    getAllControllers(): ControllerMetadata[] {
        const controllers: ControllerMetadata[] = [];
        const controllersDir = path.join(this.workspaceRoot, 'app', 'Controllers');

        if (!fs.existsSync(controllersDir)) {
            return controllers;
        }

        const files = this.findControllerFiles(controllersDir);
        for (const file of files) {
            const metadata = this.resolveController(file);
            if (metadata && metadata.isReactive) {
                controllers.push(metadata);
            }
        }

        return controllers;
    }

    private findControllerFiles(dir: string): string[] {
        const files: string[] = [];

        if (!fs.existsSync(dir)) {
            return files;
        }

        const entries = fs.readdirSync(dir, { withFileTypes: true });
        for (const entry of entries) {
            const fullPath = path.join(dir, entry.name);
            if (entry.isDirectory()) {
                files.push(...this.findControllerFiles(fullPath));
            } else if (entry.name.endsWith('Controller.php')) {
                files.push(fullPath);
            }
        }

        return files;
    }

    dispose(): void {
        for (const watcher of this.fileWatchers.values()) {
            watcher.dispose();
        }
        this.fileWatchers.clear();
        this.cache.clear();
    }
}
