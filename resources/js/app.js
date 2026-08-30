import './bootstrap';
import '@fontsource/tajawal/arabic-400.css';
import '@fontsource/tajawal/arabic-500.css';
import '@fontsource/tajawal/arabic-700.css';
import '@fontsource/tajawal/arabic-800.css';

const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
let reducedMotion = motionPreference.matches;
let revealObserver;
const observedScopes = new WeakSet();
const registeredElements = new WeakSet();
const scheduledScopes = new WeakSet();

const ignoredTags = new Set(['SCRIPT', 'STYLE', 'TEMPLATE', 'NOSCRIPT']);

const hasManagedTransition = (element) => [...element.attributes].some(({ name }) => (
    name === 'x-show'
    || name === 'x-collapse'
    || name.startsWith('x-transition')
    || name === 'wire:loading'
));

const shouldReveal = (element, explicit = false) => {
    if (!(element instanceof HTMLElement) || ignoredTags.has(element.tagName)) return false;
    if (element.dataset.reveal === 'none' || element.hasAttribute('data-motion-ignore')) return false;
    if (element.hidden || element.getAttribute('aria-hidden') === 'true' || hasManagedTransition(element)) return false;

    if (!explicit && (
        element.classList.contains('absolute')
        || element.classList.contains('fixed')
        || element.classList.contains('sr-only')
    )) return false;

    return true;
};

const pageLevelChildren = (root) => {
    let container = root;
    let depth = 0;

    while (container.children.length === 1 && depth < 3) {
        const child = container.firstElementChild;

        if (!child || child.tagName !== 'DIV' || child.hasAttribute('data-reveal')) break;

        container = child;
        depth++;
    }

    return [...container.children].filter((element) => shouldReveal(element));
};

const revealCandidates = (scope = document) => {
    const candidates = new Set();
    const explicitElements = scope.matches?.('[data-reveal]')
        ? [scope, ...scope.querySelectorAll('[data-reveal]')]
        : [...scope.querySelectorAll('[data-reveal]')];

    explicitElements.forEach((element) => {
        if (shouldReveal(element, true)) candidates.add(element);
    });

    scope.querySelectorAll('article, figure, [data-motion-item]').forEach((element, index) => {
        if (!shouldReveal(element)) return;

        if (!element.style.getPropertyValue('--reveal-delay')) {
            element.style.setProperty('--reveal-delay', `${(index % 4) * 45}ms`);
        }

        candidates.add(element);
    });

    const revealGroups = scope.matches?.('[data-reveal-group]')
        ? [scope, ...scope.querySelectorAll('[data-reveal-group]')]
        : [...scope.querySelectorAll('[data-reveal-group]')];

    revealGroups.forEach((group) => {
        const effect = group.dataset.revealGroup || 'up';
        const stagger = Math.min(Math.max(Number(group.dataset.revealStagger) || 55, 0), 120);

        [...group.children].filter((element) => shouldReveal(element)).forEach((element, index) => {
            if (!element.dataset.reveal) element.dataset.reveal = effect;
            element.style.setProperty('--reveal-delay', `${Math.min(index, 6) * stagger}ms`);
            candidates.add(element);
        });
    });

    if (scope.matches?.('[data-page-reveal]')) {
        pageLevelChildren(scope).forEach((element) => candidates.add(element));
    } else {
        scope.querySelectorAll('[data-page-reveal]').forEach((root) => {
            pageLevelChildren(root).forEach((element) => candidates.add(element));
        });
    }

    return [...candidates].filter((element) => !registeredElements.has(element));
};

const settleReveal = (element) => {
    element.classList.remove('reveal-item', 'is-visible', 'is-settled');
    element.style.removeProperty('--reveal-delay');
};

const registerRevealElements = (scope = document) => {
    revealCandidates(scope).forEach((element, index) => {
        registeredElements.add(element);
        element.classList.add('reveal-item');

        if (!element.style.getPropertyValue('--reveal-delay')) {
            element.style.setProperty('--reveal-delay', `${Math.min(index, 4) * 45}ms`);
        }

        if (reducedMotion || !revealObserver) {
            settleReveal(element);
            return;
        }

        revealObserver.observe(element);
    });
};

const scheduleRevealRegistration = (scope) => {
    if (scheduledScopes.has(scope)) return;

    scheduledScopes.add(scope);
    requestAnimationFrame(() => {
        scheduledScopes.delete(scope);
        registerRevealElements(scope);
    });
};

const observeMotionScopes = () => {
    registerRevealElements(document);

    document.querySelectorAll('[data-page-reveal]').forEach((scope) => {
        if (observedScopes.has(scope)) return;

        observedScopes.add(scope);
        new MutationObserver(() => scheduleRevealRegistration(scope)).observe(scope, { childList: true, subtree: true });
        registerRevealElements(scope);
    });
};

const createRevealObserver = () => {
    revealObserver?.disconnect();
    revealObserver = null;

    if (reducedMotion || !('IntersectionObserver' in window)) return;

    revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;

            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);

            const settle = (event) => {
                if (event && event.propertyName !== 'transform') return;
                entry.target.removeEventListener('transitionend', settle);
                settleReveal(entry.target);
            };

            entry.target.addEventListener('transitionend', settle);
            window.setTimeout(() => settle(), 950);
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -5% 0px' });
};

const startPageMotion = () => {
    document.documentElement.classList.add('motion-ready');
    createRevealObserver();
    observeMotionScopes();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startPageMotion, { once: true });
} else {
    startPageMotion();
}

document.addEventListener('livewire:navigated', observeMotionScopes);

motionPreference.addEventListener('change', (event) => {
    reducedMotion = event.matches;
    createRevealObserver();

    if (reducedMotion) {
        document.querySelectorAll('.reveal-item').forEach((element) => {
            settleReveal(element);
        });
    }
});
