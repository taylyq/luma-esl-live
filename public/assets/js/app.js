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

const lessons = document.querySelector('[data-lessons]');
if (lessons) {
    const buttons = lessons.querySelectorAll('[data-lesson-topic]');
    const title = lessons.querySelector('[data-lesson-title]');
    const unit = lessons.querySelector('[data-lesson-unit-label]');
    const image = lessons.querySelector('[data-lesson-image-preview]');

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            buttons.forEach((item) => item.classList.remove('active'));
            button.classList.add('active');

            const nextTitle = button.dataset.lessonTopic || '';
            const nextUnit = button.dataset.lessonUnit || '';
            const nextImage = button.dataset.lessonImage || '';

            if (title) title.textContent = nextTitle;
            if (unit) unit.textContent = nextUnit;
            if (!image || !nextImage) return;

            image.classList.add('loading');
            image.alt = `${nextTitle} lesson preview`;
            image.src = nextImage;
        });
    });

    if (image) {
        image.addEventListener('load', () => {
            image.classList.remove('loading');
        });

        image.addEventListener('error', () => {
            image.classList.remove('loading');
        });
    }
}
