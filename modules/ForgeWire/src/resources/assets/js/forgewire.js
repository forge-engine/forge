(() => {
	if (typeof window !== 'undefined') {
		if (window.__forgeWireInitialized) {
			return;
		}
		window.__forgeWireInitialized = true;
	}
	// ============================================================================
	// CONSTANTS & CONFIGURATION
	// ============================================================================
	const DEFAULT_DEBOUNCE = 600;
	const rootSel = '[fw\\:id]';
	const attr = (el, n) => el.getAttribute(n);
	const closestRoot = el => el.closest(rootSel);
	const optimisticHandlers = {};

	if (typeof window !== 'undefined') {
		window.ForgeWire = window.ForgeWire || {};
		window.ForgeWire.optimistic = function (actionName, handler) {
			optimisticHandlers[actionName] = handler;
		};
	}

	// ============================================================================
	// ATTRIBUTE CACHING SYSTEM
	// ============================================================================
	const attributeCache = new WeakMap();

	function getCachedAttributes(el) {
		if (!attributeCache.has(el)) {
			attributeCache.set(el, Array.from(el.getAttributeNames()));
		}
		return attributeCache.get(el);
	}

	function invalidateElementCache(el) {
		attributeCache.delete(el);
	}

	// ============================================================================
	// CLEANUP REGISTRY (Memory Leak Prevention)
	// ============================================================================
	const cleanupRegistry = new Map();

	function registerCleanup(id, cleanupFn) {
		if (!cleanupRegistry.has(id)) {
			cleanupRegistry.set(id, new Set());
		}
		cleanupRegistry.get(id).add(cleanupFn);
	}

	function cleanupComponent(id) {
		const cleanups = cleanupRegistry.get(id);
		if (cleanups) {
			cleanups.forEach(fn => {
				try {
					fn();
				} catch (e) {
					console.warn('ForgeWire cleanup error:', e);
				}
			});
			cleanupRegistry.delete(id);
		}
	}

	// ============================================================================
	// STATE MANAGEMENT
	// ============================================================================
	let composing = false;
	let tabbing = false;
	let suppressInputsUntil = 0;
	let pendingRedirectTimeout = null;

	document.addEventListener('compositionstart', () => composing = true);
	document.addEventListener('compositionend', () => composing = false);

	// ============================================================================
	// POLLING SYSTEM (IntersectionObserver)
	// ============================================================================
	let io = null;

	function ensureObserver() {
		if (io) return io;
		io = new IntersectionObserver((entries) => {
			entries.forEach((entry) => {
				const root = entry.target;
				const id = root.getAttribute('fw:id');
				if (!id) return;
				const rec = pollers.get(id);
				if (!rec) return;

				if (rec.el !== root) {
					rec.el = root;
				}

				rec.visible = entry.isIntersecting;

				if (rec.visible) {
					// Element became visible - start polling if not already polling
					if (!rec.timer) {
						schedulePoll(root);
					}
				} else {
					// Element became invisible - stop polling immediately
					if (rec.timer) {
						clearTimeout(rec.timer);
						rec.timer = null;
					}
				}
			});
		}, {root: null, threshold: 0});
		return io;
	}

	function setSuppressUntil(root, durationMs) {
		root.__fw_suppress_until = Date.now() + durationMs;
	}

	function shouldSuppress(root) {
		return (root.__fw_suppress_until && Date.now() < root.__fw_suppress_until);
	}

	// ============================================================================
	// DOM QUERIES & ELEMENT FINDING
	// ============================================================================
	function findPollTarget(root) {
		const names = getCachedAttributes(root);
		if (names.includes('fw:poll')) {
			const action = root.getAttribute('fw:action');
			return {el: root, everyMs: 2000, action: action || null};
		}
		const param = names.find(n => n.startsWith('fw:poll.'));
		if (param) {
			const action = root.getAttribute('fw:action');
			return {el: root, everyMs: parsePollInterval(param), action: action || null};
		}

		const walker = document.createTreeWalker(root, NodeFilter.SHOW_ELEMENT);
		while (walker.nextNode()) {
			const node = /** @type {Element} */(walker.currentNode);
			const ns = getCachedAttributes(node);
			if (ns.includes('fw:poll')) {
				const action = node.getAttribute('fw:action');
				return {el: node, everyMs: 2000, action: action || null};
			}
			const p = ns.find(n => n.startsWith('fw:poll.'));
			if (p) {
				const action = node.getAttribute('fw:action');
				return {el: node, everyMs: parsePollInterval(p), action: action || null};
			}
		}
		return null;
	}

	function findModelByKey(root, key) {
		const inputs = root.querySelectorAll('input, textarea, select');
		for (const n of inputs) {
			const b = getModelBinding(n);
			if (b && b.key === key) return n;
		}
		return null;
	}

	function collectParams(el) {
		const params = {};
		for (const name of getCachedAttributes(el)) {
			if (name.startsWith('fw:param-')) {
				const key = name.slice('fw:param-'.length);
				params[key] = el.getAttribute(name);
			}
		}
		return params;
	}

	function collectDepends(root) {
		const depends = new Set();
		const rootDepends = root.getAttribute('fw:depends');
		if (rootDepends) {
			rootDepends.split(',').forEach(d => {
				const trimmed = d.trim();
				if (trimmed) depends.add(trimmed);
			});
		}

		root.querySelectorAll('[fw\\:depends]').forEach(el => {
			const d = el.getAttribute('fw:depends');
			if (d) {
				d.split(',').forEach(v => {
					const trimmed = v.trim();
					if (trimmed) depends.add(trimmed);
				});
			}
		});
		return Array.from(depends);
	}

	// ============================================================================
	// ACTION PARSING
	// ============================================================================
	function parseAction(expr) {
		if (!expr) return {method: null, args: []};
		const t = expr.trim();
		const open = t.indexOf('(');
		if (open === -1) return {method: t, args: []};
		const method = t.slice(0, open).trim();
		const inner = t.slice(open + 1, t.lastIndexOf(')')).trim();
		if (!inner) return {method, args: []};

		const args = [];
		let cur = '', inS = false, inD = false, esc = false;
		for (let i = 0; i < inner.length; i++) {
			const ch = inner[i];
			if (esc) {
				cur += ch;
				esc = false;
				continue;
			}
			if (ch === '\\') {
				esc = true;
				continue;
			}
			if (ch === "'" && !inD) {
				inS = !inS;
				cur += ch;
				continue;
			}
			if (ch === '"' && !inS) {
				inD = !inD;
				cur += ch;
				continue;
			}
			if (ch === ',' && !inS && !inD) {
				args.push(cur.trim());
				cur = '';
				continue;
			}
			cur += ch;
		}
		if (cur.trim() !== '') args.push(cur.trim());

		const norm = (s) => {
			if ((s.startsWith("'") && s.endsWith("'")) || (s.startsWith('"') && s.endsWith('"'))) return s.slice(1, -1);
			if (s === 'true') return true;
			if (s === 'false') return false;
			if (s === 'null') return null;
			if (/^-?\d+(\.\d+)?$/.test(s)) return Number(s);
			return null;
		};
		return {method, args: args.map(norm)};
	}

	// ============================================================================
	// MODEL BINDING
	// ============================================================================
	function getModelBinding(el) {
		for (const name of getCachedAttributes(el)) {
			if (name === 'fw:model') return {key: el.getAttribute(name), type: 'immediate', debounce: 0};
			if (name === 'fw:model.lazy') return {key: el.getAttribute(name), type: 'lazy', debounce: null};
			if (name === 'fw:model.defer') return {key: el.getAttribute(name), type: 'defer', debounce: null};
			if (name === 'fw:model.debounce') return {
				key: el.getAttribute(name),
				type: 'debounce',
				debounce: DEFAULT_DEBOUNCE
			};
			if (name.startsWith('fw:model.debounce.')) {
				const parts = name.split('.');
				const msStr = parts[parts.length - 1];
				const ms = parseInt(msStr, 10);
				return {
					key: el.getAttribute(name),
					type: 'debounce',
					debounce: Number.isFinite(ms) ? ms : DEFAULT_DEBOUNCE
				};
			}
		}
		return null;
	}

	// ============================================================================
	// DIRTY STATE COLLECTION
	// ============================================================================
	function collectDirty(root) {
		const dirty = {};
		const inputs = root.querySelectorAll('input, textarea, select');
		for (const node of inputs) {
			const bind = getModelBinding(node);
			if (!bind) continue;

			let value;
			if (node.type === 'checkbox') {
				value = !!node.checked;
			} else if (node.type === 'radio') {
				if (!node.checked) continue;
				value = node.value;
			} else {
				value = node.value;
			}
			dirty[bind.key] = value;
		}
		return dirty;
	}

	// ============================================================================
	// NETWORK & COMMUNICATION
	// ============================================================================
	async function send(payload, signal) {
		const headers = {
			'Content-Type': 'application/json',
			'X-ForgeWire': 'true'
		};
		const csrf = window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
		if (csrf) headers['X-CSRF-TOKEN'] = csrf;

		const res = await fetch('/__wire', {method: 'POST', headers, body: JSON.stringify(payload), signal});
		if (!res.ok) {
			const text = await res.text();
			throw new Error(`Server error: ${res.status}. ${text.substring(0, 100)}`);
		}
		return res.json();
	}

	// ============================================================================
	// DEBOUNCE MANAGEMENT
	// ============================================================================
	function cancelDebounces(root) {
		root.querySelectorAll('[data-fw-timer-id]').forEach(n => {
			const id = Number(n.getAttribute('data-fw-timer-id'));
			if (id) clearTimeout(id);
			n.removeAttribute('data-fw-timer-id');
		});
	}

	// ============================================================================
	// REQUEST QUEUING
	// ============================================================================
	const queues = new Map();
	const processing = new Map();
	const inFlight = new Map();

	function getRequestKey(id, action, args) {
		const argsStr = JSON.stringify(args);
		return `${id}:${action || 'null'}:${argsStr}`;
	}

	async function trigger(root, action = null, args = [], dirtyOverride = null, options = null) {
		const id = attr(root, 'fw:id');
		if (!id) return;

		const reqKey = getRequestKey(id, action, args);
		if (inFlight.has(reqKey)) {
			return;
		}

		const req = {action, args, dirty: dirtyOverride ?? collectDirty(root), options: options || null};

		if (queues.has(id)) {
			queues.get(id).push(req);
			return;
		}

		if (processing.get(id)) {
			if (!queues.has(id)) {
				queues.set(id, []);
			}
			queues.get(id).push(req);
			return;
		}

		processing.set(id, true);
		const queue = [req];
		queues.set(id, queue);

		try {
			let currentRoot = root;
			while (queue.length > 0) {
				const nextReq = queue[0];
				const nextReqKey = getRequestKey(id, nextReq.action, nextReq.args);

				if (inFlight.has(nextReqKey)) {
					queue.shift();
					continue;
				}

				inFlight.set(nextReqKey, true);
				try {
					const result = await performTrigger(currentRoot, nextReq.action, nextReq.args, nextReq.dirty, nextReq.options);
					if (result && result.root) {
						currentRoot = result.root;
					}
				} finally {
					inFlight.delete(nextReqKey);
				}
				queue.shift();
			}
		} finally {
			queues.delete(id);
			processing.delete(id);
		}
	}

	// ============================================================================
	// COMPONENT UPDATE (HTML from server is trusted, but we validate structure)
	// ============================================================================
	function applyComponentUpdate(root, html, state, checksum, dirty = {}) {
		const id = attr(root, 'fw:id');

		const parser = new DOMParser();
		const doc = parser.parseFromString(html, 'text/html');

		const newRoot = doc.querySelector(`[fw\\:id="${id}"]`) || doc.body.firstElementChild;

		if (newRoot && newRoot.getAttribute('fw:id') === id) {
			cleanupComponent(id);
			root.replaceWith(newRoot);
			const updatedRoot = document.querySelector(`[fw\\:id="${id}"]`);

			if (updatedRoot) {
				updatedRoot.__fw_checksum = checksum || null;
				updatedRoot.setAttribute('fw:checksum', checksum || '');
				root = updatedRoot;
			}
		} else {
			const domTargets = root.querySelectorAll('[fw\\:target]');
			const matchingComponent = doc.querySelector(`[fw\\:id="${id}"]`);
			const docTargets = matchingComponent
				? matchingComponent.querySelectorAll('[fw\\:target]')
				: doc.querySelectorAll('[fw\\:target]');

			if (domTargets.length > 0 && docTargets.length === domTargets.length) {
				domTargets.forEach((el, i) => {
					el.innerHTML = docTargets[i].innerHTML;
					el.getAttributeNames().forEach(name => el.removeAttribute(name));
					for (const attr of docTargets[i].attributes) {
						el.setAttribute(attr.name, attr.value);
					}
					// Attributes changed - invalidate cache for this element and its children
					invalidateElementCache(el);
					el.querySelectorAll('*').forEach(child => invalidateElementCache(child));
				});

				root.__fw_checksum = checksum || null;
				root.setAttribute('fw:checksum', checksum || '');
			} else {
				root.innerHTML = html;
				root.__fw_checksum = checksum || null;
				root.setAttribute('fw:checksum', checksum || '');
				invalidateElementCache(root);
				root.querySelectorAll('*').forEach(child => invalidateElementCache(child));
			}
		}

		if (state) {
			root.__fw_state = state;
			const keyToElement = new Map();
			const inputs = root.querySelectorAll('input, textarea, select');
			for (const el of inputs) {
				const bind = getModelBinding(el);
				if (bind && bind.key) {
					keyToElement.set(bind.key, el);
				}
			}

			Object.entries(state).forEach(([key, val]) => {
				const el = keyToElement.get(key);
				if (el) {
					const isFocused = (document.activeElement === el);
					if (isFocused) {
						const sentValue = dirty[key];
						if (val !== undefined && val !== sentValue && el.value === sentValue) {
							const start = el.selectionStart ?? el.value.length;
							const end = el.selectionEnd ?? el.value.length;

							el.value = val;

							Promise.resolve().then(() => {
								if (document.activeElement === el && el.setSelectionRange) {
									try {

										const oldLength = sentValue ? sentValue.length : 0;
										const newLength = el.value.length;
										const lengthDiff = newLength - oldLength;
										const newStart = Math.max(0, Math.min(start + lengthDiff, newLength));
										const newEnd = Math.max(0, Math.min(end + lengthDiff, newLength));
										el.setSelectionRange(newStart, newEnd);
									} catch {
										//
									}
								}
							});
						}

					} else {
						if (el.type === 'checkbox') el.checked = !!val;
						else if (el.type === 'radio') el.checked = (el.value == val);
						else {
							if (el.value !== val) el.value = val;
						}
					}
				}
			});
		}

		return root;
	}

	// ============================================================================
	// TRIGGER EXECUTION
	// ============================================================================
	async function performTrigger(root, action = null, args = [], dirtyOverride = null, options = null) {
		if (pendingRedirectTimeout !== null) {
			clearTimeout(pendingRedirectTimeout);
			pendingRedirectTimeout = null;
		}

		const id = attr(root, 'fw:id');
		const pollRec = pollers.get(id);
		if (pollRec && pollRec.timer) {
			clearTimeout(pollRec.timer);
			pollRec.timer = null;
		}

		let currentRoot = document.querySelector(`[fw\\:id="${id}"]`);
		if (!currentRoot) {
			return {root};
		}
		root = currentRoot;

		let dirty = {};
		try {
			dirty = dirtyOverride ?? collectDirty(root);
		} catch {
			dirty = {};
		}

		const opts = options || {};
		if (action && opts.optimistic === true && optimisticHandlers[action]) {
			try {
				optimisticHandlers[action]({
					root,
					id,
					lastState: root.__fw_state || null,
					dirty,
					args
				});
			} catch (e) {
			}
		}

		let focusInfo = null;
		const active = document.activeElement;
		if (active && root.contains(active)) {
			const bind = getModelBinding(active);
			if (bind) {
				focusInfo = {key: bind.key, start: active.selectionStart ?? null, end: active.selectionEnd ?? null};
			}
		}

		cancelDebounces(root);
		root.setAttribute('fw:loading', '');

		let out;
		try {
			out = await send({
				id,
				controller: attr(root, 'fw:controller'),
				action, args,
				dirty,
				depends: collectDepends(root),
				checksum: root.__fw_checksum || root.getAttribute('fw:checksum') || null,
				fingerprint: {path: location.pathname}
			});
		} catch (e) {
			console.error('ForgeWire Request Failed:', e);
			root.removeAttribute('fw:loading');
			return {root};
		} finally {
			root.removeAttribute('fw:loading');
		}

		if (out.ignored) {
			return {root};
		}

		if (out.errors && Object.keys(out.errors).length > 0) {
			root.__fw_checksum = out.checksum || null;
			root.setAttribute('fw:checksum', out.checksum || '');

			const errorEls = root.querySelectorAll('[fw\\:validation-error]');
			errorEls.forEach(el => {
				const key = el.getAttribute('fw:validation-error');
				const messages = out.errors[key] || [];
				const showAll = el.hasAttribute('fw:validation-error.all');

				if (showAll && messages.length > 0) {
					el.textContent = messages.join(', ');
				} else {
					el.textContent = messages[0] || '';
				}
				el.style.display = messages.length ? '' : 'none';
			});

			const inputs = root.querySelectorAll('[fw\\:model], [fw\\:model\\.defer]');
			inputs.forEach(input => {
				const key = input.getAttribute('fw:model')
					|| input.getAttribute('fw:model.defer');

				if (out.errors[key]) {
					input.setAttribute('aria-invalid', 'true');
					input.classList.add('fw-invalid');
				} else {
					input.removeAttribute('aria-invalid');
					input.classList.remove('fw-invalid');
				}
			});

			return;
		}

		const errorEls = root.querySelectorAll('[fw\\:validation-error]');
		errorEls.forEach(el => {
			el.textContent = '';
			el.style.display = 'none';
		});

		root = applyComponentUpdate(root, out.html, out.state, out.checksum, dirty);

		setupPolling(root);

		if (out.updates && Array.isArray(out.updates)) {
			out.updates.forEach(update => {
				const affectedRoot = document.querySelector(`[fw\\:id="${update.id}"]`);
				if (affectedRoot) {
					applyComponentUpdate(affectedRoot, update.html, update.state, update.checksum, {});
					setupPolling(affectedRoot);
				}
			});
		}

		if (out.events && Array.isArray(out.events) && out.events.length > 0) {
			handleEvents(out.events);
		}

		if (out.flash && Array.isArray(out.flash) && out.flash.length > 0) {
			handleFlashMessages(out.flash);
		}

		if (out.redirect) {
			if (pendingRedirectTimeout !== null) {
				clearTimeout(pendingRedirectTimeout);
				pendingRedirectTimeout = null;
			}

			const redirectUrl = typeof out.redirect === 'string' ? out.redirect : out.redirect.url;
			const redirectDelay = typeof out.redirect === 'object' && out.redirect.delay ? out.redirect.delay : 0;

			if (isValidRedirect(redirectUrl)) {
				if (redirectDelay > 0) {
					pendingRedirectTimeout = setTimeout(() => {
						pendingRedirectTimeout = null;
						window.location.assign(redirectUrl);
					}, redirectDelay * 1000);
				} else {
					if (pendingRedirectTimeout !== null) {
						clearTimeout(pendingRedirectTimeout);
						pendingRedirectTimeout = null;
					}
					window.location.assign(redirectUrl);
				}
			}
			return {root};
		}

		if (focusInfo) {
			const next = findModelByKey(root, focusInfo.key);
			if (next) {
				next.focus();
				if (typeof focusInfo.start === 'number' && typeof next.setSelectionRange === 'function') {
					try {
						next.setSelectionRange(focusInfo.start, focusInfo.end ?? focusInfo.start);
					} catch {
					}
				}
			}
		}

		return {root};
	}

	// ============================================================================
	// POLLING SYSTEM
	// ============================================================================
	const pollers = new Map();

	function parsePollInterval(attrName) {
		const s = attrName.slice('fw:poll.'.length);
		if (s.endsWith('ms')) return Math.max(250, parseInt(s, 10) || 2000);
		if (s.endsWith('s')) return Math.max(250, (parseFloat(s) || 2) * 1000);
		return 2000;
	}

	function jitter(ms, pct = 0.05) {
		const d = ms * pct;
		return Math.max(250, ms + (Math.random() * 2 - 1) * d);
	}

	function setupPolling(root) {
		const id = root.getAttribute('fw:id');
		if (!id) return;

		const prev = pollers.get(id);
		if (prev) {
			if (prev.timer) {
				clearTimeout(prev.timer);
			}
			try {
				if (io && prev.el) {
					io.unobserve(prev.el);
				}
			} catch {
			}
		}

		const target = findPollTarget(root);
		if (!target) {
			if (prev) {
				pollers.delete(id);
				cleanupComponent(id);
			}
			return;
		}

		const every = target.everyMs | 0;
		const pollAction = target.action || null;

		const wasVisible = prev ? prev.visible : false;
		pollers.set(id, {el: root, timer: null, everyMs: every, visible: wasVisible, action: pollAction});

		const obs = ensureObserver();
		if (!prev || prev.el !== root) {
			obs.observe(root);
		}

		registerCleanup(id, () => {
			try {
				if (io && root) io.unobserve(root);
			} catch {
			}
		});

		requestAnimationFrame(() => {
			const rec = pollers.get(id);
			if (!rec) return;

			const currentRoot = document.querySelector(`[fw\\:id="${id}"]`);
			if (!currentRoot || currentRoot !== root) {
				rec.visible = false;
				return;
			}

			rec.el = currentRoot;

			const inDom = document.documentElement.contains(currentRoot);
			if (!inDom) {
				rec.visible = false;
				return;
			}

			const rect = currentRoot.getBoundingClientRect();
			const onScreen = rect.width > 0 && rect.height > 0 &&
				rect.bottom >= 0 && rect.right >= 0 &&
				rect.top <= (window.innerHeight || 0) &&
				rect.left <= (window.innerWidth || 0);

			rec.visible = onScreen;
			if (onScreen && !rec.timer && !queues.has(id)) {
				schedulePoll(currentRoot);
			}
		});
	}

	function schedulePoll(root) {
		const id = root.getAttribute('fw:id');
		const rec = pollers.get(id);
		if (!rec) return;

		if (rec.timer) {
			clearTimeout(rec.timer);
			rec.timer = null;
		}

		if (!rec.visible) return;
		if (!document.documentElement.contains(root)) {
			try {
				if (io) io.unobserve(root);
			} catch {
			}
			pollers.delete(id);
			cleanupComponent(id);
			return;
		}

		const wait = jitter(rec.everyMs);
		const timerId = setTimeout(() => {
			const currentRec = pollers.get(id);
			if (!currentRec || currentRec.timer !== timerId) {
				return;
			}

			const currentRoot = document.querySelector(`[fw\\:id="${id}"]`);
			if (!currentRoot || !document.documentElement.contains(currentRoot)) {
				try {
					if (io && currentRoot) io.unobserve(currentRoot);
				} catch {
				}
				pollers.delete(id);
				cleanupComponent(id);
				return;
			}

			if (document.hidden || !currentRec.visible) {
				currentRec.timer = null;
				return;
			}

			const pollAction = currentRec.action || null;
			const parsed = pollAction ? parseAction(pollAction) : {method: null, args: []};
			trigger(currentRoot, parsed.method, parsed.args);

			if (currentRec.timer === timerId && currentRec.visible && !queues.has(id)) {
				currentRec.timer = null;
				schedulePoll(currentRoot);
			}
		}, wait);
		rec.timer = timerId;

		registerCleanup(id, () => {
			if (timerId && rec.timer === timerId) {
				clearTimeout(timerId);
				rec.timer = null;
			}
		});
	}

	// ============================================================================
	// DIRECTIVE REGISTRY (Easy way to add new event-based directives)
	// ============================================================================

	/**
	 * Helper to handle common directive pattern: find element, parse action, collect params, trigger
	 * @param {Event} e - The DOM event
	 * @param {string} directiveName - The directive name (e.g., 'fw:click')
	 * @param {number} suppressMs - Milliseconds to suppress inputs after trigger (default: 120)
	 * @param {Function} beforeTrigger - Optional callback before triggering (receives el, root, e)
	 * @returns {boolean} - Returns true if directive was handled
	 */
	function handleDirective(e, directiveName, suppressMs = 120, beforeTrigger = null, opts = null) {
		const escapedName = directiveName.replace(/:/g, '\\:').replace(/\./g, '\\.');
		const el = e.target.closest(`[${escapedName}]`);
		if (!el) return false;

		const root = closestRoot(el);
		if (!root) return false;

		e.preventDefault();
		e.stopPropagation();
		setSuppressUntil(root, suppressMs);

		if (beforeTrigger) {
			beforeTrigger(el, root, e);
		}

		const parsed = parseAction(attr(el, directiveName));
		const params = collectParams(el);
		let combinedArgs = Array.isArray(parsed.args) ? [...parsed.args] : [];

		if (Object.keys(params).length > 0) {
			const obj = {};
			combinedArgs.forEach((v, i) => obj[i] = v);
			Object.assign(obj, params);
			combinedArgs = obj;
		}

		const options = opts || {};
		if (options.optimistic === true) {
			trigger(root, parsed.method, combinedArgs, null, {optimistic: true});
		} else {
			trigger(root, parsed.method, combinedArgs);
		}
		return true;
	}

	// ============================================================================
	// EVENT HANDLERS
	// ============================================================================

	document.addEventListener('click', (e) => {
		if (handleDirective(e, 'fw:click.optimistic', 120, null, {optimistic: true})) {
			return;
		}
		handleDirective(e, 'fw:click', 120);
	});

	document.addEventListener('submit', (e) => {
		const form = e.target.closest('[fw\\:submit],[fw\\:submit\\.optimistic]');
		if (!form) return;

		const lazyInputs = form.querySelectorAll('[fw\\:model\\.lazy]');
		lazyInputs.forEach(input => {
			if (document.activeElement === input) {
				const event = new Event('change', {bubbles: true});
				input.dispatchEvent(event);
			}
		});

		if (handleDirective(e, 'fw:submit.optimistic', 150, null, {optimistic: true})) {
			return;
		}
		handleDirective(e, 'fw:submit', 150);
	});

	document.addEventListener('input', (e) => {
		if (composing) return;

		const el = e.target;
		const root = closestRoot(el);

		if (!root) return;

		if (shouldSuppress(root)) return;

		const bind = getModelBinding(el);
		if (!bind) return;

		if (bind.type === 'debounce') {
			const wait = bind.debounce || DEFAULT_DEBOUNCE;
			const componentId = attr(root, 'fw:id');
			const prev = Number(el.getAttribute('data-fw-timer-id'));
			if (prev) clearTimeout(prev);

			const id = setTimeout(() => {
				if (shouldSuppress(root)) return;
				trigger(root, 'input');
			}, wait);
			el.setAttribute('data-fw-timer-id', String(id));

			if (componentId) {
				registerCleanup(componentId, () => {
					if (Number(el.getAttribute('data-fw-timer-id')) === id) {
						clearTimeout(id);
						el.removeAttribute('data-fw-timer-id');
					}
				});
			}
			return;
		}

		if (bind.type === 'immediate') {
			if (!tabbing) {
				trigger(root, 'input');
			}
		}
	});

	document.addEventListener('change', (e) => {
		if (Date.now() < suppressInputsUntil) return;
		const el = e.target;
		const bind = getModelBinding(el);
		if (!bind || bind.type !== 'lazy') return;
		const root = closestRoot(el);
		if (!root) return;
		setTimeout(() => {
			if (Date.now() < suppressInputsUntil) return;
			trigger(root, 'input');
		}, 0);
	});

	document.addEventListener('keydown', (e) => {
		if (e.key === 'Tab') {
			tabbing = true;
			setTimeout(() => {
				tabbing = false;
			}, 50);
		}
	});

	document.addEventListener('keydown', (e) => {
		const el = e.target;
		const root = closestRoot(el);
		if (!root) return;

		const key = e.key.toLowerCase();
		const attrs = getCachedAttributes(el);

		const keyMap = {
			'enter': 'enter',
			'esc': 'escape',
			'escape': 'escape',
			'backspace': 'backspace',
			'delete': 'delete'
		};
		const targetKey = keyMap[key] || key;

		const match = attrs.find(a => a === `fw:keydown.${targetKey}`);
		if (match) {
			const expr = el.getAttribute(match);
			if (expr) {
				e.preventDefault();
				e.stopPropagation();
				const parsed = parseAction(expr);
				const params = collectParams(el);
				let combinedArgs = Array.isArray(parsed.args) ? [...parsed.args] : [];

				if (Object.keys(params).length > 0) {
					const obj = {};
					combinedArgs.forEach((v, i) => obj[i] = v);
					Object.assign(obj, params);
					combinedArgs = obj;
				}

				trigger(root, parsed.method, combinedArgs);
			}
		}
	});

	// ============================================================================
	// INITIALIZATION
	// ============================================================================
	function initializePolling() {
		document.querySelectorAll('[fw\\:id]').forEach(root => {
			const pollTarget = findPollTarget(root);
			if (pollTarget) {
				setupPolling(root);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initializePolling);

		window.addEventListener('beforeunload', () => {
			if (pendingRedirectTimeout !== null) {
				clearTimeout(pendingRedirectTimeout);
				pendingRedirectTimeout = null;
			}
			cleanupRegistry.forEach((_, id) => cleanupComponent(id));
		});

		window.addEventListener('pagehide', () => {
			if (pendingRedirectTimeout !== null) {
				clearTimeout(pendingRedirectTimeout);
				pendingRedirectTimeout = null;
			}
			cleanupRegistry.forEach((_, id) => cleanupComponent(id));
		});
	} else {
		initializePolling();
	}

	// ============================================================================
	// BROWSER ACTIONS (Redirects, Flash Messages, Events)
	// ============================================================================
	function handleFlashMessages(flashes) {
		let container = document.getElementById('fw-flash-container');
		if (!container) {
			container = document.createElement('div');
			container.id = 'fw-flash-container';
			container.className = 'fw-flash-container';
			document.body.appendChild(container);
		}

		flashes.forEach(flash => {
			const messageEl = document.createElement('div');
			messageEl.className = 'fw-flash-message';
			messageEl.setAttribute('data-flash-type', flash.type || 'info');
			messageEl.setAttribute('role', 'alert');

			const textEl = document.createElement('div');
			textEl.className = 'fw-flash-message-text';
			textEl.textContent = flash.message || '';
			messageEl.appendChild(textEl);

			container.appendChild(messageEl);

			setTimeout(() => {
				messageEl.classList.add('fw-flash-message-dismissing');
				setTimeout(() => {
					if (messageEl.parentNode) {
						messageEl.parentNode.removeChild(messageEl);
					}
				}, 300);
			}, 5000);
		});
	}

	function handleEvents(events) {
		events.forEach(event => {
			if (!event.name || typeof event.name !== 'string') {
				return;
			}

			if (!/^[a-zA-Z0-9_-]+$/.test(event.name)) {
				console.warn('Invalid event name:', event.name);
				return;
			}

			const eventName = `fw:event:${event.name}`;
			const eventData = event.data || {};

			const customEvent = new CustomEvent(eventName, {
				detail: eventData,
				bubbles: true,
				cancelable: true,
			});

			document.dispatchEvent(customEvent);

			if (event.name === 'animateElement' && eventData.selector && eventData.animation) {
				const elements = document.querySelectorAll(eventData.selector);
				const duration = eventData.duration || 500;
				elements.forEach(el => {
					el.classList.add('fw-animate', `fw-animate-${eventData.animation}`);
					setTimeout(() => {
						el.classList.remove('fw-animate', `fw-animate-${eventData.animation}`);
					}, duration);
				});
			}
		});
	}

	function isValidRedirect(url) {
		if (typeof url !== 'string') {
			return false;
		}

		if (url.startsWith('/')) {
			return true;
		}

		try {
			const parsed = new URL(url, window.location.origin);
			return parsed.origin === window.location.origin;
		} catch {
			return false;
		}
	}
})();
