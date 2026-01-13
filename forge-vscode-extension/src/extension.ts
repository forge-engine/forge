import * as vscode from 'vscode';
import { ForgeDefinitionProvider } from './definitionProvider';
import { PathResolver } from './pathResolver';
import { ControllerResolver } from './controllerResolver';
import { ViewControllerMapper } from './viewControllerMapper';
import { ForgeWireCompletionProvider } from './completionProvider';
import { ForgeWireHtmlValidator } from './htmlValidator';
import { ForgeWireHoverProvider } from './hoverProvider';
import * as path from 'path';

export function activate(context: vscode.ExtensionContext) {
    const workspaceFolders = vscode.workspace.workspaceFolders;

    if (!workspaceFolders || workspaceFolders.length === 0) {
        vscode.window.showWarningMessage('Forge Framework extension requires an open workspace.');
        return;
    }

    const workspaceRoot = workspaceFolders[0].uri.fsPath;

    if (!PathResolver.isForgeProject(workspaceRoot)) {
        return;
    }

    const controllerResolver = new ControllerResolver(workspaceRoot);
    const viewControllerMapper = new ViewControllerMapper(workspaceRoot, controllerResolver);
    const definitionProvider = new ForgeDefinitionProvider(workspaceRoot, viewControllerMapper);

    const definitionProviderDisposable = vscode.languages.registerDefinitionProvider(
        { scheme: 'file', language: 'php' },
        definitionProvider
    );

    const htmlValidator = new ForgeWireHtmlValidator();
    const completionProvider = new ForgeWireCompletionProvider(viewControllerMapper);
    const hoverProvider = new ForgeWireHoverProvider(viewControllerMapper);

    const completionProviderDisposable = vscode.languages.registerCompletionItemProvider(
        { scheme: 'file', language: 'php' },
        completionProvider,
        '"', "'"
    );

    const hoverProviderDisposable = vscode.languages.registerHoverProvider(
        { scheme: 'file', language: 'php' },
        hoverProvider
    );

    const validateDocument = (document: vscode.TextDocument) => {
        if (document.languageId === 'php' || document.languageId === 'html') {
            htmlValidator.validateDocument(document);
        }
    };

    const onDidChangeTextDocument = vscode.workspace.onDidChangeTextDocument((e) => {
        validateDocument(e.document);
    });

    const onDidOpenTextDocument = vscode.workspace.onDidOpenTextDocument((document) => {
        validateDocument(document);
    });

    for (const document of vscode.workspace.textDocuments) {
        validateDocument(document);
    }

    context.subscriptions.push(
        definitionProviderDisposable,
        completionProviderDisposable,
        hoverProviderDisposable,
        htmlValidator,
        controllerResolver,
        onDidChangeTextDocument,
        onDidOpenTextDocument
    );

    console.log('Forge Framework extension activated');
}

export function deactivate() {
}
