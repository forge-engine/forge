function toggleDarkMode() {
    let element = document.body;
    element.classList.toggle("dark-theme");
}

document.addEventListener('DOMContentLoaded', function () {
    const darkMoodeToggleButton = document.getElementById('darkModeToggle');

    if (darkMoodeToggleButton) {
        darkMoodeToggleButton.addEventListener('click', toggleDarkMode);
    }
});