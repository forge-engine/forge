Project Summary: Forge Reactive UI (ForgeWire)

Goal

Build a minimal, secure, server-driven reactive UI system for the Forge PHP framework that enhances UX (no full page reloads) without introducing a client-side framework, runtime JS evaluation, or security exceptions.

This system is not a SPA framework and not a Livewire clone.

⸻

Core Principles (Must Not Be Violated)
	1.	Server is authoritative
	•	All state lives on the server.
	•	Client never executes business logic.
	•	Client only sends user intent + receives HTML.
	2.	HTML fragment swapping, not DOM diffing
	•	Server returns HTML fragments.
	•	Client replaces DOM nodes directly.
	•	No virtual DOM.
	•	No hydration.
	•	No lifecycle hooks.
	3.	Minimal JavaScript
	•	Small static runtime.
	•	No eval
	•	No dynamic script injection
	•	No expression parsing
	•	No inline logic execution
	4.	Security-first
	•	Uses existing Forge CSRF middleware.
	•	Uses same-origin checks.
	•	Works under CSP:
	•	default-src 'self'
	•	No need for unsafe-eval
	•	No bypass of middleware, authorization, or validation.
	5.	Opt-in only
	•	Reactivity is explicitly enabled.
	•	Existing controllers remain unchanged unless marked.	
	Activation Model
	•	Controllers opt-in via attribute:
	#[Reactive]
	•	Reactive controllers use a helper trait:
use ReactiveControllerHelper;
	•	When enabled:
	•	The controller’s view and its child components may participate in reactivity.
	•	Normal HTTP behavior still applies if JS is disabled.
Transport Layer
	•	Single internal endpoint (example):
	POST /__wire
	•	Accepts:
	•	JSON only
	•	Same-origin only
	•	CSRF-protected
	•	Middleware enforces:
	•	HTTP method
	•	Content-Type
	•	CSRF token
	•	Origin / Referer validation

⸻

Client-Side Directives (Very Limited)

Only directives solving ~99% of common UI needs:
	•	fw:submit
	•	Intercepts form submit
	•	Sends form data via fetch
	•	Prevents full page reload
	•	fw:model
	•	Binds input value to server property
	•	Supports modifiers:
	•	.debounce
	•	.lazy

No other directives unless strictly necessary.

No:
	•	fw:click
	•	Arbitrary JS expressions
	•	Inline logic

⸻

Interaction Flow
	1.	User interacts with form/input.
	2.	Client sends:
	•	Controller ID
	•	Action name (implicit or predefined)
	•	Payload (form data)
	3.	Server:
	•	Runs middleware
	•	Validates input
	•	Executes controller logic
	•	Renders partial HTML
	4.	Client:
	•	Replaces target DOM node(s)
	•	Does not execute scripts

⸻

Rendering Rules
	•	Server returns:
	•	HTML fragments only
	•	No JS
	•	No script tags
	•	HTML is already escaped/sanitized via Forge view system.
	•	Browser enforces CSP normally.

⸻

Explicit Non-Goals (Must NOT Be Added)
	•	No SPA routing
	•	No client-side state store
	•	No WebSockets
	•	No polling
	•	No event bus
	•	No JavaScript expressions in templates
	•	No magic method discovery
	•	No automatic model mutation

⸻

Mental Model

Think of this system as:

“Classic server-rendered HTML with fetch-based form submits and partial DOM replacement.”

It is closer to HTMX-style interaction, but:
	•	integrated at the controller level
	•	attribute-driven
	•	deeply aligned with Forge middleware and security model

⸻

Expected Outcome
	•	Add reactivity to existing controllers with minimal changes.
	•	Improve UX (live search, todo list updates, inline forms).
	•	Preserve Forge’s philosophy:
	•	transparent
	•	boring
	•	predictable
	•	secure by default

USage example of what we want to achieve
#[Reactive]
final class SearchController
{
    use ControllerHelper;
    use ReactiveControllerHelper;

    #[Route("/search")]
    public function index(string $query = ''): Response
    {
        return $this->view("pages/search/index", [
            "results" => $this->search($query),
        ]);
    }

    private function search(string $query): array
    {
        if ($query === '') {
            return [];
        }

        return SearchService::find($query);
    }
}
View
<input
    type="search"
    fw:model.debounce.300ms="query"
    placeholder="Search..."
>

<div fw:target>
    @foreach ($results as $item)
        <div>{{ $item->title }}</div>
    @endforeach
</div>

A tiny directive surface:
	•	fw:submit
	•	fw:model
	•	fw:click (optional)
	•	maybe fw:target
	•	modifiers like .debounce, .lazy
	Your fw:model does not need to be:
	•	keystroke-synced
	•	always reactive
	•	globally stateful

Instead:
	•	it’s just a request input mapper
	•	optionally delayed
	•	optionally lazy

So:
<input
    type="search"
    fw:model.debounce.300ms="query"
/>
Means:
	•	update query
	•	debounce the request
	•	re-enter controller
	•	re-render target

No frontend state machine.
No PHP pretending to be JS.