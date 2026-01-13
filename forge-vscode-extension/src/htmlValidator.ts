import * as vscode from 'vscode';

const FORGEWIRE_DIRECTIVES = [
    'fw:click',
    'fw:submit',
    'fw:model',
    'fw:model.lazy',
    'fw:model.defer',
    'fw:model.debounce',
    'fw:poll',
    'fw:action',
    'fw:id',
    'fw:target',
    'fw:loading',
    'fw:checksum',
    'fw:validation-error',
    'fw:validation-error.all',
    'fw:keydown.enter',
    'fw:keydown.escape',
    'fw:keydown.backspace',
];

const FORGEWIRE_DIRECTIVE_PATTERNS = [
    /^fw:click$/,
    /^fw:submit$/,
    /^fw:model(\.(lazy|defer|debounce(\.\d+(ms|s))?))?$/,
    /^fw:poll(\.\d+(ms|s))?$/,
    /^fw:action$/,
    /^fw:id$/,
    /^fw:target$/,
    /^fw:loading$/,
    /^fw:checksum$/,
    /^fw:validation-error(\.all)?$/,
    /^fw:keydown\.(enter|escape|backspace|tab|up|down|left|right)$/,
    /^fw:param-[a-zA-Z0-9_-]+$/,
    /^fw:event:[a-zA-Z0-9_-]+$/,
];

export class ForgeWireHtmlValidator {
    private diagnosticCollection: vscode.DiagnosticCollection;

    constructor() {
        this.diagnosticCollection = vscode.languages.createDiagnosticCollection('forgewire');
    }

    validateDocument(document: vscode.TextDocument): void {
        const diagnostics: vscode.Diagnostic[] = [];
        const text = document.getText();
        const lines = text.split('\n');

        lines.forEach((line, lineIndex) => {
            const directiveMatches = line.matchAll(/fw:[a-zA-Z0-9_.:-]+/g);

            for (const match of directiveMatches) {
                const directive = match[0];
                const matchIndex = match.index!;

                if (!this.isValidDirective(directive)) {
                    const position = new vscode.Position(lineIndex, matchIndex);
                    const range = new vscode.Range(
                        position,
                        new vscode.Position(lineIndex, matchIndex + directive.length)
                    );

                    const diagnostic = new vscode.Diagnostic(
                        range,
                        `Unknown ForgeWire directive: ${directive}`,
                        vscode.DiagnosticSeverity.Warning
                    );
                    diagnostics.push(diagnostic);
                }
            }
        });

        this.diagnosticCollection.set(document.uri, diagnostics);
    }

    private isValidDirective(directive: string): boolean {
        for (const pattern of FORGEWIRE_DIRECTIVE_PATTERNS) {
            if (pattern.test(directive)) {
                return true;
            }
        }
        return false;
    }

    clear(document: vscode.TextDocument): void {
        this.diagnosticCollection.delete(document.uri);
    }

    dispose(): void {
        this.diagnosticCollection.dispose();
    }
}
