const initializedAlerts = new WeakSet();

// Shared by public, sign-in and Livewire pages; feedback never needs Alpine to be visible.
export const initializeFeedbackAlerts = (scope = document) => {
    const selector = '[data-feedback-alert]';
    const alerts = scope.matches?.(selector)
        ? [scope, ...scope.querySelectorAll(selector)]
        : [...scope.querySelectorAll(selector)];

    alerts.forEach((alert) => {
        if (initializedAlerts.has(alert)) return;
        initializedAlerts.add(alert);

        const progress = alert.querySelector('.feedback-progress');
        const duration = Math.max(0, Number(alert.dataset.feedbackDuration) || 0);
        let remaining = duration;
        let startedAt = 0;
        let timer;
        let closed = false;
        let hovered = false;
        let focused = false;

        const pause = () => {
            if (timer !== undefined) {
                window.clearTimeout(timer);
                timer = undefined;
                remaining = Math.max(0, remaining - (Date.now() - startedAt));
            }
            if (progress) progress.style.animationPlayState = 'paused';
        };

        const close = () => {
            if (closed) return;
            closed = true;
            pause();
            alert.classList.add('feedback-leaving');
            alert.setAttribute('aria-hidden', 'true');
            alert.inert = true;

            window.setTimeout(() => {
                const stack = alert.closest('[data-feedback-stack]');
                alert.remove();
                if (stack && !stack.querySelector('[data-feedback-alert]')) stack.remove();
            }, window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 320);
        };

        const start = () => {
            if (closed || duration === 0 || hovered || focused || timer !== undefined) return;
            if (remaining <= 0) { close(); return; }

            startedAt = Date.now();
            timer = window.setTimeout(close, remaining);
            if (progress) progress.style.animationPlayState = 'running';
        };

        alert.querySelector('[data-feedback-dismiss]')?.addEventListener('click', close);
        alert.addEventListener('mouseenter', () => { hovered = true; pause(); });
        alert.addEventListener('mouseleave', () => { hovered = false; start(); });
        alert.addEventListener('focusin', () => { focused = true; pause(); });
        alert.addEventListener('focusout', (event) => {
            focused = alert.contains(event.relatedTarget);
            start();
        });

        alert.classList.add('feedback-entering');
        window.requestAnimationFrame(() => window.requestAnimationFrame(() => {
            alert.classList.remove('feedback-entering');
            start();
        }));
    });
};
