import * as vscode from 'vscode';
import { PathResolver, ResolvedPath } from './pathResolver';
import { ViewControllerMapper } from './viewControllerMapper';
import * as path from 'path';

export class ForgeDefinitionProvider implements vscode.DefinitionProvider {
  private pathResolver: PathResolver;
  private viewControllerMapper: ViewControllerMapper | null = null;

  constructor(workspaceRoot: string, viewControllerMapper?: ViewControllerMapper) {
    this.pathResolver = new PathResolver(workspaceRoot);
    this.viewControllerMapper = viewControllerMapper || null;
  }

  provideDefinition(
    document: vscode.TextDocument,
    position: vscode.Position,
    token: vscode.CancellationToken
  ): vscode.ProviderResult<vscode.Definition | vscode.LocationLink[]> {
    const line = document.lineAt(position);
    const lineText = line.text;
    const offset = document.offsetAt(position);

    // Try to match view calls in controllers
    const viewMatch = this.matchViewCall(lineText, position);
    if (viewMatch) {
      const resolved = this.pathResolver.resolveView(viewMatch.path);
      if (resolved) {
        return this.createLocation(resolved, document.uri);
      }
    }

    const slotComponentMatch = this.matchSlotComponentName(lineText, position);
    if (slotComponentMatch) {
      const resolved = this.pathResolver.resolveComponent(
        slotComponentMatch.name,
        document.fileName
      );
      if (resolved) {
        return this.createLocation(resolved, document.uri);
      }
    }

    // Try to match component calls
    const componentMatch = this.matchComponentCall(lineText, position, document);
    if (componentMatch) {
      const resolved = this.pathResolver.resolveComponent(
        componentMatch.name,
        document.fileName
      );
      if (resolved) {
        return this.createLocation(resolved, document.uri);
      }
    }

    // Try to match layout calls
    const layoutMatch = this.matchLayoutCall(lineText, position, document);
    if (layoutMatch) {
      const resolved = this.pathResolver.resolveLayout(
        layoutMatch.name,
        layoutMatch.fromModule,
        layoutMatch.moduleName,
        document.fileName
      );
      if (resolved) {
        return this.createLocation(resolved, document.uri);
      }
    }

    if (this.viewControllerMapper) {
      const forgewireMatch = this.matchForgeWireDirective(lineText, position, document);
      if (forgewireMatch) {
        return this.resolveForgeWireDefinition(forgewireMatch, document);
      }
    }

    return null;
  }

