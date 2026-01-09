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
		if (names.includes('fw:poll')) return { el: root, everyMs: 2000 };
		const param = names.find(n => n.startsWith('fw:poll.'));
		if (param) return { el: root, everyMs: parsePollInterval(param) };

		const walker = document.createTreeWalker(root, NodeFilter.SHOW_ELEMENT);
		while (walker.nextNode()) {
			const node = /** @type {Element} */(walker.currentNode);
			const ns = node.getAttributeNames();
			if (ns.includes('fw:poll')) return { el: node, everyMs: 2000 };
			const p = ns.find(n => n.startsWith('fw:poll.'));
			if (p) return { el: node, everyMs: parsePollInterval(p) };
		}
		return null;
	}

	function priorityFor(action) {
		if (action === 'input' || action === null) return 1;
		return 2;
	}

	function findModelByKey(root, key) {
		const nodes = root.querySelectorAll('*');
		for (const n of nodes) {
			const b = getModelBinding(n);
			if (b && b.key === key) return n;
		}
		return null;
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
			try {
				return JSON.parse(s);
			} catch {
				return s;
			}
		};
		return { method, args: args.map(norm) };
	}

	function getModelBinding(el) {
		for (const name of el.getAttributeNames()) {
			if (name === 'fw:model') return { key: el.getAttribute(name), type: 'immediate', debounce: 0 };
			if (name === 'fw:model.lazy') return { key: el.getAttribute(name), type: 'lazy', debounce: null };
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
		return res.json();
	}

	function cancelDebounces(root) {
		root.querySelectorAll('[data-fw-timer-id]').forEach(n => {
			const id = Number(n.getAttribute('data-fw-timer-id'));
			if (id) clearTimeout(id);
			n.removeAttribute('data-fw-timer-id');
		});
	}

	const inflight = new Map();

	const queues = new Map();
	const pending = new Map();

	async function trigger(root, action = null, args = [], dirtyOverride = null) {
		const id = attr(root, 'fw:id');

		if (queues.get(id)) {
			if (action === 'input' || action === null) {
				pending.set(id, { action, args, dirty: dirtyOverride ?? collectDirty(root) });
			}
			return;
		}

		queues.set(id, true);

		try {
			let currentAction = action;
			let currentArgs = args;
			let currentDirty = dirtyOverride;

			while (true) {
				await performTrigger(root, currentAction, currentArgs, currentDirty);

				const next = pending.get(id);
				if (next) {
					pending.delete(id);
					currentAction = next.action;
					currentArgs = next.args;
					currentDirty = next.dirty;
					continue;
				}
				break;
			}
		} finally {
			queues.delete(id);
		}
	}

	async function performTrigger(root, action = null, args = [], dirtyOverride = null) {
		const id = attr(root, 'fw:id');
		const thisPri = priorityFor(action);

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

		// Note: We no longer abort inflight requests here because we use the queue to sequence them.
		// Aborting fetch doesn't stop the server from updating the session, which causes checksum mismatches.

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
			root.removeAttribute('fw:loading');
			return;
		} finally {
			root.removeAttribute('fw:loading');
		}

		const targetEl = root.querySelector('[fw\\:target]') || root;
		const parser = new DOMParser();
		const doc = parser.parseFromString(out.html, 'text/html');
		const newTargetContent = doc.querySelector('[fw\\:target]') || doc.querySelector(`[fw\\:id="${id}"]`);

		const finalHtml = newTargetContent ? newTargetContent.innerHTML : out.html;

		if (targetEl === root) {
			root.outerHTML = newTargetContent ? newTargetContent.outerHTML : out.html;
			const newRoot = document.querySelector(`[fw\\:id="${id}"]`);
			if (!newRoot) return;
			newRoot.__fw_checksum = out.checksum || null;
			newRoot.removeAttribute('fw:loading');
			root = newRoot;
		} else {
			targetEl.innerHTML = finalHtml;
			root.removeAttribute('fw:loading');
			root.__fw_checksum = out.checksum || null;
		}

		setupPolling(root);

		if (out.redirect) {
			window.location.assign(out.redirect);
			return;
		}

		if (focusInfo) {
			const next = findModelByKey(root, focusInfo.key);
			if (next) {
				// Only restore value if it was an input-related trigger
				if (dirty.hasOwnProperty(focusInfo.key)) {
					next.value = dirty[focusInfo.key];
				}
				next.focus();
				if (typeof focusInfo.start === 'number' && typeof next.setSelectionRange === 'function') {
					requestAnimationFrame(() => {
						try {
							next.setSelectionRange(focusInfo.start, focusInfo.end ?? focusInfo.start);
						} catch {
						}
					});
				}
			}
		}
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
		pollers.set(id, { el: root, timer: null, everyMs: every, visible: false });

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
			trigger(root, null, []);
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
		trigger(root, parsed.method, parsed.args);
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
		const dirtyNow = collectDirty(root);

		setTimeout(() => {
			trigger(root, parsed.method, parsed.args, dirtyNow);
		}, 0);
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
			const key = bind.key;
			const id = setTimeout(() => {
				if (shouldSuppress(root)) return;
				if (tabbing) {
					setTimeout(() => trigger(root, 'input', [key]), 0);
				} else {
					trigger(root, 'input');
				}
			}, wait);
			const prev = Number(el.getAttribute('data-fw-timer-id'));
			if (prev) clearTimeout(prev);
			el.setAttribute('data-fw-timer-id', String(id));
			return;
		}

		if (bind.type === 'immediate') {
			if (tabbing || shouldSuppress(root)) {
				setTimeout(() => trigger(root, 'input', [bind.key]), 0);
			} else {
				trigger(root, 'input', [bind.key]);
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
		if (e.key !== 'Enter') return;
		const el = e.target;
		const bind = getModelBinding(el);
		if (!bind) return;
		const form = el.closest('form');
		if (form && !form.hasAttribute('fw:submit')) {
			e.preventDefault();
		}
	});

	document.addEventListener('DOMContentLoaded', () => {
		document.querySelectorAll('[fw\\:id]').forEach(setupPolling);
	});
})();


