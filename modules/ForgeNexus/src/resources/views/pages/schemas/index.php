<?php
use Forge\Core\View\View;

View::layout(name: "nexus", loadFromModule: true);
?>



<!-- Chart section -->
<section class="card chart-card">
    <header class="card-header">
        <h2 class="card-title">Performance Overview</h2>
        <div class="card-actions">
            <button class="card-action-button">
                <i class="fa-solid fa-ellipsis-vertical"></i>
            </button>
        </div>
    </header>
    <div class="card-body">
        <div class="chart-placeholder">
            <div class="placeholder-text">Chart Visualization</div>
        </div>
    </div>
</section>