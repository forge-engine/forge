document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.forge-debugbar-tab');
    const panels = document.querySelectorAll('.forge-debugbar-panel');
    const logo = document.querySelector('.forge-debugbar-logo');
    const debugbarPanelsContainer = document.querySelector('.forge-debugbar-panels');

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const tabName = this.dataset.tab;

            if (debugbarPanelsContainer.classList.contains('forge-debugbar-panel-hidden')) {
                debugbarPanelsContainer.classList.remove('forge-debugbar-panel-hidden');
            }
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            document.getElementById(`debugbar-panel-${tabName}`).classList.add('active');
        });
    });

    if (logo && debugbarPanelsContainer) {
        logo.addEventListener('click', function (e) {
            debugbarPanelsContainer.classList.toggle('forge-debugbar-panel-hidden');
        });
    }
    
    const toggles = document.querySelectorAll('.clickable-toggle');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const targetElement = document.querySelector(targetId);
    
            if (targetElement) {
                targetElement.classList.toggle('is-collapsed');
                this.classList.toggle('active');
            }
        });
    });
    
});