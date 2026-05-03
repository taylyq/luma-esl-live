const navToggle = document.querySelector('[data-nav-toggle]');
const nav = document.querySelector('[data-nav]');

if (navToggle && nav) {
    navToggle.addEventListener('click', () => {
        nav.classList.toggle('open');
    });
}

document.querySelectorAll('[data-prompts] button').forEach((button) => {
    button.addEventListener('click', () => {
        const input = document.querySelector('[data-message-input]');
        if (!input) return;
        input.value = button.textContent.trim();
        input.focus();
    });
});

const stream = document.querySelector('.message-stream');
if (stream) {
    stream.scrollTop = stream.scrollHeight;
}

document.querySelectorAll('.flash').forEach((flash) => {
    setTimeout(() => {
        flash.style.opacity = '0';
        flash.style.transform = 'translate(-50%, -8px)';
    }, 2800);
});
