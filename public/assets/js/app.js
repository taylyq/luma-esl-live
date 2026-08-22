const navToggle = document.querySelector('[data-nav-toggle]');
const nav = document.querySelector('[data-nav]');
const themeToggle = document.querySelector('[data-theme-toggle]');

function applyTheme(theme) {
    const safeTheme = theme === 'dark' ? 'dark' : 'light';
    document.documentElement.dataset.theme = safeTheme;

    if (themeToggle) {
        const isDark = safeTheme === 'dark';
        themeToggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
        themeToggle.setAttribute('aria-label', isDark ? 'Switch to day mode' : 'Switch to night mode');
        themeToggle.title = isDark ? 'Switch to day mode' : 'Switch to night mode';
    }
}

if (themeToggle) {
    let savedTheme = 'light';
    try {
        savedTheme = localStorage.getItem('luma_theme') === 'dark' ? 'dark' : 'light';
    } catch (error) {
        savedTheme = 'light';
    }
    applyTheme(savedTheme);

    themeToggle.addEventListener('click', () => {
        const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
        try {
            localStorage.setItem('luma_theme', nextTheme);
        } catch (error) {
            // Theme still changes for this page view when storage is unavailable.
        }
        applyTheme(nextTheme);
    });
}

if (navToggle && nav) {
    navToggle.addEventListener('click', () => {
        const isOpen = nav.classList.toggle('open');
        navToggle.classList.toggle('active', isOpen);
        navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        navToggle.setAttribute('aria-label', isOpen ? 'Close navigation' : 'Open navigation');
    });

    nav.addEventListener('click', (event) => {
        if (!(event.target instanceof Element) || !event.target.closest('a')) return;
        nav.classList.remove('open');
        navToggle.classList.remove('active');
        navToggle.setAttribute('aria-expanded', 'false');
        navToggle.setAttribute('aria-label', 'Open navigation');
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !nav.classList.contains('open')) return;
        nav.classList.remove('open');
        navToggle.classList.remove('active');
        navToggle.setAttribute('aria-expanded', 'false');
        navToggle.focus();
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

const captcha = document.querySelector('[data-captcha]');
if (captcha) {
    const answer = captcha.querySelector('[data-captcha-answer]');
    const drop = captcha.querySelector('[data-captcha-drop]');
    const tiles = Array.from(captcha.querySelectorAll('[data-captcha-tile]'));

    function selectCaptchaTile(tile) {
        if (!tile || !answer || !drop) return;

        answer.value = tile.dataset.captchaValue || '';
        tiles.forEach((item) => item.classList.remove('selected'));
        tile.classList.add('selected');
        drop.textContent = tile.textContent.trim();
        drop.classList.add('filled');
    }

    tiles.forEach((tile) => {
        tile.addEventListener('dragstart', (event) => {
            if (!event.dataTransfer) return;
            event.dataTransfer.setData('text/plain', tile.dataset.captchaValue || '');
            event.dataTransfer.effectAllowed = 'move';
        });

        tile.addEventListener('click', () => selectCaptchaTile(tile));
    });

    if (drop) {
        drop.addEventListener('dragover', (event) => {
            event.preventDefault();
            if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
        });

        drop.addEventListener('drop', (event) => {
            event.preventDefault();
            const value = event.dataTransfer?.getData('text/plain') || '';
            selectCaptchaTile(tiles.find((tile) => tile.dataset.captchaValue === value));
        });

        drop.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            selectCaptchaTile(tiles[0]);
        });
    }
}

document.querySelectorAll('.flash').forEach((flash) => {
    setTimeout(() => {
        flash.style.opacity = '0';
        flash.style.transform = 'translate(-50%, -8px)';
    }, 2800);
});

