import * as vscode from 'vscode';
import { ViewControllerMapper } from './viewControllerMapper';
import { ControllerMetadata } from './controllerResolver';

export class ForgeWireHoverProvider implements vscode.HoverProvider {
  private viewControllerMapper: ViewControllerMapper;

  constructor(viewControllerMapper: ViewControllerMapper) {
    this.viewControllerMapper = viewControllerMapper;
  }

  provideHover(
    document: vscode.TextDocument,
    position: vscode.Position,
    token: vscode.CancellationToken
  ): vscode.ProviderResult<vscode.Hover> {
    const line = document.lineAt(position);
    const lineText = line.text;
    const cursorPos = position.character;

    const directiveInfo = this.getDirectiveInfo(lineText, cursorPos);
    if (directiveInfo) {
      return this.createHover(directiveInfo, document);
    }

    const actionOrStateInfo = this.getActionOrStateInfo(lineText, cursorPos, document);
    if (actionOrStateInfo) {
      return this.createActionOrStateHover(actionOrStateInfo, document);
    }

    return null;
  }

  private getDirectiveInfo(lineText: string, cursorPos: number): { directive: string; description: string } | null {
    const directivePatterns = [
      { pattern: /fw:click\s*=/g, name: 'fw:click', desc: 'ForgeWire: Calls an action method when the element is clicked' },
      { pattern: /fw:submit\s*=/g, name: 'fw:submit', desc: 'ForgeWire: Calls an action method when the form is submitted' },
      { pattern: /fw:model\s*=/g, name: 'fw:model', desc: 'ForgeWire: Two-way data binding (immediate updates)' },
      { pattern: /fw:model\.lazy\s*=/g, name: 'fw:model.lazy', desc: 'ForgeWire: Two-way data binding (updates on blur/change)' },
      { pattern: /fw:model\.defer\s*=/g, name: 'fw:model.defer', desc: 'ForgeWire: Two-way data binding (updates only when action is triggered)' },
      { pattern: /fw:model\.debounce\s*=/g, name: 'fw:model.debounce', desc: 'ForgeWire: Two-way data binding (debounced updates, 600ms default)' },
      { pattern: /fw:model\.debounce\.\d+(ms|s)\s*=/g, name: 'fw:model.debounce', desc: 'ForgeWire: Two-way data binding (custom debounce time)' },
      { pattern: /fw:poll\s*/g, name: 'fw:poll', desc: 'ForgeWire: Auto-refreshes the component every 2 seconds' },
      { pattern: /fw:poll\.\d+(ms|s)\s*/g, name: 'fw:poll', desc: 'ForgeWire: Auto-refreshes the component at custom interval' },
      { pattern: /fw:action\s*=/g, name: 'fw:action', desc: 'ForgeWire: Specifies which action to call on poll' },
      { pattern: /fw:id\s*=/g, name: 'fw:id', desc: 'ForgeWire: Component identifier for reactive updates' },
      { pattern: /fw:target\s*/g, name: 'fw:target', desc: 'ForgeWire: Marks element to be updated on component refresh' },
      { pattern: /fw:loading\s*/g, name: 'fw:loading', desc: 'ForgeWire: Shows when component is processing a request' },
      { pattern: /fw:keydown\.\w+\s*=/g, name: 'fw:keydown', desc: 'ForgeWire: Calls an action when specific key is pressed' },
      { pattern: /fw:validation-error\s*=/g, name: 'fw:validation-error', desc: 'ForgeWire: Displays validation error for a state property' },
      { pattern: /fw:validation-error\.all\s*/g, name: 'fw:validation-error.all', desc: 'ForgeWire: Displays all validation errors' },
      { pattern: /fw:param-\w+\s*=/g, name: 'fw:param-*', desc: 'ForgeWire: Passes parameter to action method' },
    ];

    for (const { pattern, name, desc } of directivePatterns) {
      let match: RegExpExecArray | null;
      pattern.lastIndex = 0;

      while ((match = pattern.exec(lineText)) !== null) {
        const matchStart = match.index;
        const matchEnd = matchStart + match[0].length;

        if (cursorPos >= matchStart && cursorPos <= matchEnd) {
          return { directive: name, description: desc };
        }
      }
    }

    return null;
  }

