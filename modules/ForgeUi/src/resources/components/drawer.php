<?php
?>
<div id="fw-drawers-container"></div>

<script>
(function() {
    const container = document.getElementById('fw-drawers-container');
    if (!container) return;

    const drawers = new Map();

    function createDrawer(id, content, options = {}) {
        const existing = document.getElementById('fw-drawer-' + id);
        if (existing) {
            existing.remove();
        }

        const position = options.position || 'right';
        const width = options.width || (position === 'left' || position === 'right' ? '400px' : '100%');
        const height = options.height || (position === 'top' || position === 'bottom' ? '400px' : '100%');

        const backdrop = document.createElement('div');
        backdrop.className = 'fw-drawer-backdrop';
        backdrop.setAttribute('data-drawer-backdrop', id);

        const drawer = document.createElement('div');
        drawer.id = 'fw-drawer-' + id;
        drawer.className = 'fw-drawer fw-drawer-' + position;
        drawer.style.width = position === 'left' || position === 'right' ? width : '100%';
        drawer.style.height = position === 'top' || position === 'bottom' ? height : '100%';
        drawer.setAttribute('data-drawer-id', id);

        const header = document.createElement('div');
        header.className = 'fw-drawer-header';

        const title = document.createElement('h3');
        title.className = 'fw-drawer-title';
        title.textContent = options.title || '';

        const closeBtn = document.createElement('button');
        closeBtn.className = 'fw-drawer-close';
        closeBtn.innerHTML = `
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        `;
        closeBtn.setAttribute('aria-label', 'Close');

        header.appendChild(title);
        header.appendChild(closeBtn);

        const body = document.createElement('div');
        body.className = 'fw-drawer-body';
        body.innerHTML = content || '';

        drawer.appendChild(header);
        drawer.appendChild(body);

        document.body.appendChild(backdrop);
        document.body.appendChild(drawer);

        const close = () => {
            closeDrawer(id);
        };

        closeBtn.addEventListener('click', close);
        backdrop.addEventListener('click', close);

        drawers.set(id, { drawer, backdrop, close, options });
    }

    function openDrawer(id) {
        const data = drawers.get(id);
        if (!data) return;

        const { drawer, backdrop } = data;
        drawer.classList.add('open');
        backdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer(id) {
        const data = drawers.get(id);
        if (!data) return;

        const { drawer, backdrop } = data;
        drawer.classList.remove('open');
        backdrop.classList.remove('open');

        const visibleDrawers = Array.from(document.querySelectorAll('.fw-drawer.open'));
        if (visibleDrawers.length === 0) {
            document.body.style.overflow = '';
        }
    }

    function removeDrawer(id) {
        const data = drawers.get(id);
        if (!data) return;

        const { drawer, backdrop } = data;
        drawer.remove();
        backdrop.remove();
        drawers.delete(id);
    }

    document.addEventListener('fw:event:openDrawer', (e) => {
        const { id, content, ...options } = e.detail;
        const existing = drawers.get(id);
        if (existing) {
            if (content) {
                const body = existing.drawer.querySelector('.fw-drawer-body');
                if (body) {
                    body.innerHTML = content;
                }
            }
            openDrawer(id);
        } else {
            createDrawer(id, content, options);
            openDrawer(id);
        }
    });

    document.addEventListener('fw:event:closeDrawer', (e) => {
        const { id } = e.detail || {};
        if (id) {
            closeDrawer(id);
        } else {
            drawers.forEach((_, drawerId) => closeDrawer(drawerId));
        }
    });

    document.addEventListener('fw:event:removeDrawer', (e) => {
        const { id } = e.detail || {};
        if (id) {
            removeDrawer(id);
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const visibleDrawer = document.querySelector('.fw-drawer.open');
            if (visibleDrawer) {
                const id = visibleDrawer.getAttribute('data-drawer-id');
                if (id) {
                    closeDrawer(id);
                }
            }
        }
    });
})();
</script>