document.querySelectorAll('[data-upload-progress]').forEach((form) => {
    if (!(form instanceof HTMLFormElement)) return;

    const status = form.querySelector('[data-upload-status]');
    const title = form.querySelector('[data-upload-title]');
    const text = form.querySelector('[data-upload-text]');
    const bar = form.querySelector('[data-upload-bar]');
    const fileInputs = Array.from(form.querySelectorAll('input[type="file"]'));

    function selectedFiles() {
        return fileInputs.flatMap((input) => Array.from(input.files || []));
    }

    function setUploadStatus(nextTitle, nextText, percent = 0, mode = '') {
        if (status instanceof HTMLElement) {
            status.hidden = false;
            status.dataset.state = mode;
        }
        if (title) title.textContent = nextTitle;
        if (text) text.textContent = nextText;
        if (bar instanceof HTMLElement) {
            bar.style.width = `${Math.max(0, Math.min(100, percent))}%`;
        }
    }

    fileInputs.forEach((input) => {
        input.addEventListener('change', () => {
            const files = selectedFiles();
            if (!files.length) {
                if (status instanceof HTMLElement) status.hidden = true;
                return;
            }

            const totalSize = files.reduce((sum, file) => sum + file.size, 0);
            const fileNames = files.map((file) => file.name).join(', ');
            const sizeMb = (totalSize / 1024 / 1024).toFixed(2);
            setUploadStatus(`${files.length} file${files.length === 1 ? '' : 's'} ready`, `${fileNames} · ${sizeMb} MB`, 0, 'ready');
        });
    });

    form.addEventListener('submit', (event) => {
        const files = selectedFiles();
        if (!files.length) return;

        event.preventDefault();
        const xhr = new XMLHttpRequest();
        const formData = new FormData(form);
        const submitButtons = Array.from(form.querySelectorAll('button[type="submit"]'));

        submitButtons.forEach((button) => {
            button.disabled = true;
        });
        setUploadStatus('Uploading lesson images', 'Starting upload...', 2, 'uploading');

        xhr.upload.addEventListener('progress', (progressEvent) => {
            if (!progressEvent.lengthComputable) {
                setUploadStatus('Uploading lesson images', 'Uploading...', 35, 'uploading');
                return;
            }

            const percent = Math.round((progressEvent.loaded / progressEvent.total) * 100);
            setUploadStatus('Uploading lesson images', `${percent}% complete`, percent, 'uploading');
        });

        xhr.addEventListener('load', () => {
            if (xhr.status >= 200 && xhr.status < 400) {
                setUploadStatus('Upload complete', 'Saving changes...', 100, 'complete');
                window.location.href = xhr.responseURL || '/admin';
                return;
            }

            submitButtons.forEach((button) => {
                button.disabled = false;
            });
            setUploadStatus('Upload failed', 'Please try again.', 100, 'error');
        });

        xhr.addEventListener('error', () => {
            submitButtons.forEach((button) => {
                button.disabled = false;
            });
            setUploadStatus('Upload failed', 'Network error. Please try again.', 100, 'error');
        });

        xhr.open((form.method || 'POST').toUpperCase(), form.action);
        xhr.send(formData);
    });
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
    let savedLanguage = 'en';
    try {
        savedLanguage = localStorage.getItem('luma_language') || 'en';
    } catch (error) {
        savedLanguage = 'en';
    }
    languageSelects.forEach((select) => {
        select.value = savedLanguage;
    });
    document.documentElement.lang = savedLanguage;
    if (savedLanguage !== 'en') {
        setTranslateCookie(savedLanguage);
    }

    languageSelects.forEach((select) => {
        select.addEventListener('change', () => {
            try {
                localStorage.setItem('luma_language', select.value);
            } catch (error) {
                // Translation still applies for this page view when storage is unavailable.
            }
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
    const preloadedLessonImages = new Set();

    function preloadLessonImage(index) {
        const button = buttons[index];
        const src = button?.dataset.lessonImage || '';
        if (!src || preloadedLessonImages.has(src)) return;

        preloadedLessonImages.add(src);
        const preload = new Image();
        preload.decoding = 'async';
        preload.src = src;
    }

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
        preloadLessonImage((selectedIndex + 1) % buttons.length);
        preloadLessonImage((selectedIndex - 1 + buttons.length) % buttons.length);
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

    preloadLessonImage(selectedIndex);
    preloadLessonImage((selectedIndex + 1) % buttons.length);
}
