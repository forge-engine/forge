document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.file-button').forEach(button => {
        button.addEventListener('click', function () {
            const targetTrace = document.getElementById(button.dataset.target);
            document.querySelectorAll('.file-button').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.stack-trace-item').forEach(item => item.classList.remove('active'));
            button.classList.add('active');
            targetTrace.classList.add('active');
        });
    });

    document.querySelectorAll('.tab-button').forEach(button => {
        button.addEventListener('click', function () {
            const targetTab = document.getElementById(button.dataset.target);
            document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            button.classList.add('active');
            targetTab.classList.add('active');
        });
    });
});