  /**
   * Match view calls: $this->view(view: "path/to/view", ...) or $this->view("path/to/view", ...)
   */
  private matchViewCall(lineText: string, position: vscode.Position): { path: string } | null {
    // Match named parameter: $this->view(view: "path/to/view", ...)
    const namedParamRegex = /\$this->view\s*\(\s*view\s*:\s*["']([^"']+)["']/;
    let match = lineText.match(namedParamRegex);

    if (match) {
      const matchStart = lineText.indexOf(match[0]);
      const matchEnd = matchStart + match[0].length;
      const cursorPos = position.character;

      // Check if cursor is within the view path string
      const pathStart = match[0].indexOf(match[1]);
      const pathEnd = pathStart + match[1].length;

      if (cursorPos >= matchStart + pathStart && cursorPos <= matchStart + pathEnd) {
        return { path: match[1] };
      }
    }

    // Match positional parameter: $this->view("path/to/view", ...)
    const positionalRegex = /\$this->view\s*\(\s*["']([^"']+)["']/;
    match = lineText.match(positionalRegex);

    if (match) {
      const matchStart = lineText.indexOf(match[0]);
      const matchEnd = matchStart + match[0].length;
      const cursorPos = position.character;

      // Check if cursor is within the view path string
      const pathStart = match[0].indexOf(match[1]);
      const pathEnd = pathStart + match[1].length;

      if (cursorPos >= matchStart + pathStart && cursorPos <= matchStart + pathEnd) {
        return { path: match[1] };
      }
    }

    return null;
  }

  /**
   * Match component names in slots arrays: 'name' => 'ui/alert' or "name" => "ModuleName:component"
   */
  private matchSlotComponentName(lineText: string, position: vscode.Position): { name: string } | null {
    // Match pattern: 'name' => 'component/path' or "name" => "component/path"
    // This pattern appears in slots arrays passed to components
    const slotNameRegex = /['"]name['"]\s*=>\s*["']([^"']+)["']/;
    const match = lineText.match(slotNameRegex);

    if (match) {
      const matchStart = lineText.indexOf(match[0]);
      const cursorPos = position.character;

      // Check if cursor is within the component name string
      const nameStart = match[0].indexOf(match[1]);
      const nameEnd = nameStart + match[1].length;

      if (cursorPos >= matchStart + nameStart && cursorPos <= matchStart + nameEnd) {
        // Only return if it looks like a component path (contains / or :)
        // This helps avoid false positives with other 'name' => 'value' patterns
        const componentName = match[1];
        if (componentName.includes('/') || componentName.includes(':')) {
          return { name: componentName };
        }
      }
    }

    return null;
  }

  /**
   * Match component calls: component('ModuleName:component') or component(name: 'path/to/component', ...)
   */
  private matchComponentCall(lineText: string, position: vscode.Position, document?: vscode.TextDocument): { name: string } | null {
    const cursorPos = position.character;
    const lineNumber = position.line;

    // For multi-line matching, get context around the cursor
    let contextLines: string[] = [lineText];
    let currentLineIndex = 0;
    if (document) {
      const startLine = Math.max(0, lineNumber - 5);
      const endLine = Math.min(document.lineCount - 1, lineNumber + 5);
      contextLines = [];
      for (let i = startLine; i <= endLine; i++) {
        contextLines.push(document.lineAt(i).text);
        if (i === lineNumber) {
          currentLineIndex = contextLines.length - 1;
        }
      }
    }

    // Check each line in context for component matches
    for (let i = 0; i < contextLines.length; i++) {
      const line = contextLines[i];
      const isCurrentLine = i === currentLineIndex;

      // Match module component: component('ForgeUi:notifications')
      const moduleComponentRegex = /component\s*\(\s*["']([^"']+:[^"']+)["']/g;
      let match: RegExpExecArray | null;

      while ((match = moduleComponentRegex.exec(line)) !== null) {
        const matchStart = match.index;
        const nameStart = match[0].indexOf(match[1]);
        const nameEnd = nameStart + match[1].length;

        if (isCurrentLine) {
          if (cursorPos >= matchStart + nameStart && cursorPos <= matchStart + nameEnd) {
            return { name: match[1] };
          }
        } else if (matchStart + nameStart <= line.length) {
          // If cursor is near this line, allow clicking
          return { name: match[1] };
        }
      }

      // Match app component with named parameter: component(name: 'ui/footer', ...)
      const namedComponentRegex = /component\s*\(\s*name\s*:\s*["']([^"']+)["']/g;

      while ((match = namedComponentRegex.exec(line)) !== null) {
        const matchStart = match.index;
        const nameStart = match[0].indexOf(match[1]);
        const nameEnd = nameStart + match[1].length;

        if (isCurrentLine) {
          if (cursorPos >= matchStart + nameStart && cursorPos <= matchStart + nameEnd) {
            return { name: match[1] };
          }
        } else if (matchStart + nameStart <= line.length) {
          return { name: match[1] };
        }
      }

      // Match app component with positional parameter: component('ui/footer', ...)
      const positionalComponentRegex = /component\s*\(\s*["']([^"']+)["']/g;

      while ((match = positionalComponentRegex.exec(line)) !== null) {
        if (!match[1].includes(':')) {
          const matchStart = match.index;
          const nameStart = match[0].indexOf(match[1]);
          const nameEnd = nameStart + match[1].length;

          if (isCurrentLine) {
            if (cursorPos >= matchStart + nameStart && cursorPos <= matchStart + nameEnd) {
              return { name: match[1] };
            }
          } else if (matchStart + nameStart <= line.length) {
            return { name: match[1] };
          }
        }
      }
    }

    return null;
  }

  /**
   * Match layout calls: layout('name'), layout(name: "name"), layout(name: "name", fromModule: true), etc.
   */
  private matchLayoutCall(
    lineText: string,
    position: vscode.Position,
    document: vscode.TextDocument
  ): { name: string; fromModule: boolean; moduleName?: string } | null {
    // Match refactored syntax: layout(name: "ForgeNexus:nexus")
    const refactoredRegex = /layout\s*\(\s*name\s*:\s*["']([^"']+:[^"']+)["']/;
    let match = lineText.match(refactoredRegex);

    if (match) {
      const matchStart = lineText.indexOf(match[0]);
      const cursorPos = position.character;

      const nameStart = match[0].indexOf(match[1]);
      const nameEnd = nameStart + match[1].length;

      if (cursorPos >= matchStart + nameStart && cursorPos <= matchStart + nameEnd) {
        return { name: match[1], fromModule: true };
      }
    }

    // Match current syntax with all parameters: layout(name: "nexus", fromModule: true, moduleName: "ForgeNexus")
    const fullSyntaxRegex = /layout\s*\(\s*name\s*:\s*["']([^"']+)["']\s*,\s*fromModule\s*:\s*(true|false)\s*(?:,\s*moduleName\s*:\s*["']([^"']+)["'])?/;
    match = lineText.match(fullSyntaxRegex);

    if (match) {
      const matchStart = lineText.indexOf(match[0]);
      const cursorPos = position.character;

      const nameStart = match[0].indexOf(match[1]);
      const nameEnd = nameStart + match[1].length;

      if (cursorPos >= matchStart + nameStart && cursorPos <= matchStart + nameEnd) {
        return {
          name: match[1],
          fromModule: match[2] === 'true',
          moduleName: match[3]
        };
      }
    }

    // Match with fromModule: layout(name: "nexus", fromModule: true)
    const fromModuleRegex = /layout\s*\(\s*name\s*:\s*["']([^"']+)["']\s*,\s*fromModule\s*:\s*(true|false)/;
    match = lineText.match(fromModuleRegex);

    if (match) {
      const matchStart = lineText.indexOf(match[0]);
      const cursorPos = position.character;

      const nameStart = match[0].indexOf(match[1]);
      const nameEnd = nameStart + match[1].length;

      if (cursorPos >= matchStart + nameStart && cursorPos <= matchStart + nameEnd) {
        return {
          name: match[1],
          fromModule: match[2] === 'true'
        };
      }
    }

    // Match named parameter: layout(name: "main")
    const namedLayoutRegex = /layout\s*\(\s*name\s*:\s*["']([^"']+)["']/;
    match = lineText.match(namedLayoutRegex);

    if (match) {
      const matchStart = lineText.indexOf(match[0]);
      const cursorPos = position.character;

      const nameStart = match[0].indexOf(match[1]);
      const nameEnd = nameStart + match[1].length;

      if (cursorPos >= matchStart + nameStart && cursorPos <= matchStart + nameEnd) {
        return { name: match[1], fromModule: false };
      }
    }

    // Match positional parameter: layout('main')
    const positionalLayoutRegex = /layout\s*\(\s*["']([^"']+)["']/;
    match = lineText.match(positionalLayoutRegex);

    if (match) {
      const matchStart = lineText.indexOf(match[0]);
      const cursorPos = position.character;

      const nameStart = match[0].indexOf(match[1]);
      const nameEnd = nameStart + match[1].length;

      if (cursorPos >= matchStart + nameStart && cursorPos <= matchStart + nameEnd) {
        return { name: match[1], fromModule: false };
      }
    }

    return null;
  }

  private matchForgeWireDirective(
    lineText: string,
    position: vscode.Position,
    document: vscode.TextDocument
  ): { type: 'action' | 'state'; name: string } | null {
    const cursorPos = position.character;

    const actionDirectives = [
      /fw:click\s*=\s*["']([^"']+)["']/,
      /fw:submit\s*=\s*["']([^"']+)["']/,
      /fw:action\s*=\s*["']([^"']+)["']/,
      /fw:keydown\.\w+\s*=\s*["']([^"']+)["']/,
    ];

    for (const regex of actionDirectives) {
      const match = lineText.match(regex);
      if (match) {
        const matchStart = lineText.indexOf(match[0]);
        const nameStart = match[0].indexOf(match[1]);
        const nameEnd = nameStart + match[1].length;

        if (cursorPos >= matchStart + nameStart && cursorPos <= matchStart + nameEnd) {
          return { type: 'action', name: match[1] };
        }
      }
    }

    const modelRegex = /fw:model(\.(lazy|defer|debounce(\.\d+(ms|s))?))?\s*=\s*["']([^"']+)["']/;
    const modelMatch = lineText.match(modelRegex);
    if (modelMatch && modelMatch[5]) {
      const matchStart = lineText.indexOf(modelMatch[0]);
      const nameStart = modelMatch[0].indexOf(modelMatch[5]);
      const nameEnd = nameStart + modelMatch[5].length;

      if (cursorPos >= matchStart + nameStart && cursorPos <= matchStart + nameEnd) {
        return { type: 'state', name: modelMatch[5] };
      }
    }

    const validationRegex = /fw:validation-error(\.all)?\s*=\s*["']([^"']+)["']/;
    const validationMatch = lineText.match(validationRegex);
    if (validationMatch && validationMatch[2]) {
      const matchStart = lineText.indexOf(validationMatch[0]);
      const nameStart = validationMatch[0].indexOf(validationMatch[2]);
      const nameEnd = nameStart + validationMatch[2].length;

      if (cursorPos >= matchStart + nameStart && cursorPos <= matchStart + nameEnd) {
        return { type: 'state', name: validationMatch[2] };
      }
    }

    return null;
  }

  private resolveForgeWireDefinition(
    match: { type: 'action' | 'state'; name: string },
    document: vscode.TextDocument
  ): vscode.Location | null {
    if (!this.viewControllerMapper) {
      return null;
    }

    const controller = this.viewControllerMapper.getControllerForView(document.fileName);
    if (!controller) {
      return null;
    }

    if (match.type === 'action') {
      const action = controller.actions.find(a => a.name === match.name);
      if (action) {
        const targetUri = vscode.Uri.file(controller.filePath);
        return new vscode.Location(targetUri, new vscode.Position(action.line, 0));
      }
    } else if (match.type === 'state') {
      const state = controller.states.find(s => s.name === match.name);
      if (state) {
        const targetUri = vscode.Uri.file(controller.filePath);
        return new vscode.Location(targetUri, new vscode.Position(state.line, 0));
      }
    }

    return null;
  }

  /**
   * Create a location from a resolved path
   */
  private createLocation(
    resolved: ResolvedPath,
    sourceUri: vscode.Uri
  ): vscode.Location | null {
    if (!resolved.exists) {
      // Still return the location even if file doesn't exist yet
      // VS Code will show it as a broken link
    }

    const targetUri = vscode.Uri.file(resolved.filePath);

    // Return location pointing to the start of the file
    // VS Code will handle opening the file
    return new vscode.Location(targetUri, new vscode.Position(0, 0));
  }
}
