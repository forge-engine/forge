<?php
?>
<div id="fw-flash-container" class="fw-flash-container"></div>

<style>
.fw-flash-container {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 50;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.fw-flash-message {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    min-width: 300px;
    max-width: 28rem;
    transition: opacity 0.3s ease-out;
}

.fw-flash-message[data-flash-type="success"] {
    background-color: #dcfce7;
    border: 1px solid #86efac;
    color: #15803d;
}

.fw-flash-message[data-flash-type="error"] {
    background-color: #fee2e2;
    border: 1px solid #fca5a5;
    color: #991b1b;
}

.fw-flash-message[data-flash-type="info"] {
    background-color: #dbeafe;
    border: 1px solid #93c5fd;
    color: #1e40af;
}

.fw-flash-message[data-flash-type="warning"] {
    background-color: #fef3c7;
    border: 1px solid #fde047;
    color: #854d0e;
}

.fw-flash-message-dismissing {
    opacity: 0;
}

.fw-flash-message-text {
    word-wrap: break-word;
}

.fw-animate {
    animation-duration: 0.5s;
    animation-timing-function: ease-in-out;
    animation-fill-mode: both;
}

.fw-animate-fadeIn {
    animation-name: fw-fadeIn;
}

.fw-animate-fadeOut {
    animation-name: fw-fadeOut;
}

.fw-animate-slideIn {
    animation-name: fw-slideIn;
}

.fw-animate-slideOut {
    animation-name: fw-slideOut;
}

@keyframes fw-fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

@keyframes fw-fadeOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
    }
}

@keyframes fw-slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes fw-slideOut {
    from {
        transform: translateY(0);
        opacity: 1;
    }
    to {
        transform: translateY(-20px);
        opacity: 0;
    }
}
</style>
