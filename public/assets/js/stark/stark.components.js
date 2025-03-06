const createComponent = (name, template) => {
    customElements.define(name, class extends HTMLElement {
        connectedCallback() {
            const shadow = this.attachShadow({mode: 'open'});
            shadow.innerHTML = template;
        }
    });
};

const StarkComponents = {
    createComponent
};

export default StarkComponents;