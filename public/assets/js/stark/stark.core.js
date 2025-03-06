import StarkDirectives from "./stark.directives.js";
import Stark from "./stark.js";

const STATE = new WeakMap();
const BINDINGS = new WeakMap();
let UPDATE_SCHEDULED = false;

class ReactiveState {
    constructor(data, root) {
        return new Proxy(data, {
            set: (target, key, value) => {
                target[key] = value;
                Stark.update(root); // Use the imported Stark
                return true;
            }
        });
    }
}

function throttle(fn, limit) {
    let lastCall = 0;
    return function (...args) {
        const now = Date.now();
        if (now - lastCall >= limit) {
            lastCall = now;
            return fn.apply(this, args);
        }
    };
}

function debounce(fn, delay) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            fn.apply(this, args);
        }, delay);
    };
}

const StarkCore = {
    STATE,
    BINDINGS,
    UPDATE_SCHEDULED,
    ReactiveState,
    throttle,
    debounce,
    update: (root) => {
        if (!UPDATE_SCHEDULED) {
            requestAnimationFrame(() => {
                BINDINGS.get(root).forEach(update => update());
                UPDATE_SCHEDULED = false;
            });
            UPDATE_SCHEDULED = true;
        }
    },
    initData: (root, data) => {
        STATE.set(root, new ReactiveState(data, root));
        StarkDirectives.render(root);
    },
    init(selector) {
        document.querySelectorAll(selector).forEach(root => {
            const scriptTag = root.querySelector('script[type="module"]');
            if (!scriptTag) {
                STATE.set(root, new ReactiveState({}, root));
                StarkDirectives.render(root);
            }
        });
    },
};

export default StarkCore;