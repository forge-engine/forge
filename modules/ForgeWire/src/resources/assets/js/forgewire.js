(() => {
  const DEFAULT_DEBOUNCE = 600;
  const rootSel = '[wire\\:id]';
  const attr = (el,n) => el.getAttribute(n);
  const closestRoot = el => el.closest(rootSel);
  let composing = false;
  let tabbing = false;
  let suppressInputsUntil = 0; 
  
  document.addEventListener('compositionstart', () => composing = true);
  document.addEventListener('compositionend',   () => composing = false);
  
  let io = null;
  function ensureObserver() {
	if (io) return io;
	io = new IntersectionObserver((entries) => {
	  entries.forEach((entry) => {
		const root = entry.target;
		const id = root.getAttribute('wire:id');
		if (!id) return;
		const rec = pollers.get(id);
		if (!rec) return;
  
		rec.visible = entry.isIntersecting;
  
		if (rec.visible) {
		  if (!rec.timer) schedulePoll(root);
		} else {
		  if (rec.timer) { clearTimeout(rec.timer); rec.timer = null; }
		}
	  });
	}, { root: null, threshold: 0 });
	return io;
  }

  function findPollTarget(root) {
	const names = root.getAttributeNames();
	if (names.includes('wire:poll')) return { el: root, everyMs: 2000 };
	const param = names.find(n => n.startsWith('wire:poll.'));
	if (param) return { el: root, everyMs: parsePollInterval(param) };

	const walker = document.createTreeWalker(root, NodeFilter.SHOW_ELEMENT);
	while (walker.nextNode()) {
	  const node = /** @type {Element} */(walker.currentNode);
	  const ns = node.getAttributeNames();
	  if (ns.includes('wire:poll')) return { el: node, everyMs: 2000 };
	  const p = ns.find(n => n.startsWith('wire:poll.'));
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
	const inner  = t.slice(open + 1, t.lastIndexOf(')')).trim();
	if (!inner) return { method, args: [] };

	const args = [];
	let cur = '', inS = false, inD = false, esc = false;
	for (let i = 0; i < inner.length; i++) {
	  const ch = inner[i];
	  if (esc) { cur += ch; esc = false; continue; }
	  if (ch === '\\') { esc = true; continue; }
	  if (ch === "'" && !inD) { inS = !inS; cur += ch; continue; }
	  if (ch === '"' && !inS) { inD = !inD; cur += ch; continue; }
	  if (ch === ',' && !inS && !inD) { args.push(cur.trim()); cur = ''; continue; }
	  cur += ch;
	}
	if (cur.trim() !== '') args.push(cur.trim());

	const norm = (s) => {
	  if ((s.startsWith("'") && s.endsWith("'")) || (s.startsWith('"') && s.endsWith('"'))) return s.slice(1, -1);
	  if (s === 'true') return true;
	  if (s === 'false') return false;
	  if (s === 'null') return null;
	  if (/^-?\d+(\.\d+)?$/.test(s)) return Number(s);
	  try { return JSON.parse(s); } catch { return s; }
	};
	return { method, args: args.map(norm) };
  }

  function getModelBinding(el) {
	for (const name of el.getAttributeNames()) {
	  if (name === 'wire:model')             return { key: el.getAttribute(name), type: 'immediate', debounce: 0 };
	  if (name === 'wire:model.lazy')        return { key: el.getAttribute(name), type: 'lazy',      debounce: null };
	  if (name === 'wire:model.debounce')    return { key: el.getAttribute(name), type: 'debounce',  debounce: DEFAULT_DEBOUNCE };
	  if (name.startsWith('wire:model.debounce.')) {
		const ms = parseInt(name.slice('wire:model.debounce.'.length), 10);
		return { key: el.getAttribute(name), type: 'debounce', debounce: Number.isFinite(ms) ? ms : DEFAULT_DEBOUNCE };
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
	const headers = { 'Content-Type': 'application/json' };
	if (window.csrfToken) headers['X-CSRF-TOKEN'] = window.csrfToken;
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

  async function trigger(root, action = null, args = [], dirtyOverride = null) {
	const id = attr(root, 'wire:id');
	const thisPri = priorityFor(action);
  
	let dirty = {};
	try { dirty = dirtyOverride ?? collectDirty(root); } catch { dirty = {}; }

	let focusInfo = null;
	const active = document.activeElement;
	if (active && root.contains(active)) {
	  const bind = getModelBinding(active);
	  if (bind) {
		focusInfo = { key: bind.key, start: active.selectionStart ?? null, end: active.selectionEnd ?? null };
	  }
	}
  
	cancelDebounces(root);
	root.setAttribute('wire:loading', '');
  
	const prev = inflight.get(id);
	if (prev) {
	  if (prev.priority > thisPri) {
		root.removeAttribute('wire:loading');
		return;
	  }
	  try { prev.controller.abort(); } catch {}
	}
  
	const controller = new AbortController();
	inflight.set(id, { controller, priority: thisPri });
  
	let out;
	try {
	  out = await send({
		id,
		component: attr(root,'wire:component'),
		action, args,
		dirty,
		checksum: root.__fw_checksum || null,
		fingerprint: { path: location.pathname }
	  }, controller.signal);
	} catch (e) {
	  root.removeAttribute('wire:loading');
	  return;
	} finally {
	  const cur = inflight.get(id);
	  if (cur && cur.controller === controller) inflight.delete(id);
	}
  
	root.outerHTML = out.html;
	const newRoot = document.querySelector(`[wire\\:id="${id}"]`);
	if (!newRoot) return;
	newRoot.__fw_checksum = out.checksum || null;
	newRoot.removeAttribute('wire:loading');
	
	setupPolling(newRoot);
  
	if (out.redirect) {
		window.location.assign(out.redirect);
		return;
	  }
	  
	if (focusInfo) {
	  const next = findModelByKey(newRoot, focusInfo.key);
	  if (next) {
		next.focus();
		if (typeof focusInfo.start === 'number' && typeof next.setSelectionRange === 'function') {
		  requestAnimationFrame(() => {
			try { next.setSelectionRange(focusInfo.start, focusInfo.end ?? focusInfo.start); } catch {}
		  });
		}
	  }
	}
  }
  
  const pollers = new Map();

 function parsePollInterval(attrName) {
   // attrName is like 'wire:poll.5s' or 'wire:poll.300ms'
   const s = attrName.slice('wire:poll.'.length);
   if (s.endsWith('ms')) return Math.max(250, parseInt(s, 10) || 2000);
   if (s.endsWith('s'))  return Math.max(250, (parseFloat(s) || 2) * 1000);
   return 2000;
 }
 
 function jitter(ms, pct = 0.05) {
   const d = ms * pct;
   return Math.max(250, ms + (Math.random() * 2 - 1) * d);
 }
 
 function setupPolling(root) {
   const id = root.getAttribute('wire:id');
   if (!id) return;
 
   const prev = pollers.get(id);
   if (prev) {
	 if (prev.timer) clearTimeout(prev.timer);
	 try { if (io && prev.el) io.unobserve(prev.el); } catch {}
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
   const id = root.getAttribute('wire:id');
   const rec = pollers.get(id);
   if (!rec) return;
 
   if (!rec.visible) return;
   if (!document.documentElement.contains(root)) {
	 try { if (io) io.unobserve(root); } catch {}
	 pollers.delete(id);
	 return;
   }
 
   const wait = jitter(rec.everyMs);
   rec.timer = setTimeout(() => {
	 if (!document.documentElement.contains(root)) {
	   try { if (io) io.unobserve(root); } catch {}
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
   const el = e.target.closest('[wire\\:click]');
   if (!el) return;
   const root = closestRoot(el);
   if (!root) return;
   e.preventDefault();
 
   suppressInputsUntil = Date.now() + 120;
 
   const parsed = parseAction(attr(el, 'wire:click'));
   trigger(root, parsed.method, parsed.args);
 });
 
 document.addEventListener('submit', (e) => {
   const form = e.target.closest('[wire\\:submit]');
   if (!form) return;
   const root = closestRoot(form);
   if (!root) return;
   e.preventDefault();
 
   suppressInputsUntil = Date.now() + 150;
 
   const parsed = parseAction(attr(form, 'wire:submit'));
   const dirtyNow = collectDirty(root);  
   trigger(root, parsed.method, parsed.args, dirtyNow);
 });

  document.addEventListener('input', (e) => {
	if (composing) return;
	if (Date.now() < suppressInputsUntil) return;
	const el = e.target;
	const bind = getModelBinding(el);
	if (!bind) return;
	const root = closestRoot(el);
	if (!root) return;
  
	if (bind.type === 'debounce') {
	  const wait = bind.debounce || DEFAULT_DEBOUNCE;
	  const key = bind.key;
	  const id = setTimeout(() => {
		if (Date.now() < suppressInputsUntil) return;
		if (tabbing) { setTimeout(() => trigger(root, 'input' , [key]), 0); }
		else { trigger(root, 'input'); }
	  }, wait);
	  const prev = Number(el.getAttribute('data-fw-timer-id'));
	  if (prev) clearTimeout(prev);
	  el.setAttribute('data-fw-timer-id', String(id));
	  return;
	}
	if (bind.type === 'immediate') {
	  if (tabbing || Date.now() < suppressInputsUntil) {
		setTimeout(() => trigger(root, 'input' , [bind.key]), 0);
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
	  setTimeout(() => { tabbing = false; }, 50);
	}
  });
  
  document.addEventListener('keydown', (e) => {
	if (e.key !== 'Enter') return;
	const el = e.target;
	const bind = getModelBinding(el);
	if (!bind) return;
	const form = el.closest('form');
	if (form && !form.hasAttribute('wire:submit')) {
	  e.preventDefault();
	}
  });
  
  document.addEventListener('DOMContentLoaded', () => {
	document.querySelectorAll('[wire\\:id]').forEach(setupPolling);
  });
})();


