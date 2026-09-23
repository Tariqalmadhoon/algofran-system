import './app';
import '../css/public-site.css';

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)');

const initializePublicNavigation = () => {
    const root = document.querySelector('[data-public-navigation]');
    const header = root?.querySelector('[data-public-header]');
    const trigger = root?.querySelector('[data-public-menu-trigger]');
    const menu = root?.querySelector('[data-public-mobile-menu]');

    if (!root || !header || !trigger || !menu || root.dataset.navigationReady === 'true') return;

    root.dataset.navigationReady = 'true';

    const setMenuState = (open, restoreFocus = false) => {
        trigger.setAttribute('aria-expanded', String(open));
        trigger.setAttribute('aria-label', open ? 'إغلاق القائمة' : 'فتح القائمة');
        menu.setAttribute('aria-hidden', String(!open));
        menu.inert = !open;
        menu.classList.toggle('is-open', open);

        if (restoreFocus) trigger.focus({ preventScroll: true });
    };

    trigger.addEventListener('click', () => {
        setMenuState(trigger.getAttribute('aria-expanded') !== 'true');
    });

    menu.addEventListener('click', (event) => {
        if (event.target.closest('a')) setMenuState(false);
    });

    document.addEventListener('click', (event) => {
        if (!header.contains(event.target)) setMenuState(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && trigger.getAttribute('aria-expanded') === 'true') {
            setMenuState(false, true);
        }
    });

    const desktopMedia = window.matchMedia('(min-width: 64rem)');
    desktopMedia.addEventListener('change', (event) => {
        if (event.matches) setMenuState(false);
    });

    let scrollFrame;
    const updateHeader = () => {
        scrollFrame = undefined;
        header.classList.toggle('is-scrolled', window.scrollY > 12);
    };

    window.addEventListener('scroll', () => {
        if (scrollFrame) return;
        scrollFrame = window.requestAnimationFrame(updateHeader);
    }, { passive: true });

    updateHeader();
};

const initializeCounters = () => {
    const counters = [...document.querySelectorAll('[data-public-counter]')]
        .filter((element) => element.dataset.counterReady !== 'true');

    if (counters.length === 0) return;

    const formatter = new Intl.NumberFormat('ar');

    const showFinalValue = (element) => {
        const value = Number(element.dataset.publicCounter || 0);
        element.textContent = formatter.format(value);
        element.dataset.counterReady = 'true';
    };

    if (reducedMotion.matches || !('IntersectionObserver' in window)) {
        counters.forEach(showFinalValue);
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;

            const element = entry.target;
            const target = Number(element.dataset.publicCounter || 0);
            const startedAt = performance.now();
            const duration = 760;

            element.dataset.counterReady = 'true';
            observer.unobserve(element);

            const tick = (now) => {
                const progress = Math.min((now - startedAt) / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                element.textContent = formatter.format(Math.round(target * eased));

                if (progress < 1) window.requestAnimationFrame(tick);
            };

            window.requestAnimationFrame(tick);
        });
    }, { threshold: .55 });

    counters.forEach((element) => observer.observe(element));
};

const initializeHeroDepth = () => {
    if (reducedMotion.matches || !finePointer.matches || !('PointerEvent' in window)) return;

    document.querySelectorAll('[data-public-tilt]').forEach((card) => {
        if (card.dataset.tiltReady === 'true') return;

        card.dataset.tiltReady = 'true';
        let frame;
        let nextX = 0;
        let nextY = 0;

        const render = () => {
            frame = undefined;
            card.style.setProperty('--public-tilt-x', `${nextX.toFixed(2)}deg`);
            card.style.setProperty('--public-tilt-y', `${nextY.toFixed(2)}deg`);
        };

        const schedule = () => {
            if (!frame) frame = window.requestAnimationFrame(render);
        };

        card.addEventListener('pointermove', (event) => {
            const bounds = card.getBoundingClientRect();
            const horizontal = ((event.clientX - bounds.left) / bounds.width) - .5;
            const vertical = ((event.clientY - bounds.top) / bounds.height) - .5;

            nextX = vertical * -3;
            nextY = horizontal * 3;
            card.classList.add('is-tilting');
            schedule();
        }, { passive: true });

        card.addEventListener('pointerleave', () => {
            nextX = 0;
            nextY = 0;
            card.classList.remove('is-tilting');
            schedule();
        });
    });
};


const initializePublicSite = () => {
    initializePublicNavigation();
    initializeCounters();
    initializeHeroDepth();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePublicSite, { once: true });
} else {
    initializePublicSite();
}