  private getActionOrStateInfo(
    lineText: string,
    cursorPos: number,
    document: vscode.TextDocument
  ): { type: 'action' | 'state'; name: string } | null {
    const actionDirectives = [
      /fw:click\s*=\s*["']([^"']+)["']/,
      /fw:submit\s*=\s*["']([^"']+)["']/,
      /fw:action\s*=\s*["']([^"']+)["']/,
      /fw:keydown\.\w+\s*=\s*["']([^"']+)["']/,
    ];

    for (const regex of actionDirectives) {
      const match = lineText.match(regex);
      if (match && match[1]) {
        const matchStart = lineText.indexOf(match[0]);
        const nameStart = match[0].indexOf(match[1]);
        const nameEnd = nameStart + match[1].length;

        if (cursorPos >= matchStart + nameStart && cursorPos <= matchStart + nameEnd) {
          return { type: 'action', name: match[1] };
        }
      }
    }

    // Match all fw:model variants: fw:model, fw:model.lazy, fw:model.defer, fw:model.debounce, fw:model.debounce.300ms
    const modelPatterns = [
      /fw:model\.debounce\.\d+(ms|s)\s*=\s*["']([^"']+)["']/,
      /fw:model\.debounce\s*=\s*["']([^"']+)["']/,
      /fw:model\.defer\s*=\s*["']([^"']+)["']/,
      /fw:model\.lazy\s*=\s*["']([^"']+)["']/,
      /fw:model\s*=\s*["']([^"']+)["']/,
    ];

    for (const regex of modelPatterns) {
      const match = lineText.match(regex);
      if (match) {
        const propertyName = match[match.length - 1];
        const matchStart = lineText.indexOf(match[0]);
        const nameStart = match[0].indexOf(propertyName);
        const nameEnd = nameStart + propertyName.length;

        if (cursorPos >= matchStart + nameStart && cursorPos <= matchStart + nameEnd) {
          return { type: 'state', name: propertyName };
        }
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

  private createHover(
    info: { directive: string; description: string },
    document: vscode.TextDocument
  ): vscode.Hover {
    const markdown = new vscode.MarkdownString();
    markdown.appendMarkdown(`**${info.directive}**\n\n`);
    markdown.appendText(info.description);

    return new vscode.Hover(markdown);
  }

  private createActionOrStateHover(
    info: { type: 'action' | 'state'; name: string },
    document: vscode.TextDocument
  ): vscode.Hover | null {
    const controller = this.viewControllerMapper.getControllerForView(document.fileName);
    if (!controller) {
      return null;
    }

    const markdown = new vscode.MarkdownString();

    if (info.type === 'action') {
      const action = controller.actions.find(a => a.name === info.name);
      if (action) {
        markdown.appendMarkdown(`**Action: ${info.name}**\n\n`);
        markdown.appendMarkdown(`Defined in: \`${controller.className}\`\n\n`);
        if (action.parameters.length > 0) {
          markdown.appendMarkdown(`Parameters: ${action.parameters.join(', ')}\n\n`);
        } else {
          markdown.appendMarkdown(`No parameters\n\n`);
        }
        if (action.isSubmit) {
          markdown.appendMarkdown(`*Submit action*\n`);
        }
      } else {
        markdown.appendMarkdown(`**Action: ${info.name}**\n\n`);
        markdown.appendText(`Action not found in controller`);
      }
    } else {
      const state = controller.states.find(s => s.name === info.name);
      if (state) {
        markdown.appendMarkdown(`**State: ${info.name}**\n\n`);
        markdown.appendMarkdown(`Type: \`${state.type}\`\n\n`);
        markdown.appendMarkdown(`Defined in: \`${controller.className}\`\n\n`);
        if (state.isShared) {
          markdown.appendMarkdown(`*Shared state*\n`);
        } else {
          markdown.appendMarkdown(`*Component state*\n`);
        }
      } else {
        markdown.appendMarkdown(`**State: ${info.name}**\n\n`);
        markdown.appendText(`State property not found in controller`);
      }
    }

    return new vscode.Hover(markdown);
  }
}
