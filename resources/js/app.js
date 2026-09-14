import '@fontsource/tajawal/arabic-400.css';
import '@fontsource/tajawal/arabic-500.css';
import '@fontsource/tajawal/arabic-700.css';
import '@fontsource/tajawal/arabic-800.css';
import { initializeFeedbackAlerts } from './feedback';

if (document.querySelector('meta[name="user-id"]')) {
    import('./bootstrap');
}

const motionPreference = window.matchMedia('(prefers-reduced-motion: reduce)');
let reducedMotion = motionPreference.matches;
let revealObserver;
let navigationSafetyTimer;
let livewireRequests = 0;
let networkFeedbackTimer;
const observedScopes = new Map();
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
    if (element.closest('[data-reveal="none"], [data-motion-ignore]')) return false;
    if (element.hidden || element.getAttribute('aria-hidden') === 'true' || hasManagedTransition(element)) return false;

    // A transformed container changes the positioning of dialogs and fixed feedback.
    if (element.matches('.fixed, [role="dialog"], [data-feedback-stack]')
        || element.querySelector('.fixed, [role="dialog"], [data-feedback-stack]')) return false;

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
    const explicitSelector = '[data-reveal], [data-motion="reveal"]';
    const explicitElements = scope.matches?.(explicitSelector)
        ? [scope, ...scope.querySelectorAll(explicitSelector)]
        : [...scope.querySelectorAll(explicitSelector)];

    explicitElements.forEach((element) => {
        if (shouldReveal(element, true)) candidates.add(element);
    });

    scope.querySelectorAll('[data-motion-item]').forEach((element, index) => {
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
            element.style.setProperty('--reveal-delay', `${Math.min(index * stagger, 220)}ms`);
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

    // Reveal the cards inside a section, without also moving their shared container.
    const containers = new Set();
    candidates.forEach((element) => {
        for (let parent = element.parentElement; parent; parent = parent.parentElement) {
            if (candidates.has(parent)) containers.add(parent);
        }
    });

    return [...candidates].filter((element) => !containers.has(element) && !registeredElements.has(element));
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
        if (!scope.isConnected) return;
        registerRevealElements(scope);
        initializeFeedbackAlerts(scope);
    });
};

const observeMotionScopes = () => {
    observedScopes.forEach((observer, scope) => {
        if (!scope.isConnected) {
            observer.disconnect();
            observedScopes.delete(scope);
        }
    });

    registerRevealElements(document);
    initializeFeedbackAlerts(document);

    document.querySelectorAll('[data-page-reveal]').forEach((scope) => {
        if (observedScopes.has(scope)) return;

        const observer = new MutationObserver((mutations) => {
            if (mutations.some((mutation) => [...mutation.addedNodes].some((node) => node instanceof HTMLElement))) {
                scheduleRevealRegistration(scope);
            }
        });
        observer.observe(scope, { childList: true, subtree: true });
        observedScopes.set(scope, observer);
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
                if (event && (event.target !== entry.target || event.propertyName !== 'transform')) return;
                entry.target.removeEventListener('transitionend', settle);
                settleReveal(entry.target);
            };

            entry.target.addEventListener('transitionend', settle);
            window.setTimeout(() => settle(), 950);
        });
    }, { threshold: 0, rootMargin: '0px 0px -16px 0px' });
};

const startPageMotion = () => {
    document.documentElement.classList.add('motion-ready', 'ui-hydrated');
    createRevealObserver();
    observeMotionScopes();
};

const startNavigationFeedback = () => {
    window.clearTimeout(navigationSafetyTimer);
    document.documentElement.classList.add('is-navigating');
    navigationSafetyTimer = window.setTimeout(stopNavigationFeedback, 8000);
};

const stopNavigationFeedback = () => {
    window.clearTimeout(navigationSafetyTimer);
    document.documentElement.classList.remove('is-navigating');
};

const isEligibleNavigation = (event, link) => {
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return false;
    if (link.target && link.target !== '_self') return false;
    if (link.hasAttribute('download') || link.hasAttribute('data-no-navigation-feedback')) return false;

    const destination = new URL(link.href, window.location.href);
    const current = new URL(window.location.href);

    if (destination.origin !== current.origin) return false;
    if (!['http:', 'https:'].includes(destination.protocol)) return false;
    if (destination.pathname === current.pathname && destination.search === current.search && destination.hash) return false;

    return true;
};

document.addEventListener('click', (event) => {
    const link = event.target.closest?.('a[href]');
    if (link && isEligibleNavigation(event, link)) startNavigationFeedback();
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || form.hasAttribute('wire:submit') || (form.target && form.target !== '_self')) return;

    window.setTimeout(() => {
        if (!event.defaultPrevented) startNavigationFeedback();
    });
});

const stopMotionScopes = () => {
    revealObserver?.disconnect();
    observedScopes.forEach((observer) => observer.disconnect());
    observedScopes.clear();
    document.querySelectorAll('.reveal-item').forEach(settleReveal);
};

window.addEventListener('pagehide', stopMotionScopes);
window.addEventListener('pageshow', (event) => {
    stopNavigationFeedback();
    if (event.persisted) {
        createRevealObserver();
        observeMotionScopes();
    }
});

document.addEventListener('focusin', (event) => {
    const element = event.target.closest?.('.reveal-item');
    if (element) {
        revealObserver?.unobserve(element);
        settleReveal(element);
    }
});

document.addEventListener('livewire:init', () => {
    window.Livewire?.hook('request', ({ succeed, fail }) => {
        livewireRequests++;

        if (livewireRequests === 1) {
            networkFeedbackTimer = window.setTimeout(() => {
                if (livewireRequests > 0) document.documentElement.classList.add('is-network-busy');
            }, 160);
        }

        let finished = false;
        const finish = () => {
            if (finished) return;
            finished = true;
            livewireRequests = Math.max(0, livewireRequests - 1);

            if (livewireRequests === 0) {
                window.clearTimeout(networkFeedbackTimer);
                document.documentElement.classList.remove('is-network-busy');
            }
        };

        succeed(finish);
        fail(finish);
    });
});

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startPageMotion, { once: true });
} else {
    startPageMotion();
}

document.addEventListener('livewire:navigating', () => {
    startNavigationFeedback();
    stopMotionScopes();
});
document.addEventListener('livewire:navigated', () => {
    stopNavigationFeedback();
    createRevealObserver();
    document.querySelectorAll('.reveal-item').forEach(settleReveal);
    observeMotionScopes();
});

motionPreference.addEventListener('change', (event) => {
    reducedMotion = event.matches;
    createRevealObserver();

    if (reducedMotion) {
        document.querySelectorAll('.reveal-item').forEach((element) => {
            settleReveal(element);
        });
    }
});
