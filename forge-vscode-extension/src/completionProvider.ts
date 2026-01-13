import * as vscode from 'vscode';
import { ViewControllerMapper } from './viewControllerMapper';
import { ControllerMetadata } from './controllerResolver';

export class ForgeWireCompletionProvider implements vscode.CompletionItemProvider {
  private viewControllerMapper: ViewControllerMapper;

  constructor(viewControllerMapper: ViewControllerMapper) {
    this.viewControllerMapper = viewControllerMapper;
  }

  provideCompletionItems(
    document: vscode.TextDocument,
    position: vscode.Position,
    token: vscode.CancellationToken,
    context: vscode.CompletionContext
  ): vscode.ProviderResult<vscode.CompletionItem[] | vscode.CompletionList> {
    const line = document.lineAt(position);
    const lineText = line.text;
    const beforeCursor = lineText.substring(0, position.character);

    const controller = this.viewControllerMapper.getControllerForView(document.fileName);
    if (!controller) {
      return [];
    }

    if (this.isInActionDirective(beforeCursor)) {
      return this.getActionCompletions(controller, beforeCursor);
    }

    if (this.isInStateDirective(beforeCursor)) {
      return this.getStateCompletions(controller);
    }

    return [];
  }

  private isInActionDirective(text: string): boolean {
    return /fw:(click|submit|action|keydown\.\w+)\s*=\s*["']?$/.test(text) ||
           /fw:(click|submit|action|keydown\.\w+)\s*=\s*["'][^"']*$/.test(text);
  }

  private isInStateDirective(text: string): boolean {
    return /fw:model(\.(lazy|defer|debounce(\.\d+(ms|s))?))?\s*=\s*["']?$/.test(text) ||
           /fw:model(\.(lazy|defer|debounce(\.\d+(ms|s))?))?\s*=\s*["'][^"']*$/.test(text) ||
           /fw:validation-error(\.all)?\s*=\s*["']?$/.test(text) ||
           /fw:validation-error(\.all)?\s*=\s*["'][^"']*$/.test(text);
  }

  private getActionCompletions(
    controller: ControllerMetadata,
    beforeCursor: string
  ): vscode.CompletionItem[] {
    const items: vscode.CompletionItem[] = [];

    for (const action of controller.actions) {
      const item = new vscode.CompletionItem(
        action.name,
        vscode.CompletionItemKind.Method
      );
      item.detail = `Action in ${controller.className}`;
      item.documentation = `Parameters: ${action.parameters.join(', ') || 'none'}`;
      item.insertText = action.name;
      items.push(item);
    }

    return items;
  }

  private getStateCompletions(controller: ControllerMetadata): vscode.CompletionItem[] {
    const items: vscode.CompletionItem[] = [];

    for (const state of controller.states) {
      const item = new vscode.CompletionItem(
        state.name,
        vscode.CompletionItemKind.Property
      );
      item.detail = `${state.type} property in ${controller.className}`;
      item.documentation = state.isShared ? 'Shared state' : 'Component state';
      item.insertText = state.name;
      items.push(item);
    }

    return items;
  }
}
