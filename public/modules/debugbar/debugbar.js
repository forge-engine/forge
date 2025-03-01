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
            // Deactivate all tabs and panels
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));

            // Activate clicked tab and corresponding panel
            this.classList.add('active');
            document.getElementById(`debugbar-panel-${tabName}`).classList.add('active');
        });
    });

    if (logo && debugbarPanelsContainer) { // Check if both elements exist
        logo.addEventListener('click', function (e) {
            debugbarPanelsContainer.classList.toggle('forge-debugbar-panel-hidden');
        });
    }
});