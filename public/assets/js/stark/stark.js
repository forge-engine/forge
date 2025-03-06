// stark.js
import StarkCore from './stark.core.js';
import StarkDirectives from './stark.directives.js';
import StarkComponents from './stark.components.js';

const Stark = {
    ...StarkCore,
    ...StarkDirectives,
    ...StarkComponents
};

document.addEventListener('DOMContentLoaded', () => Stark.init());

Stark.createComponent('user-card', `
    <style>
        .card { border: 1px solid #ccc; padding: 1rem; }
    </style>
    <div class="card">
        <slot name="name"></slot>
        <slot name="email"></slot>
    </div>
`);

export default Stark;