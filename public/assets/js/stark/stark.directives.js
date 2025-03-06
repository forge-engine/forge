import StarkCore from './stark.core.js';

const parseDirectives = (element) => {
    const directives = {};
    for (const {name, value} of element.attributes) {
        if (name.startsWith('st-')) {
            const [type, ...modifiers] = name.slice(3).split(':');
            directives[type] = {value, modifiers};
        }
    }
    return directives;
};

// Helper to safely access nested object properties
const getValueByPath = (obj, path) => {
    return path.split('.').reduce((acc, key) => acc && acc[key], obj);
};

// Helper to safely set nested object properties
const setValueByPath = (obj, path, value) => {
    const keys = path.split('.');
    const lastKey = keys.pop();
    const target = keys.reduce((acc, key) => acc[key] = acc[key] || {}, obj);
    target[lastKey] = value;
};

const createBinding = (node, expr, root) => {
    const update = () => {
        try {
            const state = StarkCore.STATE.get(root);
            const value = getValueByPath(state, expr);
            node.textContent = value ?? '';
        } catch (e) {
            console.error(`Error updating binding ${expr}:`, e);
        }
    };
    StarkCore.BINDINGS.get(root).push(update);
    update();
};

const render = (root) => {
    if (!StarkCore.BINDINGS.has(root)) StarkCore.BINDINGS.set(root, []);

    const walk = (node) => {
        if (node.nodeType === Node.TEXT_NODE) {
            const matches = node.textContent.match(/\{\{(.*?)\}\}/g);
            if (matches) {
                const parent = node.parentNode;
                const marker = document.createComment('stark-binding');
                parent.replaceChild(marker, node);

                matches.forEach((match) => {
                    const expr = match.slice(2, -2).trim();
                    const textNode = document.createTextNode('');
                    parent.insertBefore(textNode, marker);
                    createBinding(textNode, expr, root);
                });
                parent.removeChild(marker);
            }
            return;
        }

        const directives = parseDirectives(node);

        // Handle st-model (2-way binding)
        if (directives.model) {
            const prop = directives.model.value;
            const modifiers = directives.model.modifiers;

            const state = StarkCore.STATE.get(root);

            let handler = (e) => {
                let value = e.target.value;

                if (modifiers.includes('number')) {
                    value = parseFloat(value);
                    if (isNaN(value)) return;
                }
                if (modifiers.includes('trim')) {
                    value = value.trim();
                }

                setValueByPath(state, prop, value);
                StarkCore.update(root);
            };

            if (modifiers.includes('debounce')) {
                const timeout = parseInt(modifiers[modifiers.indexOf('debounce') + 1] || 300);
                handler = StarkCore.debounce(handler, timeout);
            } else if (modifiers.includes('throttle')) {
                const timeout = parseInt(modifiers[modifiers.indexOf('throttle') + 1] || 500);
                handler = StarkCore.throttle(handler, timeout);
            }

            node.value = getValueByPath(state, prop) ?? '';
            node.addEventListener('input', handler);
        }

        // Handle st-bind (now supports input changes)
        if (directives.bind) {
            const prop = directives.bind.value;
            const [attr] = directives.bind.modifiers;
            const state = StarkCore.STATE.get(root);

            const update = () => {
                const value = getValueByPath(state, prop);
                if (attr === 'value' && node.tagName === 'INPUT') {
                    node.value = value ?? '';
                    node.addEventListener('input', (e) => {
                        setValueByPath(state, prop, e.target.value);
                        StarkCore.update(root);
                    });
                } else {
                    node.setAttribute(attr, value);
                }
            };

            StarkCore.BINDINGS.get(root).push(update);
            update();
        }

        // Handle st-if (conditional rendering)
        if (directives.if) {
            const prop = directives.if.value;
            const state = StarkCore.STATE.get(root);
            const parent = node.parentNode;
            const marker = document.createComment('st-if');

            const update = () => {
                if (getValueByPath(state, prop)) {
                    if (!parent.contains(node)) parent.insertBefore(node, marker);
                } else {
                    if (parent.contains(node)) parent.replaceChild(marker, node);
                }
            };

            StarkCore.BINDINGS.get(root).push(update);
            update();
        }

        // Handle st-show (visibility toggle)
        if (directives.show) {
            const prop = directives.show.value;
            const state = StarkCore.STATE.get(root);

            const update = () => {
                node.style.display = getValueByPath(state, prop) ? '' : 'none';
            };

            StarkCore.BINDINGS.get(root).push(update);
            update();
        }

        // Handle st-on
        if (directives.on) {
            const [eventType, ...modifiers] = directives.on.modifiers;
            const [handlerName] = directives.on.value.split('|').map((s) => s.trim());

            const state = StarkCore.STATE.get(root);

            let handler = (e) => {
                if (modifiers.includes('prevent')) e.preventDefault();
                if (modifiers.includes('stop')) e.stopPropagation();
                if (modifiers.includes('self') && e.target !== node) return;

                if (typeof state[handlerName] === 'function') {
                    state[handlerName].call(state, e);
                    StarkCore.update(root);
                }
            };

            if (modifiers.includes('once')) {
                const originalHandler = handler;
                handler = (e) => {
                    originalHandler(e);
                    node.removeEventListener(eventType, handler);
                };
            }

            if (modifiers.includes('throttle')) {
                const timeout = parseInt(modifiers[modifiers.indexOf('throttle') + 1] || 500);
                handler = StarkCore.throttle(handler, timeout);
            }

            node.addEventListener(eventType, handler);
        }

        node.childNodes.forEach(walk);
    };

    walk(root);
    StarkCore.BINDINGS.get(root).forEach((update) => update());
};

const StarkDirectives = {
    parseDirectives,
    createBinding,
    render,
};

export default StarkDirectives;