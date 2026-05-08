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

const languageSelect = document.querySelector('[data-language-select]');

function setTranslateCookie(language) {
    const value = language === 'en' ? '' : `/en/${language}`;
    const maxAge = language === 'en' ? 'Max-Age=0' : 'Max-Age=31536000';
    const hostParts = window.location.hostname.split('.');
    const rootDomain = hostParts.length > 2 ? `.${hostParts.slice(-2).join('.')}` : window.location.hostname;

    document.cookie = `googtrans=${value}; Path=/; ${maxAge}; SameSite=Lax`;
    document.cookie = `googtrans=${value}; Path=/; Domain=${rootDomain}; ${maxAge}; SameSite=Lax`;
}

window.googleTranslateElementInit = function () {
    if (!window.google || !window.google.translate) return;

    new window.google.translate.TranslateElement({
        pageLanguage: 'en',
        includedLanguages: 'en,vi,es',
        autoDisplay: false,
    }, 'google_translate_element');
};

if (languageSelect) {
    const savedLanguage = localStorage.getItem('luma_language') || 'en';
    languageSelect.value = savedLanguage;
    document.documentElement.lang = savedLanguage;
    if (savedLanguage !== 'en') {
        setTranslateCookie(savedLanguage);
    }

    languageSelect.addEventListener('change', () => {
        localStorage.setItem('luma_language', languageSelect.value);
        document.documentElement.lang = languageSelect.value;
        setTranslateCookie(languageSelect.value);
        window.location.reload();
    });
}
