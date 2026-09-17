document.addEventListener('DOMContentLoaded', () => {
    const counters = document.querySelectorAll('[data-count-to]');
    counters.forEach((el) => animateCount(el, Number(el.dataset.countTo || 0)));

    const nav = document.querySelector('.nav-links');
    const toggle = document.querySelector('.nav-toggle');
    if (toggle && nav) {
        toggle.addEventListener('click', () => nav.classList.toggle('open'));
    }
});

function animateCount(element, target) {
    const duration = 1200;
    const start = 0;
    const startTime = performance.now();

    function update(now) {
        const progress = Math.min((now - startTime) / duration, 1);
        const value = Math.floor(progress * (target - start) + start);
        element.textContent = value;
        if (progress < 1) requestAnimationFrame(update);
    }

    requestAnimationFrame(update);
}
