<?php layout('main') ?>

<?= component('ui/flash-container') ?>

<div class="container my-5">
    <h1 class="text-3xl font-bold mb-5">ForgeWire Browser Actions</h1>
    <p class="text-gray-600 mb-8">Examples of redirect, flash messages, and browser events triggered from controller actions</p>

    <div <?= fw_id('browser-actions-demo') ?> class="space-y-8">
        <section class="card p-6 shadow-sm">
            <h2 class="text-2xl font-semibold mb-4">Redirect</h2>
            <p class="text-gray-600 mb-4">Trigger a browser redirect from a controller action</p>
            <button fw:click="testRedirect" class="btn btn-primary">
                Redirect to Examples
            </button>
        </section>

        <section class="card p-6 shadow-sm">
            <h2 class="text-2xl font-semibold mb-4">Flash Messages</h2>
            <p class="text-gray-600 mb-4">Display flash messages that auto-dismiss after 5 seconds</p>
            <div class="flex gap-2 flex-wrap">
                <button fw:click="testFlashSuccess" class="btn bg-green-600 hover:bg-green-700 text-white">
                    Success Flash
                </button>
                <button fw:click="testFlashError" class="btn bg-red-600 hover:bg-red-700 text-white">
                    Error Flash
                </button>
                <button fw:click="testFlashInfo" class="btn bg-blue-600 hover:bg-blue-700 text-white">
                    Info Flash
                </button>
                <button fw:click="testFlashWarning" class="btn bg-yellow-600 hover:bg-yellow-700 text-white">
                    Warning Flash
                </button>
            </div>
        </section>

        <section class="card p-6 shadow-sm">
            <h2 class="text-2xl font-semibold mb-4">Modal Events</h2>
            <p class="text-gray-600 mb-4">Open and close modals via controller-dispatched events</p>
            <div class="flex gap-2">
                <button fw:click="openModal" fw:param-modalId="confirmDelete" fw:param-title="Confirm Delete" fw:param-message="Are you sure you want to delete this item?" class="btn btn-primary">
                    Open Delete Modal
                </button>
                <button fw:click="openModal" fw:param-modalId="infoModal" fw:param-title="Information" fw:param-message="This is an informational modal" class="btn btn-secondary">
                    Open Info Modal
                </button>
            </div>
        </section>

        <section class="card p-6 shadow-sm">
            <h2 class="text-2xl font-semibold mb-4">Notifications</h2>
            <p class="text-gray-600 mb-4">Trigger custom notifications via events</p>
            <div class="flex gap-2 flex-wrap">
                <button fw:click="showNotification" fw:param-type="success" fw:param-message="Operation completed successfully!" class="btn bg-green-600 hover:bg-green-700 text-white">
                    Success Notification
                </button>
                <button fw:click="showNotification" fw:param-type="error" fw:param-message="Something went wrong!" class="btn bg-red-600 hover:bg-red-700 text-white">
                    Error Notification
                </button>
            </div>
        </section>

        <section class="card p-6 shadow-sm">
            <h2 class="text-2xl font-semibold mb-4">Animations</h2>
            <p class="text-gray-600 mb-4">Trigger animations via events</p>
            <div class="flex gap-2">
                <button fw:click="triggerAnimation" fw:param-selector=".card" fw:param-animation="fadeIn" class="btn btn-primary">
                    Animate Cards
                </button>
            </div>
        </section>

        <section class="card p-6 shadow-sm">
            <h2 class="text-2xl font-semibold mb-4">Combined Actions</h2>
            <p class="text-gray-600 mb-4">Combine multiple actions: flash + event + redirect</p>
            <button fw:click="combinedAction" class="btn btn-primary">
                Combined Action
            </button>
        </section>
    </div>
</div>

<?= component('examples/modal-example') ?>

<script>
document.addEventListener('fw:event:openModal', (e) => {
    const { id, title, message } = e.detail;
    const modal = document.getElementById('fw-modal');
    const modalTitle = document.getElementById('fw-modal-title');
    const modalMessage = document.getElementById('fw-modal-message');
    
    if (modal && modalTitle && modalMessage) {
        modalTitle.textContent = title || '';
        modalMessage.textContent = message || '';
        modal.classList.remove('hidden');
    }
});

document.addEventListener('fw:event:closeModal', () => {
    const modal = document.getElementById('fw-modal');
    if (modal) {
        modal.classList.add('hidden');
    }
});

document.addEventListener('fw:event:showNotification', (e) => {
    const { type, message } = e.detail;
    const container = document.getElementById('fw-flash-container');
    if (!container) return;

    const notificationEl = document.createElement('div');
    notificationEl.className = 'fw-flash-message';
    notificationEl.setAttribute('data-flash-type', type || 'info');
    notificationEl.setAttribute('role', 'alert');
    
    const textEl = document.createElement('div');
    textEl.className = 'fw-flash-message-text';
    textEl.textContent = message || '';
    notificationEl.appendChild(textEl);

    container.appendChild(notificationEl);

    setTimeout(() => {
        notificationEl.classList.add('fw-flash-message-dismissing');
        setTimeout(() => {
            if (notificationEl.parentNode) {
                notificationEl.parentNode.removeChild(notificationEl);
            }
        }, 300);
    }, 5000);
});

</script>
