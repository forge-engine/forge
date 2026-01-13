<?php
    $id = $id ?? 'fw-modal';
    $size = $size ?? 'md';
    $closable = $closable ?? true;
    $backdrop = $backdrop ?? true;

    $sizeClasses = [
        'sm' => 'fw-modal-content-sm',
        'md' => 'fw-modal-content',
        'lg' => 'fw-modal-content-lg',
        'xl' => 'fw-modal-content-xl',
        '2xl' => 'fw-modal-content-2xl',
        'full' => 'fw-modal-content-full',
    ];

    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
?>
<div id="<?= e($id) ?>" class="fw-modal hidden" data-modal-id="<?= e($id) ?>">
    <?php if ($backdrop): ?>
    <div class="fw-modal-backdrop" data-modal-close></div>
    <?php endif; ?>
    
    <div class="fw-modal-wrapper">
        <div class="<?= $sizeClass ?>">
            <?php if ($closable): ?>
            <div class="flex justify-end p-2">
                <button class="fw-modal-close" data-modal-close aria-label="Close">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($slots['header']) || isset($slots['title'])): ?>
            <div class="fw-modal-header">
                <?php if (isset($slots['title'])): ?>
                    <h3 class="fw-modal-title"><?= $slots['title'] ?></h3>
                <?php elseif (isset($slots['header'])): ?>
                    <?= $slots['header'] ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="fw-modal-body">
                <?php if (isset($slots['default'])): ?>
                    <?= $slots['default'] ?>
                <?php elseif (isset($slots['message'])): ?>
                    <p class="fw-modal-message"><?= $slots['message'] ?></p>
                <?php endif; ?>
            </div>
            
            <?php if (isset($slots['footer']) || isset($slots['actions'])): ?>
            <div class="fw-modal-footer">
                <?php if (isset($slots['actions'])): ?>
                    <?= $slots['actions'] ?>
                <?php elseif (isset($slots['footer'])): ?>
                    <?= $slots['footer'] ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function() {
    const modal = document.getElementById('<?= e($id) ?>');
    if (!modal) return;

    function setupModalEvents() {
        const closeElements = modal.querySelectorAll('[data-modal-close]');
        closeElements.forEach(el => {
            el.addEventListener('click', () => {
                closeModal();
            });
        });

        const backdrop = modal.querySelector('.fw-modal-backdrop');
        if (backdrop) {
            backdrop.addEventListener('click', (e) => {
                if (e.target === backdrop) {
                    closeModal();
                }
            });
        }
    }

    function openModal() {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        requestAnimationFrame(() => {
            modal.style.display = '';
        });
    }

    function closeModal() {
        modal.classList.add('hidden');
        const visibleModals = document.querySelectorAll('.fw-modal:not(.hidden)');
        if (visibleModals.length === 0) {
            document.body.style.overflow = '';
        }
    }

    setupModalEvents();

    document.addEventListener('fw:event:openModal', (e) => {
        if (e.detail.id === '<?= e($id) ?>') {
            openModal();
        }
    });

    document.addEventListener('fw:event:closeModal', (e) => {
        const { id } = e.detail || {};
        if (!id || id === '<?= e($id) ?>') {
            closeModal();
        }
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeModal();
        }
    });
})();
</script>
