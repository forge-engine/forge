(() => {
	const DEFAULT_DEBOUNCE = 600;
	const rootSel = '[fw\\:id]';
	const attr = (el, n) => el.getAttribute(n);
	const closestRoot = el => el.closest(rootSel);
	let composing = false;
	let tabbing = false;
	let suppressInputsUntil = 0;

	document.addEventListener('compositionstart', () => composing = true);
	document.addEventListener('compositionend', () => composing = false);

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

				rec.visible = entry.isIntersecting;

				if (rec.visible) {
					if (!rec.timer) schedulePoll(root);
				} else {
					if (rec.timer) {
						clearTimeout(rec.timer);
						rec.timer = null;
					}
				}
			});
		}, { root: null, threshold: 0 });
		return io;
	}

	function setSuppressUntil(root, durationMs) {
		root.__fw_suppress_until = Date.now() + durationMs;
	}

	function shouldSuppress(root) {
		return (root.__fw_suppress_until && Date.now() < root.__fw_suppress_until);
	}

	function findPollTarget(root) {
		const names = root.getAttributeNames();
		if (names.includes('fw:poll')) {
			const action = root.getAttribute('fw:action');
			return { el: root, everyMs: 2000, action: action || null };
		}
		const param = names.find(n => n.startsWith('fw:poll.'));
		if (param) {
			const action = root.getAttribute('fw:action');
			return { el: root, everyMs: parsePollInterval(param), action: action || null };
		}

		const walker = document.createTreeWalker(root, NodeFilter.SHOW_ELEMENT);
		while (walker.nextNode()) {
			const node = /** @type {Element} */(walker.currentNode);
			const ns = node.getAttributeNames();
			if (ns.includes('fw:poll')) {
				const action = node.getAttribute('fw:action');
				return { el: node, everyMs: 2000, action: action || null };
			}
			const p = ns.find(n => n.startsWith('fw:poll.'));
			if (p) {
				const action = node.getAttribute('fw:action');
				return { el: node, everyMs: parsePollInterval(p), action: action || null };
			}
		}
		return null;
	}

	function findModelByKey(root, key) {
		const nodes = root.querySelectorAll('*');
		for (const n of nodes) {
			const b = getModelBinding(n);
			if (b && b.key === key) return n;
		}
		return null;
	}

	function collectParams(el) {
		const params = {};
		for (const name of el.getAttributeNames()) {
			if (name.startsWith('fw:param-')) {
				const key = name.slice('fw:param-'.length);
				params[key] = el.getAttribute(name);
			}
		}
		return params;
	}

	function parseAction(expr) {
		if (!expr) return { method: null, args: [] };
		const t = expr.trim();
		const open = t.indexOf('(');
		if (open === -1) return { method: t, args: [] };
		const method = t.slice(0, open).trim();
		const inner = t.slice(open + 1, t.lastIndexOf(')')).trim();
		if (!inner) return { method, args: [] };

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
		return { method, args: args.map(norm) };
	}

	function getModelBinding(el) {
		for (const name of el.getAttributeNames()) {
			if (name === 'fw:model') return { key: el.getAttribute(name), type: 'immediate', debounce: 0 };
			if (name === 'fw:model.lazy') return { key: el.getAttribute(name), type: 'lazy', debounce: null };
			if (name === 'fw:model.defer') return { key: el.getAttribute(name), type: 'defer', debounce: null };
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

	function collectDirty(root) {
		const dirty = {};
		root.querySelectorAll('*').forEach(node => {
			const bind = getModelBinding(node);
			if (!bind) return;

			let value;
			if (node.type === 'checkbox') {
				value = !!node.checked;
			} else if (node.type === 'radio') {
				if (!node.checked) return;
				value = node.value;
			} else {
				value = node.value;
			}
			dirty[bind.key] = value;
		});
		return dirty;
	}

	async function send(payload, signal) {
		const headers = {
			'Content-Type': 'application/json',
			'X-ForgeWire': 'true'
		};
		const csrf = window.csrfToken || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
		if (csrf) headers['X-CSRF-TOKEN'] = csrf;

		const res = await fetch('/__wire', { method: 'POST', headers, body: JSON.stringify(payload), signal });
		if (!res.ok) {
			const text = await res.text();
			throw new Error(`Server error: ${res.status}. ${text.substring(0, 100)}`);
		}
		return res.json();
	}

	function cancelDebounces(root) {
		root.querySelectorAll('[data-fw-timer-id]').forEach(n => {
			const id = Number(n.getAttribute('data-fw-timer-id'));
			if (id) clearTimeout(id);
			n.removeAttribute('data-fw-timer-id');
		});
	}

	const queues = new Map(); // id -> Array of requests

	async function trigger(root, action = null, args = [], dirtyOverride = null) {
		const id = attr(root, 'fw:id');
		const req = { action, args, dirty: dirtyOverride ?? collectDirty(root) };

		if (queues.has(id)) {
			queues.get(id).push(req);
			return;
		}

		const queue = [req];
		queues.set(id, queue);

		try {
			let currentRoot = root;
			while (queue.length > 0) {
				const nextReq = queue[0];
				const result = await performTrigger(currentRoot, nextReq.action, nextReq.args, nextReq.dirty);
				if (result && result.root) {
					currentRoot = result.root;
				}
				queue.shift();
			}
		} finally {
			queues.delete(id);
		}
	}

	function applyComponentUpdate(root, html, state, checksum, dirty = {}) {
		const id = attr(root, 'fw:id');

		const parser = new DOMParser();
		const doc = parser.parseFromString(html, 'text/html');

		const newRoot = doc.querySelector(`[fw\\:id="${id}"]`) || doc.body.firstElementChild;

		if (newRoot && newRoot.getAttribute('fw:id') === id) {
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
				});

				root.__fw_checksum = checksum || null;
				root.setAttribute('fw:checksum', checksum || '');
			} else {
				root.innerHTML = html;
				root.__fw_checksum = checksum || null;
				root.setAttribute('fw:checksum', checksum || '');
			}
		}

		// Sync server state back to ALL model-bound elements
		if (state) {
			Object.entries(state).forEach(([key, val]) => {
				const el = findModelByKey(root, key);
				if (el) {
					const isFocused = (document.activeElement === el);
					if (isFocused) {
						// Only update focused element if server says it's DIFFERENT from what was sent OR what it is now
						if (val !== undefined && val !== el.value && val !== dirty[key]) {
							el.value = val;
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

	async function performTrigger(root, action = null, args = [], dirtyOverride = null) {
		const id = attr(root, 'fw:id');

		let dirty = {};
		try {
			dirty = dirtyOverride ?? collectDirty(root);
		} catch {
			dirty = {};
		}

		let focusInfo = null;
		const active = document.activeElement;
		if (active && root.contains(active)) {
			const bind = getModelBinding(active);
			if (bind) {
				focusInfo = { key: bind.key, start: active.selectionStart ?? null, end: active.selectionEnd ?? null };
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
				checksum: root.__fw_checksum || root.getAttribute('fw:checksum') || null,
				fingerprint: { path: location.pathname }
			});
		} catch (e) {
			console.error('ForgeWire Request Failed:', e);
			root.removeAttribute('fw:loading');
			return { root };
		} finally {
			root.removeAttribute('fw:loading');
		}

		if (out.ignored) {
			return { root };
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

		// Process updates for affected components (shared state changes)
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
			if (isValidRedirect(out.redirect)) {
				window.location.assign(out.redirect);
			}
			return { root };
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

		return { root };
	}

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
			if (prev.timer) clearTimeout(prev.timer);
			try {
				if (io && prev.el) io.unobserve(prev.el);
			} catch {
			}
			pollers.delete(id);
		}

		const target = findPollTarget(root);
		if (!target) return;

		const every = target.everyMs | 0;
		const pollAction = target.action || null;
		pollers.set(id, { el: root, timer: null, everyMs: every, visible: false, action: pollAction });

		const obs = ensureObserver();
		obs.observe(root);

		requestAnimationFrame(() => {
			const rec = pollers.get(id);
			if (!rec) return;
			const inDom = document.documentElement.contains(root);
			const rect = root.getBoundingClientRect();
			const onScreen = inDom && rect.width > 0 && rect.height > 0 &&
				rect.bottom >= 0 && rect.right >= 0 &&
				rect.top <= (window.innerHeight || 0) &&
				rect.left <= (window.innerWidth || 0);
			if (onScreen) {
				rec.visible = true;
				if (!rec.timer) schedulePoll(root);
			}
		});
	}

	function schedulePoll(root) {
		const id = root.getAttribute('fw:id');
		const rec = pollers.get(id);
		if (!rec) return;

		if (!rec.visible) return;
		if (!document.documentElement.contains(root)) {
			try {
				if (io) io.unobserve(root);
			} catch {
			}
			pollers.delete(id);
			return;
		}

		const wait = jitter(rec.everyMs);
		rec.timer = setTimeout(() => {
			if (!document.documentElement.contains(root)) {
				try {
					if (io) io.unobserve(root);
				} catch {
				}
				pollers.delete(id);
				return;
			}
			if (document.hidden || !rec.visible) {
				rec.timer = null;
				schedulePoll(root);
				return;
			}
			const pollAction = rec.action || null;
			const parsed = pollAction ? parseAction(pollAction) : { method: null, args: [] };
			trigger(root, parsed.method, parsed.args);
			rec.timer = null;
			schedulePoll(root);
		}, wait);
	}

	// -- events ----------------------------------------------------------------

	document.addEventListener('click', (e) => {
		const el = e.target.closest('[fw\\:click]');
		if (!el) return;
		const root = closestRoot(el);
		if (!root) return;
		e.preventDefault();

		setSuppressUntil(root, 120);

		const parsed = parseAction(attr(el, 'fw:click'));
		const params = collectParams(el);
		let combinedArgs = Array.isArray(parsed.args) ? [...parsed.args] : [];

		if (Object.keys(params).length > 0) {
			const obj = {};
			combinedArgs.forEach((v, i) => obj[i] = v);
			Object.assign(obj, params);
			combinedArgs = obj;
		}

		trigger(root, parsed.method, combinedArgs);
	});

	document.addEventListener('submit', (e) => {
		const form = e.target.closest('[fw\\:submit]');
		if (!form) return;
		const root = closestRoot(form);
		if (!root) return;
		e.preventDefault();

		const lazyInputs = form.querySelectorAll('[fw\\:model\\.lazy]');
		lazyInputs.forEach(input => {
			if (document.activeElement === input) {
				const event = new Event('change', { bubbles: true });
				input.dispatchEvent(event);
			}
		});

		setSuppressUntil(root, 150);

		const parsed = parseAction(attr(form, 'fw:submit'));
		const params = collectParams(form);
		let combinedArgs = Array.isArray(parsed.args) ? [...parsed.args] : [];

		if (Object.keys(params).length > 0) {
			const obj = {};
			combinedArgs.forEach((v, i) => obj[i] = v);
			Object.assign(obj, params);
			combinedArgs = obj;
		}

		trigger(root, parsed.method, combinedArgs);
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
			const id = setTimeout(() => {
				if (shouldSuppress(root)) return;
				trigger(root, 'input');
			}, wait);
			const prev = Number(el.getAttribute('data-fw-timer-id'));
			if (prev) clearTimeout(prev);
			el.setAttribute('data-fw-timer-id', String(id));
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
		const attrs = el.getAttributeNames();

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
	} else {
		initializePolling();
	}

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
