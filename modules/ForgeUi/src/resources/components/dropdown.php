<?php
?>
<div id="fw-dropdowns-container"></div>

<script>
(function() {
    const container = document.getElementById('fw-dropdowns-container');
    if (!container) return;

    const dropdowns = new Map();

    function createDropdown(id, items, options = {}) {
        const existing = document.getElementById('fw-dropdown-' + id);
        if (existing) {
            existing.remove();
        }

        const dropdown = document.createElement('div');
        dropdown.id = 'fw-dropdown-' + id;
        dropdown.className = 'fw-dropdown';
        dropdown.setAttribute('data-dropdown-id', id);

        const menu = document.createElement('div');
        menu.className = 'fw-dropdown-menu';
        menu.setAttribute('role', 'menu');

        items.forEach((item, index) => {
            if (item.divider) {
                const divider = document.createElement('div');
                divider.className = 'fw-dropdown-divider';
                menu.appendChild(divider);
            } else {
                const menuItem = document.createElement('div');
                menuItem.className = 'fw-dropdown-item';
                if (item.disabled) {
                    menuItem.classList.add('disabled');
                }
                menuItem.setAttribute('role', 'menuitem');
                menuItem.setAttribute('tabindex', item.disabled ? '-1' : '0');
                menuItem.textContent = item.label || item.text || '';
                
                if (item.icon) {
                    const icon = document.createElement('span');
                    icon.innerHTML = item.icon;
                    icon.className = 'mr-2';
                    menuItem.insertBefore(icon, menuItem.firstChild);
                }

                if (!item.disabled && (item.action || item.onClick)) {
                    menuItem.addEventListener('click', () => {
                        if (item.action) {
                            window.location.href = item.action;
                        } else if (item.onClick) {
                            item.onClick();
                        }
                        closeDropdown(id);
                    });
                }

                menu.appendChild(menuItem);
            }
        });

        dropdown.appendChild(menu);
        container.appendChild(dropdown);

        dropdowns.set(id, { dropdown, menu, items, options });
    }

    function openDropdown(id, targetElement) {
        const data = dropdowns.get(id);
        if (!data) return;

        const { dropdown, menu } = data;
        const targetRect = targetElement.getBoundingClientRect();
        const menuRect = menu.getBoundingClientRect();

        menu.style.top = (targetRect.bottom + 4) + 'px';
        menu.style.left = targetRect.left + 'px';
        menu.classList.add('open');

        document.addEventListener('click', function closeOnOutside(e) {
            if (!dropdown.contains(e.target) && !targetElement.contains(e.target)) {
                closeDropdown(id);
                document.removeEventListener('click', closeOnOutside);
            }
        });
    }

    function closeDropdown(id) {
        const data = dropdowns.get(id);
        if (data) {
            data.menu.classList.remove('open');
        }
    }

    function toggleDropdown(id, targetElement) {
        const data = dropdowns.get(id);
        if (data && data.menu.classList.contains('open')) {
            closeDropdown(id);
        } else {
            openDropdown(id, targetElement);
        }
    }

    document.addEventListener('fw:event:createDropdown', (e) => {
        const { id, items, ...options } = e.detail;
        createDropdown(id, items, options);
    });

    document.addEventListener('fw:event:openDropdown', (e) => {
        const { id, target } = e.detail;
        const element = typeof target === 'string' ? document.querySelector(target) : target;
        if (element) {
            openDropdown(id, element);
        }
    });

    document.addEventListener('fw:event:closeDropdown', (e) => {
        const { id } = e.detail || {};
        if (id) {
            closeDropdown(id);
        } else {
            dropdowns.forEach((_, dropdownId) => closeDropdown(dropdownId));
        }
    });

    document.addEventListener('fw:event:toggleDropdown', (e) => {
        const { id, target } = e.detail;
        const element = typeof target === 'string' ? document.querySelector(target) : target;
        if (element) {
            toggleDropdown(id, element);
        }
    });

    if (window.FwComponentManager) {
        window.FwComponentManager.delegate('keydown', '.fw-dropdown-item', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    }
})();
</script>
