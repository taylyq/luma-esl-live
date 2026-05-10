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

const languageSelects = Array.from(document.querySelectorAll('[data-language-select]'));

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

if (languageSelects.length) {
    const savedLanguage = localStorage.getItem('luma_language') || 'en';
    languageSelects.forEach((select) => {
        select.value = savedLanguage;
    });
    document.documentElement.lang = savedLanguage;
    if (savedLanguage !== 'en') {
        setTranslateCookie(savedLanguage);
    }

    languageSelects.forEach((select) => {
        select.addEventListener('change', () => {
            localStorage.setItem('luma_language', select.value);
            document.documentElement.lang = select.value;
            setTranslateCookie(select.value);
            window.location.reload();
        });
    });
}

const lessons = document.querySelector('[data-lessons]');
if (lessons) {
    const buttons = Array.from(lessons.querySelectorAll('[data-lesson-topic]'));
    const title = lessons.querySelector('[data-lesson-title]');
    const unit = lessons.querySelector('[data-lesson-unit-label]');
    const image = lessons.querySelector('[data-lesson-image-preview]');
    const imageFrame = lessons.querySelector('[data-lesson-image-frame]');

    let selectedIndex = Math.max(0, buttons.findIndex((button) => button.classList.contains('active')));

    function selectLesson(index) {
        const button = buttons[index];
        if (!button) return;
        selectedIndex = index;

        buttons.forEach((item) => item.classList.remove('active'));
        button.classList.add('active');
        button.scrollIntoView({ block: 'nearest' });

        const nextTitle = button.dataset.lessonTopic || '';
        const nextUnit = button.dataset.lessonUnit || '';
        const nextImage = button.dataset.lessonImage || '';

        if (title) title.textContent = nextTitle;
        if (unit) unit.textContent = nextUnit;
        if (imageFrame) imageFrame.scrollTop = 0;
        if (!image || !nextImage) return;

        image.classList.add('loading');
        image.alt = `${nextTitle} lesson preview`;
        image.src = nextImage;
    }

    function stepLesson(direction) {
        const nextIndex = (selectedIndex + direction + buttons.length) % buttons.length;
        selectLesson(nextIndex);
    }

    buttons.forEach((button, index) => {
        button.addEventListener('click', () => selectLesson(index));
    });

    lessons.addEventListener('click', (event) => {
        if (!(event.target instanceof Element)) return;
        const previous = event.target.closest('[data-lesson-prev]');
        const next = event.target.closest('[data-lesson-next]');
        if (!previous && !next) return;

        event.preventDefault();
        stepLesson(previous ? -1 : 1);
    });

    document.addEventListener('keydown', (event) => {
        const tag = document.activeElement?.tagName;
        if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
        if (event.key === 'ArrowUp') {
            event.preventDefault();
            stepLesson(-1);
        }
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            stepLesson(1);
        }
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
